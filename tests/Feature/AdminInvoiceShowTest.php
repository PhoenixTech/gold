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

    private function createSampleInvoice(): Invoice
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
            'code' => 'ZN-1001',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'علی رضایی',
            'mobile' => '09121234567',
            'email' => 'ali@example.com',
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

        $invoice = $this->createSampleInvoice();

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

    public function test_admin_can_view_invoice_print_layout_with_autoprint(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createSampleInvoice();

        $response = $this->get(route('admin.invoice.print', $invoice->hash));

        $response->assertOk();
        $response->assertViewIs('admin.invoices.invoice-show');
        $response->assertSee('window.print()', false);
        $response->assertSee($invoice->hash);
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
}
