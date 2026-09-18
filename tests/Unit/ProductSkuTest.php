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
        $category = Category::factory()->create(['code' => 'A', 'name' => 'انگشتر']);
        $catId = $category->id;

        // Simulate 3 existing products in category
        for ($i = 1; $i <= 3; $i++) {
            Product::factory()->create([
                'user_id' => $this->user->id,
                'category_id' => $catId,
            ]);
        }

        $sku = Product::generateSku('women', 'gold', $catId);

        $this->assertSame('F1A0004', $sku);
    }

    public function test_generates_correct_sku_for_men_silver(): void
    {
        $category = Category::factory()->create(['code' => 'L', 'name' => 'النگو']);

        $sku = Product::generateSku('men', 'silver', $category->id);

        $this->assertSame('M2L0001', $sku);
    }

    public function test_generates_correct_sku_for_children_gold(): void
    {
        $category = Category::factory()->create(['code' => 'Gr', 'name' => 'گردنبند و آویز']);

        $sku = Product::generateSku('children', 'gold', $category->id);

        $this->assertSame('C1Gr0001', $sku);
    }

    public function test_generates_correct_sku_for_unisex_fallback(): void
    {
        $category = Category::factory()->create(['code' => 'D', 'name' => 'دستبند']);

        $sku = Product::generateSku('unisex', 'gold', $category->id);

        $this->assertSame('U1D0001', $sku);
    }

    public function test_auto_sets_sku_on_model_create_and_update(): void
    {
        $this->category->update(['code' => 'A']);

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'target_group' => 'women',
            'metal_type' => 'gold',
            'sku' => null,
        ]);

        $this->assertStringStartsWith('F1A', $product->sku);

        // Update product to men + silver
        $product->target_group = 'men';
        $product->metal_type = 'silver';
        $product->save();

        $this->assertStringStartsWith('M2A', $product->fresh()->sku);
    }
}
