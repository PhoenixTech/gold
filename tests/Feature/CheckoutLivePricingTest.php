<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Transport;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutLivePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMetalSettings();
    }

    public function test_offline_payment_window_defaults_to_three_hours(): void
    {
        $this->assertSame(3, Invoice::offlinePaymentHours());
    }

    public function test_checkout_rejects_online_payment(): void
    {
        [$customer, $address, $transport, $product, $quantity] = $this->readyCheckout();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'online',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_invoice_uses_live_piece_price_not_stored_quantity_price(): void
    {
        [$customer, $address, $transport, $product, $quantity] = $this->readyCheckout([
            'price' => 111,
        ]);

        $expected = app(ProductPriceCalculator::class)->priceForQuantity($product, $quantity);
        $this->assertNotSame(111, $expected);

        $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ])->assertRedirect();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame($expected + (int) $transport->price, (int) $invoice->total_price);
        $this->assertSame($expected, (int) $invoice->orders()->first()->price_total);
    }

    public function test_invoice_keeps_quoted_price_when_gold_changes_within_thirty_minutes(): void
    {
        [$customer, $address, $transport, $product, $quantity] = $this->readyCheckout();
        $this->bindCart($product, $quantity);

        $quote = app(CartQuoteService::class)->refresh();
        $locked = $quote['prices'][app(CartQuoteService::class)->priceKey($product->id, $quantity->id)];

        $this->updateGold('4000000');
        $this->assertNotSame(
            $locked,
            app(ProductPriceCalculator::class)->priceForQuantity($product->fresh(), $quantity->fresh())
        );

        $this->actingAs($customer, 'customer')
            ->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]))
            ->withSession([CartQuoteService::SESSION_KEY => $quote])
            ->post(route('client.card.check'), [
                'product_id' => [$product->id],
                'count' => [1],
                'quantity_id' => [$quantity->id],
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'payment_method' => 'card',
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->first();
        $this->assertSame($locked + (int) $transport->price, (int) $invoice->total_price);
    }

    public function test_expired_quote_blocks_checkout_until_customer_reviews_new_prices(): void
    {
        [$customer, $address, $transport, $product, $quantity] = $this->readyCheckout();
        $this->bindCart($product, $quantity);

        $quote = app(CartQuoteService::class)->refresh();
        $this->travel(31)->minutes();
        $this->updateGold('4000000');

        $this->actingAs($customer, 'customer')
            ->from(route('client.card'))
            ->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]))
            ->withSession([CartQuoteService::SESSION_KEY => $quote])
            ->post(route('client.card.check'), [
                'product_id' => [$product->id],
                'count' => [1],
                'quantity_id' => [$quantity->id],
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'payment_method' => 'card',
            ])
            ->assertRedirect(route('client.card'))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('invoices', 0);
    }

    /**
     * @return array{0: Customer, 1: Address, 2: Transport, 3: Product, 4: Quantity}
     */
    protected function readyCheckout(array $quantityAttributes = []): array
    {
        BankAccount::factory()->active()->create();

        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '0912000'.rand(1000, 9999),
            'email' => 'liveprice'.uniqid().'@example.com',
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
            'metal_type' => 'gold',
        ]);

        $quantity = Quantity::factory()->create(array_merge([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 1_000_000,
            'code' => 'C-'.uniqid(),
        ], $quantityAttributes));

        return [$customer, $address, $transport, $product, $quantity];
    }

    protected function bindCart(Product $product, Quantity $quantity): void
    {
        request()->cookies->set('card', json_encode([$product->id]));
        request()->cookies->set('q', json_encode([$quantity->id]));
    }

    protected function updateGold(string $value): void
    {
        $setting = Setting::query()->where('key', 'gold')->firstOrFail();
        $setting->value = $value;
        $setting->raw = $value;
        $setting->save();
    }

    protected function seedMetalSettings(): void
    {
        foreach ([
            'gold' => '2000000',
            'silver' => '80000',
            'min' => '105',
        ] as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->section = 'General';
            $setting->type = 'TEXT';
            $setting->title = $key;
            $setting->ltr = true;
            $setting->size = 12;
            $setting->value = $value;
            $setting->raw = $value;
            $setting->save();
        }
    }
}
