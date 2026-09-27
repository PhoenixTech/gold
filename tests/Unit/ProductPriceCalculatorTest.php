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
            $this->calculator->baseMetalPrice($product),
            1.5,
            10,
            0.05,
            0.10,
            1000,
        );

        $this->assertSame($expected, $this->calculator->calculate($product, 1.5));
        $this->assertNotSame(
            $this->calculator->calculateFromParts(
                $this->calculator->baseMetalPrice($product),
                1.5,
                99,
                0.07,
                0.09,
                1000,
            ),
            $this->calculator->calculate($product, 1.5)
        );
    }

    public function test_calculate_uses_sum_of_all_three_labor_charges(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 10,
            'labor_charge_2' => 3.5,
            'labor_charge_3' => 1.5,
            'profit' => 5,
            'tax' => 10,
            'addon' => 1000,
        ]);

        $expectedFee = 15.0; // 10 + 3.5 + 1.5
        $this->assertSame($expectedFee, $this->calculator->feePercent($product));

        $expected = $this->calculator->calculateFromParts(
            $this->calculator->baseMetalPrice($product),
            1.5,
            15.0,
            0.05,
            0.10,
            1000,
        );

        $this->assertSame($expected, $this->calculator->calculate($product, 1.5));
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

        $silver = $this->calculator->baseMetalPrice($product);
        $gold = $this->calculator->baseMetalPrice($product->replicate()->fill(['metal_type' => 'gold']));

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

    public function test_minimum_percent_setting_is_applied_to_the_market_price_base(): void
    {
        $goldProduct = $this->makeProduct(['metal_type' => 'gold']);
        $silverProduct = $this->makeProduct(['metal_type' => 'silver']);

        $this->assertSame(2_100_000, $this->calculator->baseMetalPrice($goldProduct));
        $this->assertSame(84_000, $this->calculator->baseMetalPrice($silverProduct));
    }

    public function test_updating_minimum_percent_setting_reprices_piece_prices(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 0,
            'wage' => 0,
            'profit' => 0,
            'tax' => 0,
            'buy_price' => 1_900_000,
            'status' => 1,
        ]);
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1,
            'count' => 1,
            'code' => 'S-'.uniqid(),
        ]);

        $this->calculator->repriceProduct($product->fresh(['quantities']));
        $quantity->refresh();
        $this->assertSame(2_100_000, $quantity->price);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        $setting = Setting::query()->where('key', 'min')->firstOrFail();
        $setting->update([
            'value' => '100',
            'raw' => '100',
        ]);
        $quantity->refresh();
        $this->assertSame(2_000_000, $quantity->price);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        $setting->update([
            'value' => '105',
            'raw' => '105',
        ]);
        $quantity->refresh();
        $this->assertSame(2_100_000, $quantity->price);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_reprice_marks_out_stock_when_price_falls_below_buy_price(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'labor_charge_1' => 0,
            'profit' => 0,
            'tax' => 0,
            'buy_price' => 2_050_000,
            'status' => 1,
        ]);
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1,
            'count' => 1,
            'code' => 'S-'.uniqid(),
        ]);

        $setting = Setting::query()->where('key', 'min')->firstOrFail();
        $setting->update([
            'value' => '100',
            'raw' => '100',
        ]);

        $this->calculator->repriceProduct($product->fresh(['quantities']));
        $quantity->refresh();
        $this->assertSame(2_000_000, $quantity->price);
        $this->assertSame('OUT_STOCK', $product->fresh()->stock_status);
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

    public function test_gold_karat_coefficients_and_ratios_match_manager_specification(): void
    {
        $expected = [
            6 => [250, '25%', '6/24'],
            8 => [333, '33.3%', '8/24'],
            9 => [375, '37.5%', '9/24'],
            10 => [417, '41.7%', '10/24'],
            12 => [500, '50%', '12/24'],
            14 => [583, '58.3%', '7/12'],
            15 => [625, '62.5%', '5/8'],
            18 => [750, '75%', '3/4'],
            20 => [833, '83.3%', '5/6'],
            21 => [875, '87.5%', '7/8'],
            22 => [916, '91.6%', '11/12'],
            24 => [999, '99.9%', '24/24'],
        ];

        foreach ($expected as $karatValue => [$coeff, $purity, $ratio]) {
            $karat = \App\Enums\GoldKarat::from($karatValue);
            $this->assertSame($coeff, $karat->coefficient());
            $this->assertSame($purity, $karat->purityPercentage());
            $this->assertSame($ratio, $karat->pureGoldRatio());
            $this->assertEqualsWithDelta($coeff / 750, $karat->ratio(), 0.00001);
        }
    }

    public function test_base_metal_price_applies_karat_ratio_for_gold_products(): void
    {
        $product18 = $this->makeProduct(['metal_type' => 'gold', 'karat' => 18]);
        $product24 = $this->makeProduct(['metal_type' => 'gold', 'karat' => 24]);
        $product14 = $this->makeProduct(['metal_type' => 'gold', 'karat' => 14]);
        $product12 = $this->makeProduct(['metal_type' => 'gold', 'karat' => 12]);
        $product9 = $this->makeProduct(['metal_type' => 'gold', 'karat' => 9]);

        $base18 = $this->calculator->baseMetalPrice($product18);
        $base24 = $this->calculator->baseMetalPrice($product24);
        $base14 = $this->calculator->baseMetalPrice($product14);
        $base12 = $this->calculator->baseMetalPrice($product12);
        $base9 = $this->calculator->baseMetalPrice($product9);

        $this->assertSame(2_100_000, $base18);
        $this->assertSame(2_797_200, $base24);
        $this->assertSame(1_632_400, $base14);
        $this->assertSame(1_400_000, $base12);
        $this->assertSame(1_050_000, $base9);
    }

    public function test_reprice_product_calculates_piece_price_based_on_product_karat(): void
    {
        $product18 = $this->makeProduct([
            'metal_type' => 'gold',
            'karat' => 18,
            'labor_charge_1' => 10,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
            'status' => 1,
        ]);
        $product24 = $this->makeProduct([
            'metal_type' => 'gold',
            'karat' => 24,
            'labor_charge_1' => 10,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
            'status' => 1,
        ]);

        $q18 = Quantity::factory()->create([
            'product_id' => $product18->id,
            'weight' => 2,
            'count' => 1,
            'code' => 'K18-'.uniqid(),
        ]);
        $q24 = Quantity::factory()->create([
            'product_id' => $product24->id,
            'weight' => 2,
            'count' => 1,
            'code' => 'K24-'.uniqid(),
        ]);

        $this->calculator->repriceProduct($product18->fresh(['quantities']));
        $this->calculator->repriceProduct($product24->fresh(['quantities']));

        $q18->refresh();
        $q24->refresh();

        $this->assertTrue($q24->price > $q18->price);
        $this->assertSame($this->calculator->calculate($product18, 2), $q18->price);
        $this->assertSame($this->calculator->calculate($product24, 2), $q24->price);
    }

    public function test_silver_product_ignores_karat(): void
    {
        $silverDefault = $this->makeProduct(['metal_type' => 'silver', 'karat' => 18]);
        $silverCustom = $this->makeProduct(['metal_type' => 'silver', 'karat' => 24]);

        $this->assertSame(84_000, $this->calculator->baseMetalPrice($silverDefault));
        $this->assertSame(84_000, $this->calculator->baseMetalPrice($silverCustom));
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
