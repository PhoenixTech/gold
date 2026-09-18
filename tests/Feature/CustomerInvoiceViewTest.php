<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee(route('client.profile') . '#invoices');
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
        $response->assertSee(__('Order') . ' <span class="font-fanum">#' . $invoice->id . '</span>', false);
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
            if (!array_key_exists($key, $fa) || trim((string)$fa[$key]) === '') {
                $missing[] = $key;
            }
        }

        $this->assertEmpty($missing, 'The following translation keys are missing or empty in resources/lang/fa.json: ' . implode(', ', $missing));
    }
}
