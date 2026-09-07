<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminStockInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
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

    public function test_admin_can_view_stock_inventory_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.stock.index'));

        $response->assertOk();
        $response->assertSee(__('Stock inventory'));
        $response->assertSee(__('Total stock count'));
        $response->assertSee(__('Total stock weight'));
        $response->assertSee(__('In-stock products'));
        $response->assertSee(__('Total inventory value'));
    }

    public function test_stock_inventory_only_displays_products_with_stock(): void
    {
        $this->actingAsAdmin();

        $inStockProduct = $this->makeProduct([
            'name' => 'In Stock Gold Necklace',
            'stock_quantity' => 5,
            'metal_type' => 'gold',
            'weight' => 12.5,
            'price' => 25000000,
        ]);

        $outOfStockProduct = $this->makeProduct([
            'name' => 'Out of Stock Silver Ring',
            'stock_quantity' => 0,
            'metal_type' => 'silver',
            'weight' => 4.0,
            'price' => 500000,
        ]);

        $response = $this->get(route('admin.stock.index'));

        $response->assertOk();
        $response->assertSee('In Stock Gold Necklace');
        $response->assertDontSee('Out of Stock Silver Ring');
    }

    public function test_stock_inventory_filters_by_metal_type(): void
    {
        $this->actingAsAdmin();

        $goldProduct = $this->makeProduct([
            'name' => 'Gold Bracelet 18K',
            'stock_quantity' => 2,
            'metal_type' => 'gold',
        ]);

        $silverProduct = $this->makeProduct([
            'name' => 'Silver Earring 925',
            'stock_quantity' => 4,
            'metal_type' => 'silver',
        ]);

        $response = $this->get(route('admin.stock.index', [
            'filter' => ['metal_type' => 'gold'],
        ]));

        $response->assertOk();
        $response->assertSee('Gold Bracelet 18K');
        $response->assertDontSee('Silver Earring 925');
    }

    public function test_stock_inventory_action_routes_redirect_to_product_routes(): void
    {
        $this->actingAsAdmin();

        $product = $this->makeProduct([
            'stock_quantity' => 3,
        ]);

        $editResponse = $this->get(route('admin.stock.edit', $product->slug));
        $editResponse->assertRedirect(route('admin.product.edit', $product->slug));

        $showResponse = $this->get(route('admin.stock.show', $product->slug));
        $showResponse->assertRedirect(route('admin.product.show', $product->slug));
    }

    public function test_stock_inventory_displays_total_weight_total_price_and_no_category(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create(['name' => 'SpecialCategoryName123']);

        $product = $this->makeProduct([
            'name' => 'Diamond Ring Gold',
            'category_id' => $category->id,
            'stock_quantity' => 3,
            'weight' => 2.500,
            'price' => 10000000,
            'metal_type' => 'gold',
        ]);

        $response = $this->get(route('admin.stock.index'));

        $response->assertOk();
        // Check column headers
        $response->assertSee(__('stock_quantity'));
        $response->assertSee(__('total_weight'));
        $response->assertSee(__('total_price'));

        // Check values in row:
        // Total weight should be 3 * 2.5 = 7.500 g
        $response->assertSee('7.5');
        // Total price should be 3 * 10,000,000 = 30,000,000
        $response->assertSee(number_format(30000000));
        // Count should show pieces unit
        $response->assertSee(__('pieces'));

        // Category name should not appear as a table cell column
        // (verify cols does not have category_id or weight)
        $cols = $response->viewData('cols');
        $this->assertNotContains('category_id', $cols);
        $this->assertNotContains('weight', $cols);
        $this->assertContains('stock_quantity', $cols);
        $this->assertContains('total_weight', $cols);
        $this->assertContains('total_price', $cols);
    }

    public function test_stock_inventory_aggregates_multiple_piece_quantities_correctly(): void
    {
        $this->actingAsAdmin();

        $product = $this->makeProduct([
            'name' => 'Custom Piece Necklace',
            'stock_quantity' => 3,
            'weight' => 1.0,
            'price' => 1000000,
            'metal_type' => 'gold',
        ]);

        \App\Models\Quantity::factory()->create([
            'product_id' => $product->id,
            'count' => 1,
            'weight' => 4.250,
            'price' => 12000000,
        ]);

        \App\Models\Quantity::factory()->create([
            'product_id' => $product->id,
            'count' => 2,
            'weight' => 5.000,
            'price' => 15000000,
        ]);

        // Total weight should be (4.250 * 1) + (5.000 * 2) = 14.250 g
        $this->assertEquals(14.25, $product->totalStockWeight());
        // Total price should be (12,000,000 * 1) + (15,000,000 * 2) = 42,000,000 Toman
        $this->assertEquals(42000000, $product->totalStockPrice());

        $response = $this->get(route('admin.stock.index'));
        $response->assertOk();
        $response->assertSee('14.25');
        $response->assertSee(number_format(42000000));
    }

    public function test_stock_inventory_supports_sorting_by_total_weight_and_total_price(): void
    {
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Product A',
            'stock_quantity' => 2,
            'weight' => 5.0,
            'price' => 1000000,
        ]);

        $this->makeProduct([
            'name' => 'Product B',
            'stock_quantity' => 1,
            'weight' => 20.0,
            'price' => 5000000,
        ]);

        $weightSortAsc = $this->get(route('admin.stock.index', ['sort' => 'total_weight', 'sortType' => 'asc']));
        $weightSortAsc->assertOk();

        $priceSortDesc = $this->get(route('admin.stock.index', ['sort' => 'total_price', 'sortType' => 'desc']));
        $priceSortDesc->assertOk();
    }
}

