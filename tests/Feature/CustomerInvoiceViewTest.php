<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerInvoiceViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GfxSeeder::class);
    }

    private function createCustomerWithInvoice(): array
    {
        $customer = Customer::factory()->create([
            'name' => 'مهندس کیانی',
            'mobile' => '09121112233',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان ولیعصر، پلاک ۱۰۰';
        $address->save();

        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'name' => 'دستبند طلای ۱۸ عیار زنانه لوتوس',
            'price' => 7_500_000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 5,
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2.350,
            'count' => 1,
            'price' => 7_500_000,
            'code' => 'LOTUS-18K',
        ]);

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->status = Invoice::PROCESSING;
        $invoice->total_price = 7_550_000;
        $invoice->transport_price = 50_000;
        $invoice->count = 1;
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = 7_500_000;
        $order->save();

        return [$customer, $invoice, $product, $order];
    }

    public function test_invoice_view_uses_customer_dashboard_layout(): void
    {
        [$customer, $invoice, $product] = $this->createCustomerWithInvoice();

        $response = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));

        $response->assertOk();

        // 1. Frontend website layout elements (header, footer) should NOT be present
        $response->assertDontSee('id="zar-menu"', false);
        $response->assertDontSee('WTFFooter');

        // 2. Customer dashboard layout elements must be present
        $response->assertSee('id="AvisaCustomer"', false);
        $response->assertSee('class="avisa-container-mobile"', false);
        $response->assertSee('class="avisa-bottom-navbar', false);

        // 3. Back to orders navigation and header
        $response->assertSee(route('client.profile').'#invoices');
        $response->assertSee(__('Back to orders'));
        $response->assertSee(__('Order details'));

        // 4. Products and prices
        $response->assertSee('دستبند طلای ۱۸ عیار زنانه لوتوس');
        $response->assertSee('7,550,000');
        $response->assertSee('7,500,000');
        $response->assertSee('50,000');
        $response->assertSee('تهران، خیابان ولیعصر، پلاک ۱۰۰');
    }

    public function test_profile_invoices_tab_renders_redesigned_cards_with_product_info(): void
    {
        [$customer, $invoice, $product] = $this->createCustomerWithInvoice();

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();

        // Check for natural customer order card components
        $response->assertSee('class="card avisa-card-ref avisa-order-card', false);
        $response->assertSee(__('Order').' <span class="font-fanum">#'.$invoice->id.'</span>', false);
        $response->assertSee('دستبند طلای ۱۸ عیار زنانه لوتوس');
        $response->assertSee('7,550,000');
        $response->assertSee(route('client.invoice', $invoice->hash));
        $response->assertSee(__('Order details'));
    }

    public function test_unauthorized_customer_cannot_view_another_customer_invoice(): void
    {
        [$customer, $invoice] = $this->createCustomerWithInvoice();
        $otherCustomer = Customer::factory()->create();

        $response = $this->actingAs($otherCustomer, 'customer')->get(route('client.invoice', $invoice->hash));

        $response->assertRedirect(route('client.sign-in'));
    }

    public function test_all_translation_keys_in_invoice_and_card_views_exist_in_fa_json(): void
    {
        $files = [
            resource_path('views/client/customer/invoice.blade.php'),
            resource_path('views/client/customer/partials/invoice-card.blade.php'),
            resource_path('views/client/customer/partials/bottom-nav.blade.php'),
            resource_path('views/layouts/customer.blade.php'),
        ];

        $fa = json_decode(file_get_contents(resource_path('lang/fa.json')), true);

        $keys = [];
        foreach ($files as $file) {
            $blade = file_get_contents($file);
            preg_match_all("/__\(\s*[\x27\x22](.*?)[\x27\x22]\s*[\),]/", $blade, $matches);
            $keys = array_merge($keys, $matches[1]);
        }
        $keys = array_unique($keys);

        $missing = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $fa) || trim((string) $fa[$key]) === '') {
                $missing[] = $key;
            }
        }

        $this->assertEmpty($missing, 'The following translation keys are missing or empty in resources/lang/fa.json: '.implode(', ', $missing));
    }

    public function test_offline_invoice_shows_positive_remaining_seconds_in_countdown(): void
    {
        [$customer, $invoice] = $this->createCustomerWithInvoice();

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = $invoice->total_price;
        $payment->order_id = 'ORDER-'.uniqid();
        $payment->save();

        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->save();

        $response = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));
        $response->assertOk();

        preg_match('/data-deadline="(\d+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'Could not find data-deadline in invoice response');
        $this->assertGreaterThan(0, (int) $matches[1], 'Countdown data-deadline should be greater than 0');
    }

    public function test_invoice_card_updates_alert_and_hides_upload_button_after_receipt_upload(): void
    {
        Storage::fake('public');
        [$customer, $invoice] = $this->createCustomerWithInvoice();

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = $invoice->total_price;
        $payment->order_id = 'ORDER-'.uniqid();
        $payment->save();

        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->save();

        // 1. Before uploading receipt: should prompt customer to upload and show upload modal button
        $responseBefore = $this->actingAs($customer, 'customer')->get(route('client.profile'));
        $responseBefore->assertOk();
        $responseBefore->assertSee(__('Please upload your payment receipt'));
        $responseBefore->assertSee('data-receipt-modal-open', false);
        $responseBefore->assertDontSee(__('Payment receipt is under review'));

        // 2. Upload receipt
        $this->actingAs($customer, 'customer')->post(route('client.invoice.receipts.store', $invoice), [
            'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        // 3. After uploading receipt: should show review state and hide upload button
        $responseAfter = $this->actingAs($customer, 'customer')->get(route('client.profile'));
        $responseAfter->assertOk();
        $responseAfter->assertSee(__('Payment receipt is under review'));
        $responseAfter->assertDontSee(__('Please upload your payment receipt'));
        $responseAfter->assertDontSee('data-receipt-modal-open', false);

        // In invoice view, payment panel is still visible while awaiting confirmation
        $responseInvoicePending = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));
        $responseInvoicePending->assertOk();
        $responseInvoicePending->assertSee('id="payment-panel"', false);

        // 4. When payment is accepted (marked PAID):
        $invoice->status = Invoice::PAID;
        $invoice->save();

        $this->assertTrue($invoice->isActive());
        $this->assertTrue(in_array(Invoice::PAID, Invoice::activeStatuses(), true));

        // Invoice must still be present in active orders menu/tab in profile
        $responseAcceptedProfile = $this->actingAs($customer, 'customer')->get(route('client.profile'));
        $responseAcceptedProfile->assertOk();
        $responseAcceptedProfile->assertSee(route('client.invoice', $invoice->hash));
        $responseAcceptedProfile->assertDontSee(__('Payment receipt is under review'));
        $responseAcceptedProfile->assertDontSee(__('Please upload your payment receipt'));
        $responseAcceptedProfile->assertDontSee('data-receipt-modal-open', false);

        // Invoice view should NOT show payment-panel once accepted
        $responseAcceptedInvoice = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));
        $responseAcceptedInvoice->assertOk();
        $responseAcceptedInvoice->assertDontSee('id="payment-panel"', false);
        $responseAcceptedInvoice->assertDontSee('liana-payment-panel', false);

        // 5. When order is finally delivered/completed:
        $invoice->status = Invoice::COMPLETED;
        $invoice->save();
        $this->assertFalse($invoice->isActive());
    }
}
