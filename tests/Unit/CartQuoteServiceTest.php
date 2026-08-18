<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CartQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartQuoteService $quotes;

    protected ProductPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMetalSettings();
        $this->quotes = app(CartQuoteService::class);
        $this->calculator = app(ProductPriceCalculator::class);
    }

    public function test_quote_ttl_defaults_to_thirty_minutes(): void
    {
        $this->assertSame(30, $this->quotes->ttlMinutes());
    }

    public function test_card_items_use_live_gold_price_not_stored_piece_price(): void
    {
        [$product, $quantity] = $this->makeCartPiece(['price' => 111]);
        $this->bindCart($product, $quantity);

        $expected = $this->calculator->priceForQuantity($product, $quantity);
        $this->assertNotSame(111, $expected);

        $lines = cardItems();

        $this->assertCount(1, $lines);
        $this->assertSame($expected, $lines[0]['price']);
        $this->assertSame($expected, $lines[0]['q']['price']);
        $this->assertSame($expected, $lines[0]['qz'][0]['price']);
    }

    public function test_quote_locks_prices_until_expiry(): void
    {
        [$product, $quantity] = $this->makeCartPiece();
        $this->bindCart($product, $quantity);

        $quote = $this->quotes->refresh();
        $locked = $quote['prices'][$this->quotes->priceKey($product->id, $quantity->id)];

        $this->updateGold('4000000');
        $live = $this->calculator->priceForQuantity($product->fresh(), $quantity->fresh());
        $this->assertNotSame($locked, $live);

        $this->assertSame($locked, $this->quotes->unitPrice($product, $quantity));
        $this->assertSame($locked, cardItems()[0]['price']);
    }

    public function test_expired_checkout_quote_is_rejected_and_repriced(): void
    {
        [$product, $quantity] = $this->makeCartPiece();
        $this->bindCart($product, $quantity);

        $this->quotes->refresh();
        $this->travel(31)->minutes();
        $this->updateGold('4000000');

        try {
            $this->quotes->assertValidForCheckout();
            $this->fail('Expired quote should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('price', $exception->errors());
        }

        $expected = $this->calculator->priceForQuantity($product->fresh(), $quantity->fresh());
        $this->assertSame($expected, $this->quotes->current()['prices'][$this->quotes->priceKey($product->id, $quantity->id)]);
    }

    /**
     * @return array{0: Product, 1: Quantity}
     */
    protected function makeCartPiece(array $quantityAttributes = []): array
    {
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

        return [$product, $quantity];
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
