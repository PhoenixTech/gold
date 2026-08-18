<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected ProductPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(ProductPriceCalculator::class);
        $this->seedMetalSettings();
    }

    public function test_calculate_from_parts_matches_legacy_structure(): void
    {
        $price = $this->calculator->calculateFromParts(
            metalPrice: 1_000_000,
            weight: 2,
            feePercent: 15,
            profitRate: 0.07,
            taxRate: 0.09,
            addon: 5000,
        );

        $this->assertSame(2_507_000, $price);
    }

    public function test_calculate_uses_product_labor_profit_tax_and_addon(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 10,
            'wage' => 99,
            'profit' => 5,
            'tax' => 10,
            'addon' => 1000,
        ]);

        $expected = $this->calculator->calculateFromParts(
            (int) Setting::where('key', 'gold')->first()->value,
            1.5,
            10,
            0.05,
            0.10,
            1000,
        );

        $this->assertSame($expected, $this->calculator->calculate($product, 1.5));
        $this->assertNotSame(
            $this->calculator->calculateFromParts(
                (int) Setting::where('key', 'gold')->first()->value,
                1.5,
                99,
                0.07,
                0.09,
                1000,
            ),
            $this->calculator->calculate($product, 1.5)
        );
    }

    public function test_silver_products_use_silver_setting(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'silver',
            'labor_charge_1' => 10,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
        ]);

        $silver = (int) Setting::where('key', 'silver')->first()->value;
        $gold = (int) Setting::where('key', 'gold')->first()->value;

        $this->assertSame(
            $this->calculator->calculateFromParts($silver, 2, 10, 0.07, 0.09, 0),
            $this->calculator->calculate($product, 2)
        );
        $this->assertNotSame(
            $this->calculator->calculateFromParts($gold, 2, 10, 0.07, 0.09, 0),
            $this->calculator->calculate($product, 2)
        );
    }

    public function test_reprice_updates_each_piece_from_its_weight(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 15,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
            'status' => 1,
        ]);

        $light = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1,
            'count' => 1,
            'price' => 1,
            'code' => 'L-'.uniqid(),
        ]);
        $heavy = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 3,
            'count' => 1,
            'price' => 1,
            'code' => 'H-'.uniqid(),
        ]);

        $this->calculator->repriceProduct($product->fresh(['quantities']));

        $light->refresh();
        $heavy->refresh();
        $product->refresh();

        $this->assertSame($this->calculator->calculate($product, 1), $light->price);
        $this->assertSame($this->calculator->calculate($product, 3), $heavy->price);
        $this->assertSame($light->price, $product->price);
        $this->assertSame(2, $product->stock_quantity);
    }

    public function test_sold_piece_is_excluded_from_product_min_price(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 15,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
            'status' => 1,
        ]);

        $cheap = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1,
            'count' => 1,
            'code' => 'C-'.uniqid(),
        ]);
        $expensive = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 4,
            'count' => 1,
            'code' => 'E-'.uniqid(),
        ]);

        $this->calculator->repriceProduct($product->fresh(['quantities']));
        $cheap->refresh();
        $expensive->refresh();

        $cheap->markSold();
        $this->calculator->syncProductAggregates($product->fresh());

        $product->refresh();

        $this->assertSame(0, $cheap->fresh()->count);
        $this->assertSame($expensive->price, $product->price);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame($expensive->price, $product->lowestAvailablePrice());
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
