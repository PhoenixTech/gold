<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Services\SmsService;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression tests for the gaps found while tracing the card-to-card flow:
 * checkout -> receipt upload -> admin review -> paid.
 */
class CardToCardFlowGapsTest extends TestCase
{
    use RefreshDatabase;

    private BankAccount $bank;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->bank = BankAccount::factory()->active()->create();
    }

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{0: Customer, 1: Invoice, 2: Payment}
     */
    private function awaitingInvoice(int $total = 1_000_000, ?\DateTimeInterface $createdAt = null): array
    {
        $customer = Customer::factory()->create(['credit' => 0]);
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::AWAITING_PAYMENT,
            'total_price' => $total,
            'created_at' => $createdAt ?? now(),
        ]);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'CARD-'.$invoice->hash;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = $total;
        $payment->save();

        return [$customer, $invoice, $payment];
    }

    private function addReceipt(Invoice $invoice, Payment $payment, int $amount): PaymentReceipt
    {
        return PaymentReceipt::query()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'path' => 'payment-receipts/'.$invoice->id.'/slip.jpg',
            'original_name' => 'slip.jpg',
            'mime' => 'image/jpeg',
            'amount' => $amount,
        ]);
    }

    private function confirm(Invoice $invoice)
    {
        return $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $this->bank->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
        ]);
    }

    // A: an empty receipt row must not park the invoice in review forever.
    public function test_receipt_without_slip_or_amount_is_rejected(): void
    {
        [$customer, $invoice] = $this->awaitingInvoice();

        $this->actingAs($customer, 'customer')
            ->post(route('client.invoice.receipts.store', $invoice), ['receipts' => [['tracking_number' => '123']]])
            ->assertSessionHasErrors(['receipts.0.amount', 'receipts.0.slip']);

        $this->assertFalse($invoice->fresh()->hasUploadedReceipt());
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->fresh()->displayStatusKey());
    }

    // B: a partial shop payment must not block the customer's receipt upload.
    public function test_customer_can_upload_receipt_after_admin_records_partial_payment(): void
    {
        $this->actingAsAdmin();
        [$customer, $invoice, $payment] = $this->awaitingInvoice();

        $this->post(route('admin.invoice.add-payment', $invoice), ['method' => 'pos', 'amount' => 400_000])
            ->assertRedirect();

        $this->actingAs($customer, 'customer')
            ->post(route('client.invoice.receipts.store', $invoice), [
                'receipts' => [['amount' => 600_000, 'slip' => UploadedFile::fake()->image('r.jpg')]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($payment->id, $invoice->fresh()->paymentReceipts()->first()->payment_id);
        $this->assertSame(0, $invoice->fresh()->remainingReceiptBalance());
    }

    // B: the expiry job must not fail an invoice that already holds shop money.
    public function test_expiry_skips_invoice_with_recorded_shop_payment(): void
    {
        $this->actingAsAdmin();
        [, $invoice] = $this->awaitingInvoice(createdAt: now()->subDay());

        $this->post(route('admin.invoice.add-payment', $invoice), ['method' => 'pos', 'amount' => 400_000]);
        $this->artisan('offline:expire')->assertSuccessful();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }

    // B: settling a website invoice in the shop closes the pending card payment and notifies once.
    public function test_full_shop_payment_closes_pending_card_payment_and_sends_one_sms(): void
    {
        $sms = Mockery::spy(SmsService::class);
        $this->app->instance(SmsService::class, $sms);
        $this->actingAsAdmin();
        [, $invoice, $payment] = $this->awaitingInvoice();

        $this->post(route('admin.invoice.add-payment', $invoice), ['method' => 'pos', 'amount' => 1_000_000]);

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(Payment::CANCEL, $payment->fresh()->status);
        $sms->shouldHaveReceived('send')->once();
    }

    // B: unverified receipts must still go through review before the invoice is paid.
    public function test_shop_payment_plus_unreviewed_receipt_does_not_mark_paid(): void
    {
        $this->actingAsAdmin();
        [, $invoice, $payment] = $this->awaitingInvoice();
        $this->addReceipt($invoice, $payment, 600_000);

        $this->post(route('admin.invoice.add-payment', $invoice), ['method' => 'pos', 'amount' => 400_000]);

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
        $this->assertSame(Payment::PENDING, $payment->fresh()->status);

        $this->confirm($invoice)->assertSessionHasNoErrors();
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(600_000 * (int) config('app.currency.factor'), (int) $payment->fresh()->amount);
    }

    // B: canceling an unpaid invoice refunds money the shop already recorded.
    public function test_cancel_refunds_recorded_shop_payment(): void
    {
        $this->actingAsAdmin();
        [$customer, $invoice] = $this->awaitingInvoice();

        $this->post(route('admin.invoice.add-payment', $invoice), ['method' => 'pos', 'amount' => 400_000]);
        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'Customer changed mind']);

        $this->assertSame(Invoice::CANCELED, $invoice->fresh()->status);
        $this->assertSame(400_000, (int) $customer->fresh()->credit);
    }

    // C: an overpaying receipt goes to the customer credit.
    public function test_overpayment_is_added_to_customer_credit(): void
    {
        $this->actingAsAdmin();
        [$customer, $invoice, $payment] = $this->awaitingInvoice();
        $this->addReceipt($invoice, $payment, 1_050_000);

        $this->confirm($invoice)->assertSessionHasNoErrors();

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(50_000, (int) $customer->fresh()->credit);
    }

    // D: approving a receipt sends exactly one SMS.
    public function test_payment_approval_sends_one_sms(): void
    {
        $sms = Mockery::spy(SmsService::class);
        $this->app->instance(SmsService::class, $sms);
        $this->actingAsAdmin();
        [, $invoice, $payment] = $this->awaitingInvoice();
        $this->addReceipt($invoice, $payment, 1_000_000);

        $this->confirm($invoice)->assertSessionHasNoErrors();

        $sms->shouldHaveReceived('send')->once();
    }

    // H: a re-upload request keeps the old receipt visible to the admin as history.
    public function test_reupload_request_keeps_rejected_receipt_in_admin_history(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        [, $invoice, $payment] = $this->awaitingInvoice();
        $this->addReceipt($invoice, $payment, 1_000_000);

        $this->post(route('admin.invoice.request-receipt-reupload', $invoice), ['reason' => 'Blurry photo']);

        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->fresh()->displayStatusKey());
        $this->assertSame(1, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
        $this->get(route('admin.invoice.edit', $invoice))
            ->assertOk()
            ->assertSee('Blurry photo');
    }
}
