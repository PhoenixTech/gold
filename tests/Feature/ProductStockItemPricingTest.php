<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockItemPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMetalSettings();
    }

    public function test_reprice_updates_gold_products_only(): void
    {
        $gold = $this->makeProduct(['metal_type' => 'gold', 'status' => 1]);
        $silver = $this->makeProduct(['metal_type' => 'silver', 'status' => 1]);

        Quantity::factory()->create(['product_id' => $gold->id, 'weight' => 2, 'count' => 1, 'price' => 10, 'code' => 'G-'.uniqid()]);
        Quantity::factory()->create(['product_id' => $silver->id, 'weight' => 2, 'count' => 1, 'price' => 10, 'code' => 'S-'.uniqid()]);

        app(ProductPriceCalculator::class)->repriceProducts('gold');

        $this->assertNotSame(10, (int) $gold->quantities()->first()->fresh()->price);
        $this->assertSame(10, (int) $silver->quantities()->first()->fresh()->price);
    }

    public function test_adding_to_card_requires_available_stock_piece(): void
    {
        $product = $this->makeProduct(['metal_type' => 'gold', 'status' => 1, 'stock_status' => 'IN_STOCK']);
        Quantity::factory()->create(['product_id' => $product->id, 'weight' => 2, 'count' => 1, 'code' => 'A-'.uniqid()]);

        $response = $this->getJson(route('client.product-card-toggle', $product->slug));

        $response->assertStatus(422);
        $response->assertJsonFragment(['OK' => false]);
    }

    public function test_sold_piece_cannot_be_added_to_card_and_is_excluded_from_price(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'labor_charge_1' => 15,
            'profit' => 7,
            'tax' => 9,
        ]);

        $piece = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'code' => 'P-'.uniqid(),
        ]);
        $other = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 3,
            'count' => 1,
            'code' => 'O-'.uniqid(),
        ]);

        $calculator = app(ProductPriceCalculator::class);
        $calculator->repriceProduct($product->fresh(['quantities']));
        $piece->refresh();
        $other->refresh();

        $piece->markSold();
        $calculator->syncProductAggregates($product->fresh());

        $this->assertSame(0, $piece->fresh()->count);
        $this->assertSame($other->price, $product->fresh()->price);
        $this->assertSame(1, $product->fresh()->stock_quantity);

        $response = $this->getJson(route('client.product-card-toggle', $product->slug).'?quantity='.$piece->id);
        $response->assertStatus(422);
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

    protected function makeProduct(array $attributes = []): Product
    {
        $user = User::query()->first() ?? User::factory()->create();
        $category = Category::query()->first() ?? Category::factory()->create();

        return Product::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'product-'.uniqid(),
        ], $attributes));
    }
}
