<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_helper_identifies_low_stock(): void
    {
        $lowStockProduct = new Product([
            'min_stock_level' => 5,
            'stock_quantity' => 2,
        ]);
        $this->assertTrue($lowStockProduct->isLowStock());

        $normalProduct = new Product([
            'min_stock_level' => 5,
            'stock_quantity' => 5,
        ]);
        $this->assertFalse($normalProduct->isLowStock());

        $noMinProduct = new Product([
            'min_stock_level' => 0,
            'stock_quantity' => 0,
        ]);
        $this->assertFalse($noMinProduct->isLowStock());
    }

    public function test_product_step_stock_view_contains_min_stock_helper_text(): void
    {
        $product = $this->makeProduct(['min_stock_level' => 3]);

        $html = view('admin.products.sub-pages.product-step-stock', [
            'item' => $product,
            'stockItems' => [],
            'goldMarketPrice' => 2000000,
            'silverMarketPrice' => 80000,
            'minimumPercent' => 105,
        ])->render();

        $this->assertStringContainsString(__('If stock is below this number, we will notify you.'), $html);
    }

    public function test_admin_can_filter_low_stock_products_in_list(): void
    {
        $this->actingAsAdmin();

        $lowStockProduct = $this->makeProduct([
            'name' => 'Low Stock Gold Ring',
            'min_stock_level' => 5,
            'stock_quantity' => 2,
        ]);

        $normalProduct = $this->makeProduct([
            'name' => 'Normal Stock Silver Ring',
            'min_stock_level' => 5,
            'stock_quantity' => 10,
        ]);

        $response = $this->get(route('admin.product.index', [
            'filter' => ['low_stock' => '1'],
        ]));

        $response->assertOk();
        $response->assertSee($lowStockProduct->name);
        $response->assertDontSee($normalProduct->name);
    }

    public function test_admin_product_list_displays_low_stock_badge(): void
    {
        $this->actingAsAdmin();

        $lowStockProduct = $this->makeProduct([
            'name' => 'Alert Gold Bracelet',
            'min_stock_level' => 5,
            'stock_quantity' => 1,
        ]);

        $response = $this->get(route('admin.product.index'));

        $response->assertOk();
        $response->assertSee(__('Low stock'));
        $response->assertSee(__('Below minimum stock'));
    }

    public function test_dashboard_displays_low_stock_notice(): void
    {
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Low Stock Pendant',
            'min_stock_level' => 10,
            'stock_quantity' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('Low stock notice'));
        $response->assertSee(__('Low stock alert'));
    }

    public function test_summary_displays_low_stock_notice(): void
    {
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Low Stock Earring',
            'min_stock_level' => 8,
            'stock_quantity' => 3,
        ]);

        $response = $this->get(route('admin.summary.index'));

        $response->assertOk();
        $response->assertSee(__('Low stock notice'));
        $response->assertSee(__('Low stock alert'));
    }

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
}
