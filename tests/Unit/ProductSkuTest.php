<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_generates_correct_sku_format_for_female_gold_fourth_product(): void
    {
        $catId = 1;

        // Simulate 3 existing products in category 1
        for ($i = 1; $i <= 3; $i++) {
            Product::factory()->create([
                'user_id' => $this->user->id,
                'category_id' => $catId,
            ]);
        }

        $sku = Product::generateSku('women', 'gold', $catId);

        $this->assertSame('FG010004', $sku);
    }

    public function test_generates_correct_sku_for_men_silver(): void
    {
        $catId = 5;

        $sku = Product::generateSku('men', 'silver', $catId);

        $this->assertSame('MS050001', $sku);
    }

    public function test_generates_correct_sku_for_children_gold(): void
    {
        $catId = 12;

        $sku = Product::generateSku('children', 'gold', $catId);

        $this->assertSame('CG120001', $sku);
    }

    public function test_generates_correct_sku_for_unisex_fallback(): void
    {
        $catId = 3;

        $sku = Product::generateSku('unisex', 'gold', $catId);

        $this->assertSame('UG030001', $sku);
    }

    public function test_auto_sets_sku_on_model_create_and_update(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'target_group' => 'women',
            'metal_type' => 'gold',
            'sku' => null,
        ]);

        $catPad = sprintf('%02d', $this->category->id);
        $this->assertStringStartsWith("FG{$catPad}", $product->sku);

        // Update product to men + silver
        $product->target_group = 'men';
        $product->metal_type = 'silver';
        $product->save();

        $this->assertStringStartsWith("MS{$catPad}", $product->fresh()->sku);
    }
}
