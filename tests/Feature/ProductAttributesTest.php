<?php

namespace Tests\Feature;

use App\Http\Requests\ProductSaveRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        $this->category = Category::factory()->create(['code' => 'A', 'name' => 'انگشتر']);
    }

    public function test_product_model_casts_group_a_attributes(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold', 'rose_gold'],
            'stones' => ['diamond', 'pearl'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine', 'birthday'],
        ]);

        $fresh = $product->fresh();

        $this->assertIsArray($fresh->plating_colors);
        $this->assertEquals(['yellow_gold', 'rose_gold'], $fresh->plating_colors);

        $this->assertIsArray($fresh->stones);
        $this->assertEquals(['diamond', 'pearl'], $fresh->stones);

        $this->assertIsArray($fresh->accessories);
        $this->assertEquals(['leather_bracelet'], $fresh->accessories);

        $this->assertIsArray($fresh->occasions);
        $this->assertEquals(['valentine', 'birthday'], $fresh->occasions);
    }

    public function test_product_option_dictionaries_contain_required_items(): void
    {
        $platingOptions = Product::platingColorOptions();
        $this->assertArrayHasKey('yellow_gold', $platingOptions);
        $this->assertArrayHasKey('rose_gold', $platingOptions);
        $this->assertArrayHasKey('silver_yellow_gold_plated', $platingOptions);
        $this->assertArrayHasKey('silver_rose_gold_plated', $platingOptions);
        $this->assertArrayHasKey('silver_white_gold_plated', $platingOptions);

        $stoneOptions = Product::stoneOptions();
        $this->assertArrayHasKey('none', $stoneOptions);
        $this->assertArrayHasKey('diamond', $stoneOptions);
        $this->assertArrayHasKey('pearl', $stoneOptions);
        $this->assertArrayHasKey('crystal', $stoneOptions);
        $this->assertArrayHasKey('agate', $stoneOptions);
        $this->assertArrayHasKey('turquoise', $stoneOptions);
        $this->assertArrayHasKey('zirconium', $stoneOptions);
        $this->assertArrayHasKey('amethyst', $stoneOptions);
        $this->assertArrayHasKey('onyx', $stoneOptions);
        $this->assertArrayHasKey('opal', $stoneOptions);
        $this->assertArrayHasKey('jade', $stoneOptions);
        $this->assertArrayHasKey('lapis_lazuli', $stoneOptions);
        $this->assertArrayHasKey('quartz', $stoneOptions);
        $this->assertArrayHasKey('coral', $stoneOptions);
        $this->assertArrayHasKey('shell', $stoneOptions);

        $accessoryOptions = Product::accessoryOptions();
        $this->assertArrayHasKey('none', $accessoryOptions);
        $this->assertArrayHasKey('leather_bracelet', $accessoryOptions);

        $occasionOptions = Product::occasionOptions();
        $this->assertArrayHasKey('valentine', $occasionOptions);
        $this->assertArrayHasKey('mothers_day', $occasionOptions);
        $this->assertArrayHasKey('girls_day', $occasionOptions);
        $this->assertArrayHasKey('womens_day', $occasionOptions);
        $this->assertArrayHasKey('birthday', $occasionOptions);
        $this->assertArrayHasKey('anniversary', $occasionOptions);
        $this->assertArrayHasKey('yalda', $occasionOptions);
        $this->assertArrayHasKey('wedding', $occasionOptions);
    }

    public function test_label_resolver_methods_return_correct_translated_labels(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold', 'silver_rose_gold_plated'],
            'stones' => ['diamond', 'turquoise'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine', 'yalda'],
        ]);

        $this->assertContains(__('Yellow Gold'), $product->getPlatingColorLabels());
        $this->assertContains(__('Silver Rose Gold Plated'), $product->getPlatingColorLabels());

        $this->assertContains(__('Diamond'), $product->getStoneLabels());
        $this->assertContains(__('Turquoise'), $product->getStoneLabels());

        $this->assertContains(__('Leather Bracelet'), $product->getAccessoryLabels());

        $this->assertContains(__('Valentine'), $product->getOccasionLabels());
        $this->assertContains(__('Yalda'), $product->getOccasionLabels());
    }

    public function test_query_scopes_filter_by_group_a_attributes(): void
    {
        $p1 = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold'],
            'stones' => ['diamond'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine'],
        ]);

        $p2 = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['silver_white_gold_plated'],
            'stones' => ['ruby'],
            'accessories' => ['none'],
            'occasions' => ['wedding'],
        ]);

        $this->assertTrue(Product::withPlatingColor('yellow_gold')->where('id', $p1->id)->exists());
        $this->assertFalse(Product::withPlatingColor('yellow_gold')->where('id', $p2->id)->exists());

        $this->assertTrue(Product::withStone('diamond')->where('id', $p1->id)->exists());
        $this->assertFalse(Product::withStone('diamond')->where('id', $p2->id)->exists());

        $this->assertTrue(Product::withAccessory('leather_bracelet')->where('id', $p1->id)->exists());
        $this->assertFalse(Product::withAccessory('leather_bracelet')->where('id', $p2->id)->exists());

        $this->assertTrue(Product::withOccasion('valentine')->where('id', $p1->id)->exists());
        $this->assertFalse(Product::withOccasion('valentine')->where('id', $p2->id)->exists());
    }

    public function test_validation_accepts_valid_attributes_and_rejects_invalid(): void
    {
        $validData = [
            'name' => 'Valid Product Name',
            'excerpt' => 'Valid excerpt here',
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold', 'rose_gold'],
            'stones' => ['diamond', 'pearl'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine', 'birthday'],
        ];

        $request = new ProductSaveRequest;
        $validator = Validator::make($validData, $request->rules());
        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));

        $invalidData = array_merge($validData, [
            'plating_colors' => ['invalid_color'],
            'stones' => ['invalid_stone'],
            'occasions' => ['invalid_occasion'],
        ]);

        $invalidValidator = Validator::make($invalidData, $request->rules());
        $this->assertTrue($invalidValidator->fails());
        $this->assertArrayHasKey('plating_colors.0', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('stones.0', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('occasions.0', $invalidValidator->errors()->toArray());
    }

    public function test_admin_can_update_product_with_group_a_attributes(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'name' => 'Sample Jewelry Product',
            'excerpt' => 'Detailed product excerpt',
        ]);

        $response = $this->post(route('admin.product.update', $product), [
            'id' => $product->id,
            'name' => 'Sample Jewelry Product Updated',
            'excerpt' => 'Detailed product excerpt updated',
            'category_id' => $this->category->id,
            'target_group' => 'women',
            'metal_type' => 'gold',
            'status' => 1,
            'addon' => 0,
            'plating_colors' => ['yellow_gold', 'rose_gold'],
            'stones' => ['diamond', 'pearl'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine', 'anniversary'],
        ]);

        $response->assertSessionHasNoErrors();
        $product->refresh();

        $this->assertEquals(['yellow_gold', 'rose_gold'], $product->plating_colors);
        $this->assertEquals(['diamond', 'pearl'], $product->stones);
        $this->assertEquals(['leather_bracelet'], $product->accessories);
        $this->assertEquals(['valentine', 'anniversary'], $product->occasions);
    }

    public function test_product_resource_includes_group_a_attributes_and_labels(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold'],
            'stones' => ['diamond'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine'],
        ]);

        $resource = (new ProductResource($product))->toArray(new Request);

        $this->assertArrayHasKey('plating_colors', $resource);
        $this->assertArrayHasKey('plating_color_labels', $resource);
        $this->assertArrayHasKey('stones', $resource);
        $this->assertArrayHasKey('stone_labels', $resource);
        $this->assertArrayHasKey('accessories', $resource);
        $this->assertArrayHasKey('accessory_labels', $resource);
        $this->assertArrayHasKey('occasions', $resource);
        $this->assertArrayHasKey('occasion_labels', $resource);

        $this->assertEquals(['yellow_gold'], $resource['plating_colors']);
        $this->assertContains(__('Yellow Gold'), $resource['plating_color_labels']);
    }

    public function test_client_product_view_displays_group_a_attributes(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => 1,
            'plating_colors' => ['yellow_gold'],
            'stones' => ['diamond'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine'],
        ]);

        $response = $this->get(route('client.product', $product->slug));

        $response->assertOk();
        $response->assertSee(__('Plating Color'));
        $response->assertSee(__('Stone'));
        $response->assertSee(__('Accessory'));
        $response->assertSee(__('Occasions'));
        $response->assertSee(__('Yellow Gold'));
        $response->assertSee(__('Diamond'));
        $response->assertSee(__('Leather Bracelet'));
        $response->assertSee(__('Valentine'));
    }

    public function test_none_option_for_stone_and_accessory(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'stones' => ['none'],
            'accessories' => ['none'],
        ]);

        $this->assertEquals(['none'], $product->stones);
        $this->assertEquals(['none'], $product->accessories);
        $this->assertEquals([__('None')], $product->getStoneLabels());
        $this->assertEquals([__('None')], $product->getAccessoryLabels());
    }

    public function test_admin_can_clear_all_group_a_attributes(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'plating_colors' => ['yellow_gold'],
            'stones' => ['diamond'],
            'accessories' => ['leather_bracelet'],
            'occasions' => ['valentine'],
        ]);

        $response = $this->post(route('admin.product.update', $product), [
            'id' => $product->id,
            'name' => $product->name,
            'excerpt' => $product->excerpt,
            'category_id' => $this->category->id,
            'status' => 1,
            'addon' => 0,
            // Omit plating_colors, stones, accessories, occasions to simulate unchecking all
        ]);

        $response->assertSessionHasNoErrors();
        $product->refresh();

        $this->assertEmpty($product->plating_colors);
        $this->assertEmpty($product->stones);
        $this->assertEmpty($product->accessories);
        $this->assertEmpty($product->occasions);
    }
}
