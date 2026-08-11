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

    public function test_invoice_page_shows_active_bank_account_fields(): void
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

        $this->assertStringContainsString('Melli', $html);
        $this->assertStringContainsString('Shop Holder', $html);
        $this->assertStringContainsString('6037991111222233', $html);
        $this->assertStringContainsString('IR120170000000123456789001', $html);
        $this->assertStringContainsString(__('Awaiting Payment'), $html);
        $this->assertStringContainsString(__('Offline payment'), $html);
        $this->assertStringContainsString(__('Upload your payment receipt'), $html);
        $this->assertStringContainsString(__('Choose files or drop them here'), $html);
    }
}
