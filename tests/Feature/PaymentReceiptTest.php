<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Customer, 1: Invoice, 2: Payment}
     */
    private function createAwaitingCardInvoice(): array
    {
        BankAccount::factory()->active()->create([
            'bank_name' => 'Melli',
            'account_holder_name' => 'Shop Holder',
            'card_number' => '6037991111222233',
            'iban' => 'IR120170000000123456789001',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '0912000'.rand(1000, 9999),
            'email' => 'buyer'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان آزادی پلاک ۱۰';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک';
        $transport->price = 50000;
        $transport->is_default = 1;
        $transport->sort = 1;
        $transport->save();

        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => 1_000_000,
            'stock_quantity' => 1,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 1_000_000,
            'code' => 'C-'.uniqid(),
        ]);

        $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ])->assertRedirect();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $payment = $invoice->payments()->where('type', 'CARD')->latest('id')->firstOrFail();

        return [$customer, $invoice, $payment];
    }

    public function test_customer_can_upload_multiple_receipts(): void
    {
        Storage::fake('public');
        [$customer, $invoice] = $this->createAwaitingCardInvoice();

        $response = $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [
                UploadedFile::fake()->image('receipt1.jpg'),
                UploadedFile::fake()->create('receipt2.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_other_customer_cannot_upload_receipts(): void
    {
        Storage::fake('public');
        [, $invoice] = $this->createAwaitingCardInvoice();

        $other = Customer::factory()->create([
            'name' => 'Other',
            'mobile' => '0912111'.rand(1000, 9999),
            'email' => 'other'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($other, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ]);

        $response->assertForbidden();
        $this->assertSame(0, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_guest_cannot_upload_receipts(): void
    {
        Storage::fake('public');
        [, $invoice] = $this->createAwaitingCardInvoice();

        auth('customer')->logout();

        $response = $this->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ]);

        $response->assertRedirect();
        $this->assertGuest('customer');
        $this->assertSame(0, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_admin_can_confirm_payment_marks_invoice_paid(): void
    {
        Storage::fake('public');
        [$customer, $invoice, $payment] = $this->createAwaitingCardInvoice();

        $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.invoice.confirm-payment', $invoice));

        $response->assertRedirect(route('admin.invoice.edit', $invoice));
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(Payment::SUCCESS, $payment->fresh()->status);
    }

    public function test_receipt_upload_is_blocked_after_deadline(): void
    {
        Storage::fake('public');
        [$customer, $invoice] = $this->createAwaitingCardInvoice();

        $invoice->forceFill(['created_at' => now()->subHours(4)])->save();

        $response = $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ]);

        $response->assertRedirect();
        $this->assertSame(0, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
        $response->assertSessionHasErrors();
    }

    public function test_expire_offline_command_fails_overdue_invoices_without_receipts(): void
    {
        [$customer, $invoice, $payment] = $this->createAwaitingCardInvoice();

        $invoice->forceFill(['created_at' => now()->subHours(4)])->save();
        $payment->forceFill(['created_at' => now()->subHours(4)])->save();

        $this->artisan('offline:expire')->assertSuccessful();

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(Payment::FAIL, $payment->fresh()->status);
    }

    public function test_expire_offline_command_skips_invoices_with_receipts(): void
    {
        Storage::fake('public');
        [$customer, $invoice, $payment] = $this->createAwaitingCardInvoice();

        $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        $invoice->forceFill(['created_at' => now()->subHours(4)])->save();

        $this->artisan('offline:expire')->assertSuccessful();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
        $this->assertSame(Payment::PENDING, $payment->fresh()->status);
    }

    public function test_invoice_page_shows_offline_deadline(): void
    {
        [$customer, $invoice] = $this->createAwaitingCardInvoice();
        $invoice->load(['customer', 'address.state', 'address.city', 'orders.product', 'orders.quantity', 'payments', 'paymentReceipts']);

        $html = view('segments.invoice.LianaInvoice.LianaInvoice', [
            'invoice' => $invoice,
            'qr' => new class
            {
                public function render(string $url): string
                {
                    return 'data:image/svg+xml,'.rawurlencode('<svg></svg>');
                }
            },
            'data' => (object) [
                'area_name' => 'invoice',
                'part' => 'LianaInvoice',
            ],
        ])->render();

        $this->assertStringContainsString(__('Deadline:'), $html);
        $this->assertStringContainsString(__('Pay and upload your receipt within :hours hours.', ['hours' => Invoice::offlinePaymentHours()]), $html);
        $this->assertStringContainsString(Invoice::formatPersianDateTime($invoice->offlinePaymentDeadline()), $html);
        $this->assertStringNotContainsString($invoice->offlinePaymentDeadline()->format('Y-m-d H:i'), $html);
        $this->assertStringContainsString(__('WAITING_RECEIPT'), $html);
    }

    public function test_offline_invoice_without_receipt_is_waiting_receipt(): void
    {
        [, $invoice] = $this->createAwaitingCardInvoice();

        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->fresh()->load('paymentReceipts')->displayStatusKey());
    }

    public function test_offline_invoice_with_receipt_is_waiting_confirmation(): void
    {
        Storage::fake('public');
        [$customer, $invoice] = $this->createAwaitingCardInvoice();

        $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        $this->assertSame(
            Invoice::WAITING_CONFIRMATION,
            $invoice->fresh()->load('paymentReceipts')->displayStatusKey()
        );
    }

    public function test_admin_filter_statuses_replace_pending_with_offline_states(): void
    {
        $this->assertNotContains(Invoice::PENDING, Invoice::adminFilterStatuses());
        $this->assertContains(Invoice::WAITING_RECEIPT, Invoice::adminFilterStatuses());
        $this->assertContains(Invoice::WAITING_CONFIRMATION, Invoice::adminFilterStatuses());
        $this->assertNotContains(Invoice::PENDING, Invoice::editableStatuses());
    }

    public function test_admin_invoice_edit_shows_next_step_and_persian_deadline(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        [, $invoice] = $this->createAwaitingCardInvoice();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.invoice.edit', $invoice));

        $response->assertOk();
        $response->assertSee(__('WAITING_RECEIPT'), false);
        $response->assertSee(__('Shipping and tracking'), false);
        $response->assertSee(__('Delivery address'), false);
        $response->assertSee(Invoice::formatPersianDateTime($invoice->offlinePaymentDeadline()), false);
        $response->assertDontSee($invoice->offlinePaymentDeadline()->format('Y-m-d H:i'), false);
        $response->assertDontSee('value="PENDING"', false);
    }

    public function test_admin_can_filter_invoices_waiting_for_confirmation(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        Storage::fake('public');

        [$customer, $withReceipt] = $this->createAwaitingCardInvoice();
        $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $withReceipt), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        [, $withoutReceipt] = $this->createAwaitingCardInvoice();

        $withReceipt->customer->name = 'Waiting Confirmation Buyer';
        $withReceipt->customer->save();
        $withoutReceipt->customer->name = 'Waiting Receipt Buyer';
        $withoutReceipt->customer->save();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.invoice.index', [
            'filter' => ['status' => Invoice::WAITING_CONFIRMATION],
        ]));

        $response->assertOk();
        $response->assertSee('Waiting Confirmation Buyer', false);
        $response->assertDontSee('Waiting Receipt Buyer', false);
    }

    public function test_admin_invoice_list_shows_jalali_created_at_instead_of_hash(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        app()->setLocale('fa');

        [, $invoice] = $this->createAwaitingCardInvoice();
        $invoice->customer->name = 'List Date Customer';
        $invoice->customer->save();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.invoice.index'));

        $response->assertOk();
        $response->assertSee('List Date Customer', false);
        $response->assertSee(__('created_at'), false);
        $response->assertDontSee(__('hash'), false);
        $response->assertSee(Invoice::formatPersianDateTime($invoice->created_at), false);
        $response->assertDontSee($invoice->created_at->format('Y-m-d H:i'), false);
    }

    public function test_invoice_page_hides_offline_payment_panel_for_failed_invoices(): void
    {
        [$customer, $invoice, $payment] = $this->createAwaitingCardInvoice();

        $invoice->forceFill(['status' => Invoice::FAILED])->save();
        $payment->forceFill(['status' => Payment::FAIL])->save();
        $invoice->load(['customer', 'address.state', 'address.city', 'orders.product', 'orders.quantity', 'payments', 'paymentReceipts']);

        $html = view('segments.invoice.LianaInvoice.LianaInvoice', [
            'invoice' => $invoice,
            'qr' => new class
            {
                public function render(string $url): string
                {
                    return 'data:image/svg+xml,'.rawurlencode('<svg></svg>');
                }
            },
            'data' => (object) [
                'area_name' => 'invoice',
                'part' => 'LianaInvoice',
            ],
        ])->render();

        $this->assertStringNotContainsString('liana-payment-panel', $html);
        $this->assertStringNotContainsString('Card to card', $html);
    }
}
