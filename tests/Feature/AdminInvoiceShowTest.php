<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceShowTest extends TestCase
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

    private function createSampleInvoice(array $attributes = []): Invoice
    {
        $admin = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'name' => 'طلای ۱۸ عیار ژونلا',
            'status' => 1,
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2.450,
            'code' => 'ZN-'.rand(1000, 99999),
        ]);

        $customer = Customer::factory()->create([
            'name' => $attributes['customer_name'] ?? 'علی رضایی',
            'mobile' => $attributes['customer_mobile'] ?? ('0912'.rand(1000000, 9999999)),
            'email' => $attributes['customer_email'] ?? ('customer_'.uniqid().'@example.com'),
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان ولیعصر، پلاک ۱۲۳';
        $address->zip = '1234567890';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پست پیشتاز';
        $transport->price = 45000;
        $transport->save();

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->transport_price = 45000;
        $invoice->status = Invoice::PAID;
        $invoice->total_price = 5045000;
        $invoice->count = 1;
        $invoice->tracking_code = 'POST-998877';
        $invoice->desc = 'لطفا با بسته‌بندی کادویی ارسال شود.';
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = 5000000;
        $order->save();

        $payment = new Payment;
        $payment->order_id = rand(100000, 999999);
        $payment->invoice_id = $invoice->id;
        $payment->type = 'ONLINE';
        $payment->status = Payment::SUCCESS;
        $payment->amount = 5045000;
        $payment->reference_id = 'REF-123456789';
        $payment->save();

        return $invoice;
    }

    public function test_guest_cannot_access_admin_invoice_show_or_print(): void
    {
        $invoice = $this->createSampleInvoice();

        $this->get(route('admin.invoice.show', $invoice->hash))->assertRedirect();
        $this->get(route('admin.invoice.print', $invoice->hash))->assertRedirect();
    }

    public function test_admin_can_view_invoice_in_admin_layout(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice(['customer_mobile' => '09121234567']);

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.invoice-show');
        $response->assertSee($invoice->hash);
        $response->assertSee('علی رضایی');
        $response->assertSee('09121234567');
        $response->assertSee('طلای ۱۸ عیار ژونلا');
        $response->assertSee('POST-998877');
        $response->assertSee('REF-123456789');
        $response->assertSee(route('client.invoice', $invoice->hash));
        $response->assertSee(route('admin.invoice.edit', $invoice));
    }

    public function test_admin_invoice_show_renders_store_pickup_without_shipment_placeholders(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        $invoice = Invoice::factory()->readyForPickup()->create();

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertSee('data-fulfillment-method="pickup"', false);
        $response->assertSee(__('Store pickup'));
        $response->assertSee(__('Pickup location'));
        $response->assertSee(__('Pickup cost'));
        $response->assertDontSee(__('No address registered.'));
        $response->assertDontSee(__('Standard Transport'));
        $response->assertDontSee(__('Pending shipment'));
        $response->assertDontSee(__('Shipping cost'));
    }

    public function test_admin_can_view_invoice_print_layout_with_autoprint(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();

        $response = $this->get(route('admin.invoice.print', $invoice->hash));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.invoice-print');
        $response->assertSee('window.print()', false);
        $response->assertSee($invoice->hash);
    }

    public function test_print_layout_is_a_standalone_document_without_the_admin_chrome(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();

        $response = $this->get(route('admin.invoice.print', $invoice->hash));

        $response->assertOk();
        // A standalone document declares its own DOCTYPE and never renders the
        // admin shell, so printing cannot depend on an ID-based @media print
        // hide-list staying in sync with the panel markup.
        $this->assertStringStartsWith('<!DOCTYPE html>', $response->getContent());
        $response->assertDontSee('id="panel-top-navbar"', false);
        $response->assertDontSee('id="sidebar-panel"', false);
        $response->assertDontSee('<aside', false);

        // Print sizing must be declared on the document itself.
        $response->assertSee('@page', false);
        $response->assertSee('size: A4 portrait', false);

        // The 4-point approval modal belongs on the screen view, not the paper.
        $response->assertDontSee('id="confirmPaymentModal"', false);
    }

    public function test_print_layout_waits_for_assets_before_automatically_printing(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();

        $content = $this->get(route('admin.invoice.print', $invoice->hash))->getContent();

        // A fixed setTimeout printed before the logo, QR and product
        // thumbnails had painted, producing truncated printouts.
        $this->assertStringNotContainsString('setTimeout(window.print()', $content);
        $this->assertStringContainsString("window.addEventListener('load'", $content);
        $this->assertStringContainsString('document.fonts.ready', $content);
    }

    public function test_print_layout_names_the_third_party_recipient_for_gift_orders(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();
        $invoice->is_third_party = true;
        $invoice->recipient_name = 'Maryam Gift';
        $invoice->recipient_mobile = '09121234567';
        $invoice->recipient_national_id = '0012345678';
        $invoice->save();

        $response = $this->get(route('admin.invoice.print', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee(__('Recipient (gift order)'));
        $response->assertSee('Maryam Gift');
        $response->assertSee('09121234567');
        $response->assertSee('0012345678');
    }

    public function test_print_layout_shows_offline_payment_evidence(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::SUCCESS;
        $payment->amount = $invoice->total_price;
        $payment->order_id = 'CARD-'.$invoice->hash;
        $payment->reference_id = 'REF-PRINT-1';
        $payment->meta = [
            'confirmed_at' => now()->toDateTimeString(),
            'confirmed_by_name' => 'Manager',
            'bank_account_name' => 'Melli Destination',
        ];
        $payment->save();

        PaymentReceipt::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'path' => 'receipts/print.png',
            'original_name' => 'print.png',
            'mime' => 'image/png',
            'size' => 100,
            'amount' => $invoice->total_price,
        ]);

        $response = $this->get(route('admin.invoice.print', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee(__('Card to card'));
        $response->assertSee(__('Paid in full'));
        $response->assertSee(__('Receipts uploaded'));
        $response->assertSee(__('Received via receipts'));
        $response->assertSee(__('Remaining balance'));
        $response->assertSee('Melli Destination');
        $response->assertSee('Manager');
        $response->assertSee('REF-PRINT-1');
    }

    public function test_print_layout_labels_customer_credit_as_credit_not_discount(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();
        $invoice->credit_price = 250000;
        $invoice->save();

        $response = $this->get(route('admin.invoice.print', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee(__('Customer credit used'));
        $response->assertDontSee(__('Discount amount'));
    }

    public function test_invoice_show_displays_confirm_payment_button_when_waiting_confirmation(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();
        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->save();

        $payment = new Payment;
        $payment->order_id = rand(100000, 999999);
        $payment->invoice_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = $invoice->total_price;
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->invoice_id = $invoice->id;
        $receipt->payment_id = $payment->id;
        $receipt->uploaded_by_customer_id = $invoice->customer_id;
        $receipt->path = 'receipts/sample.jpg';
        $receipt->original_name = 'sample.jpg';
        $receipt->mime = 'image/jpeg';
        $receipt->size = 10240;
        $receipt->save();

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertSee(route('admin.invoice.confirm-payment', $invoice));
        $response->assertSee('sample.jpg');
    }

    public function test_admin_invoice_list_only_shows_print_button_for_accepted_invoices(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $accepted = $this->createSampleInvoice();
        $accepted->status = Invoice::PAID;
        $accepted->save();

        $unaccepted = $this->createSampleInvoice();
        $unaccepted->status = Invoice::AWAITING_PAYMENT;
        $unaccepted->save();

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();
        $response->assertSee(route('admin.invoice.print', $accepted->hash));
        $response->assertDontSee(route('admin.invoice.print', $unaccepted->hash));
    }

    public function test_admin_cannot_print_unaccepted_invoice(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $unaccepted = $this->createSampleInvoice();
        $unaccepted->status = Invoice::AWAITING_PAYMENT;
        $unaccepted->save();

        $response = $this->get(route('admin.invoice.print', $unaccepted->hash));

        $response->assertRedirect(route('admin.invoice.index'));
        $response->assertSessionHasErrors();
    }

    public function test_admin_invoice_show_hides_print_button_for_unaccepted_invoice(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $unaccepted = $this->createSampleInvoice();
        $unaccepted->status = Invoice::AWAITING_PAYMENT;
        $unaccepted->save();

        $response = $this->get(route('admin.invoice.show', $unaccepted->hash));

        $response->assertOk();
        $response->assertDontSee(__('Print invoice'));
    }
}
