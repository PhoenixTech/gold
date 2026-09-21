<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Transport;
use App\Models\User;
use App\Services\CartQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMetalSettings();
    }

    protected function seedMetalSettings(): void
    {
        foreach ([
            'gold' => 1_000_000,
            'silver' => 50_000,
            'min' => 100,
        ] as $key => $value) {
            $setting = new Setting;
            $setting->section = 'general';
            $setting->key = $key;
            $setting->type = 'TEXT';
            $setting->title = $key;
            $setting->ltr = true;
            $setting->size = 12;
            $setting->value = $value;
            $setting->raw = $value;
            $setting->save();
        }
    }

    public function test_discount_check_returns_valid_for_active_code(): void
    {
        Discount::factory()->create([
            'code' => 'GOLD10',
            'type' => 'PERCENT',
            'amount' => 10,
            'title' => 'تخفیف ۱۰ درصدی',
            'expire' => now()->addDays(5),
        ]);

        $response = $this->getJson(route('client.card.discount', 'GOLD10'));

        $response->assertOk();
        $response->assertJson([
            'OK' => true,
            'msg' => __('Discount code is valid.'),
        ]);
        $this->assertEquals('GOLD10', $response->json('data.code'));
        $this->assertEquals(10, $response->json('data.amount'));
    }

    public function test_discount_check_rejects_expired_code(): void
    {
        Discount::factory()->create([
            'code' => 'EXPIRED',
            'type' => 'PERCENT',
            'amount' => 20,
            'expire' => now()->subDay(),
        ]);

        $response = $this->getJson(route('client.card.discount', 'EXPIRED'));

        $response->assertOk();
        $response->assertJson([
            'OK' => false,
            'err' => __("Discount code isn't valid."),
        ]);
    }

    public function test_discount_check_rejects_empty_or_invalid_code(): void
    {
        $response = $this->getJson(route('client.card.discount', 'INVALID_CODE'));

        $response->assertOk();
        $response->assertJson([
            'OK' => false,
            'err' => __("Discount code isn't valid."),
        ]);
    }

    public function test_checkout_applies_discount_correctly_to_invoice(): void
    {
        BankAccount::factory()->active()->create();

        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '09121112233',
            'email' => 'discount'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان تست پلاک ۱';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک';
        $transport->price = 20000;
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
            'labor_charge_1' => 0,
            'profit' => 0,
            'tax' => 0,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 2_000_000,
            'code' => 'C-'.uniqid(),
        ]);

        $discount = Discount::factory()->create([
            'code' => 'OFF10',
            'type' => 'PERCENT',
            'amount' => 10,
            'expire' => now()->addDays(2),
        ]);

        $quote = app(CartQuoteService::class)->ensure();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
            'discount_id' => $discount->id,
        ]);

        $response->assertRedirect();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($discount->id, $invoice->discount_id);

        $expectedProductsTotal = (int) (((100 - 10) * (2 * 1_000_000)) / 100);
        $this->assertEquals($expectedProductsTotal + 20_000, $invoice->total_price);
    }
}
