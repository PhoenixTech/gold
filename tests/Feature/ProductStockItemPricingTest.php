<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    public function test_adding_to_card_uses_the_first_entered_available_piece(): void
    {
        $product = $this->makeProduct(['metal_type' => 'gold', 'status' => 1, 'stock_status' => 'IN_STOCK']);
        $first = Quantity::factory()->create(['product_id' => $product->id, 'weight' => 5, 'count' => 1, 'code' => 'A-'.uniqid()]);
        Quantity::factory()->create(['product_id' => $product->id, 'weight' => 1, 'count' => 1, 'code' => 'B-'.uniqid()]);

        $response = $this->getJson(route('client.product-card-toggle', $product->slug));

        $response->assertOk();
        $response->assertJsonFragment(['OK' => true]);
        $this->assertTrue($first->is($product->firstAvailableQuantity()));
    }

    public function test_product_price_uses_first_entered_available_piece_not_the_cheapest(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'status' => 1,
            'labor_charge_1' => 15,
            'profit' => 7,
            'tax' => 9,
        ]);

        $first = Quantity::factory()->create(['product_id' => $product->id, 'weight' => 4, 'count' => 1, 'code' => 'F-'.uniqid()]);
        $cheaper = Quantity::factory()->create(['product_id' => $product->id, 'weight' => 1, 'count' => 1, 'code' => 'C-'.uniqid()]);

        app(ProductPriceCalculator::class)->repriceProduct($product->fresh(['quantities']));
        $first->refresh();
        $cheaper->refresh();

        $this->assertTrue($first->is($product->firstAvailableQuantity()));
        $this->assertSame($first->price, $product->lowestAvailablePrice());
        $this->assertGreaterThan($cheaper->price, $product->lowestAvailablePrice());
    }

    public function test_product_view_only_offers_the_first_available_piece(): void
    {
        $blade = file_get_contents(resource_path('views/segments/product/ProductAria/ProductAria.blade.php'));
        $vue = file_get_contents(base_path('resources/js/client-vue/QuantitiesAddToCard.vue'));

        $this->assertNotFalse($blade);
        $this->assertNotFalse($vue);
        $this->assertStringContainsString('firstAvailableQuantity', $blade);
        $this->assertStringContainsString('$offerPiece', $blade);
        $this->assertStringNotContainsString('$availableStockItems->count()', $blade);
        $this->assertStringNotContainsString('availableQuantities()->get()', $blade);
        $this->assertStringContainsString('this.select(first)', $vue);
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

    public function test_saving_stock_pieces_assigns_sequential_skus_from_product_sku(): void
    {
        $this->actingAsAdmin();
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'target_group' => 'unisex',
            'status' => 1,
            'stock_status' => 'IN_STOCK',
        ]);
        $sku = $product->sku;

        $response = $this->post(route('admin.product.update', $product), [
            'id' => $product->id,
            'name' => $product->name,
            'excerpt' => $product->excerpt,
            'category_id' => $product->category_id,
            'metal_type' => 'gold',
            'target_group' => 'unisex',
            'stock_status' => 'IN_STOCK',
            'status' => 1,
            'addon' => $product->addon ?? 0,
            'labor_charge_1' => $product->labor_charge_1 ?? 15,
            'profit' => $product->profit ?? 7,
            'tax' => $product->tax ?? 9,
            'stock_quantity' => $product->stock_quantity ?? 0,
            'weight' => $product->weight ?? 0,
            'cat' => [],
            'tags' => '',
            'stock_items' => json_encode([
                ['weight' => 1.0, 'count' => 1],
                ['weight' => 1.1, 'count' => 1],
                ['weight' => 1.2, 'count' => 1],
                ['weight' => 1.3, 'count' => 1],
            ]),
        ]);

        $response->assertRedirect();

        $codes = $product->fresh()->quantities()->orderBy('id')->pluck('code')->all();

        $this->assertSame([
            $sku.'-0001',
            $sku.'-0002',
            $sku.'-0003',
            $sku.'-0004',
        ], $codes);
    }

    public function test_stock_editor_shows_sku_before_weight_and_collapses_price_breakdown(): void
    {
        $vue = file_get_contents(base_path('resources/js/components/StockItemsInput.vue'));

        $this->assertNotFalse($vue);
        $this->assertTrue(strpos($vue, '{{ skuLabel }}') < strpos($vue, '{{ weightLabel }}'));
        $this->assertStringContainsString('breakdownOpen: false', $vue);
        $this->assertStringContainsString('v-show="item.breakdownOpen"', $vue);
        $this->assertStringContainsString('breakdownTitle', $vue);
        $this->assertStringNotContainsString('livePriceLabel', $vue);
        $this->assertStringContainsString('this.items.unshift(item)', $vue);
        $this->assertStringContainsString('stock-toolbar', $vue);
        $this->assertStringContainsString('stock-list', $vue);
        $this->assertStringNotContainsString('this.items.push(item)', $vue);
        $this->assertStringContainsString('label: `نرخ روز ${this.metalName}`', $vue);
        $this->assertStringContainsString('label: `حداقل درصد سود ${this.formatPercent(minimumPercent)}`', $vue);

        $blade = file_get_contents(resource_path('views/admin/products/sub-pages/product-step-stock.blade.php'));
        $this->assertNotFalse($blade);
        $this->assertTrue(strpos($blade, 'id="stock_quantity"') < strpos($blade, 'stock-items-input'));
    }

    public function test_stock_editor_passes_market_prices_and_minimum_percent_separately(): void
    {
        $product = $this->makeProduct(['metal_type' => 'gold']);

        $html = view('admin.products.sub-pages.product-step-stock', [
            'item' => $product,
        ])->render();

        $this->assertStringContainsString(':gold-price="2000000"', $html);
        $this->assertStringContainsString(':silver-price="80000"', $html);
        $this->assertStringContainsString(':minimum-percent="105"', $html);
    }

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
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
