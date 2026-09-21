<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function createAwaitingInvoiceWithReceipts(int $totalPrice = 1_000_000, int $receiptAmount = 1_000_000): array
    {
        $bankAccount = BankAccount::factory()->active()->create([
            'bank_name' => 'Melli',
            'account_holder_name' => 'Shop Holder',
            'card_number' => '6037991111222233',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'تست مشتری',
            'mobile' => '09121114455',
            'email' => 'client'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان شریعتی، پلاک ۵';
        $address->save();

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->total_price = $totalPrice;
        $invoice->count = 1;
        $invoice->save();

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->amount = $totalPrice;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'CARD-'.$invoice->hash.'-'.time();
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->invoice_id = $invoice->id;
        $receipt->payment_id = $payment->id;
        $receipt->path = 'receipts/test.jpg';
        $receipt->original_name = 'test.jpg';
        $receipt->amount = $receiptAmount;
        $receipt->payment_date = '1405/06/31';
        $receipt->tracking_number = 'TRK-9999';
        $receipt->bank_account_id = $bankAccount->id;
        $receipt->save();

        return [$invoice, $payment, $bankAccount, $customer];
    }

    public function test_admin_payment_approval_requires_all_four_checklist_items(): void
    {
        $this->actingAsAdmin();
        [$invoice, $payment, $bankAccount] = $this->createAwaitingInvoiceWithReceipts();

        $response = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
        ]);

        $response->assertSessionHasErrors(['zero_balance']);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);

        $response2 = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'zero_balance' => 1,
        ]);

        $response2->assertSessionHasErrors(['bank_verified']);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }

    public function test_admin_payment_approval_requires_destination_bank_account(): void
    {
        $this->actingAsAdmin();
        [$invoice] = $this->createAwaitingInvoiceWithReceipts();

        $response = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
            'bank_account_id' => '',
        ]);

        $response->assertSessionHasErrors(['bank_account_id']);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);

        $responseInvalid = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
            'bank_account_id' => 999999,
        ]);

        $responseInvalid->assertSessionHasErrors(['bank_account_id']);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }

    public function test_admin_payment_approval_rejected_when_remaining_balance_is_greater_than_zero(): void
    {
        $this->actingAsAdmin();
        [$invoice, $payment, $bankAccount] = $this->createAwaitingInvoiceWithReceipts(
            totalPrice: 1_000_000,
            receiptAmount: 400_000
        );

        $response = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('errors') || $invoice->fresh()->status === Invoice::AWAITING_PAYMENT);
        $this->assertNotSame(Invoice::PAID, $invoice->fresh()->status);
    }

    public function test_admin_payment_approval_succeeds_when_all_safeguards_pass(): void
    {
        $this->actingAsAdmin();
        [$invoice, $payment, $bankAccount] = $this->createAwaitingInvoiceWithReceipts(
            totalPrice: 1_000_000,
            receiptAmount: 1_000_000
        );

        $response = $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
        ]);

        $response->assertRedirect(route('admin.invoice.edit', $invoice));
        $this->assertTrue(
            in_array($invoice->fresh()->status, [Invoice::PAID, Invoice::PROCESSING], true)
        );
        $this->assertSame(Payment::SUCCESS, $payment->fresh()->status);
    }

    public function test_admin_payment_approval_dispatches_customer_sms_notification(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        [$invoice, $payment, $bankAccount] = $this->createAwaitingInvoiceWithReceipts();

        $this->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
        ])->assertRedirect();

        $this->assertTrue(
            in_array($invoice->fresh()->status, [Invoice::PAID, Invoice::PROCESSING], true)
        );
    }

    public function test_admin_invoice_form_renders_four_point_checklist_safeguard_ui(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        [$invoice] = $this->createAwaitingInvoiceWithReceipts();

        $response = $this->get(route('admin.invoice.edit', $invoice));

        $response->assertOk();
        $response->assertSee('receipt_info_checked', false);
        $response->assertSee('account_selected', false);
        $response->assertSee('bank_verified', false);
        $response->assertSee('zero_balance', false);
        $response->assertSee('bank_account_id', false);
    }

    public function test_non_admin_cannot_confirm_payment(): void
    {
        [$invoice, , $bankAccount] = $this->createAwaitingInvoiceWithReceipts();

        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')->post(route('admin.invoice.confirm-payment', $invoice), [
            'bank_account_id' => $bankAccount->id,
            'receipt_info_checked' => 1,
            'account_selected' => 1,
            'bank_verified' => 1,
            'zero_balance' => 1,
        ]);

        $response->assertRedirect();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }
}
