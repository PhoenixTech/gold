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

    public function test_product_helper_identifies_below_buy_price(): void
    {
        $belowPriceProduct = new Product([
            'buy_price' => 10000000,
            'price' => 9000000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 5,
        ]);
        $this->assertTrue($belowPriceProduct->isBelowBuyPrice());
        $this->assertFalse($belowPriceProduct->canBeSold());

        $normalPriceProduct = new Product([
            'buy_price' => 10000000,
            'price' => 12000000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 5,
        ]);
        $this->assertFalse($normalPriceProduct->isBelowBuyPrice());
        $this->assertTrue($normalPriceProduct->canBeSold());

        $zeroBuyPriceProduct = new Product([
            'buy_price' => 0,
            'price' => 5000000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 5,
        ]);
        $this->assertFalse($zeroBuyPriceProduct->isBelowBuyPrice());
        $this->assertTrue($zeroBuyPriceProduct->canBeSold());
    }

    public function test_product_cannot_be_added_to_cart_when_below_buy_price(): void
    {
        $product = $this->makeProduct([
            'buy_price' => 10000000,
            'price' => 8000000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 5,
        ]);

        $response = $this->getJson(route('client.product-card-toggle', $product));

        $response->assertStatus(422);
        $response->assertJson([
            'OK' => false,
        ]);
    }

    public function test_admin_can_filter_below_buy_price_products_in_list(): void
    {
        $this->actingAsAdmin();

        $belowProduct = $this->makeProduct([
            'name' => 'Floor Breached Product',
            'buy_price' => 5000000,
            'price' => 4000000,
        ]);

        $normalProduct = $this->makeProduct([
            'name' => 'Profitable Product',
            'buy_price' => 5000000,
            'price' => 6000000,
        ]);

        $response = $this->get(route('admin.product.index', [
            'filter' => ['below_buy_price' => '1'],
        ]));

        $response->assertOk();
        $response->assertSee($belowProduct->name);
        $response->assertDontSee($normalProduct->name);
    }

    public function test_admin_product_list_displays_below_buy_price_badge(): void
    {
        $this->actingAsAdmin();

        $belowProduct = $this->makeProduct([
            'name' => 'Floor Badge Item',
            'buy_price' => 5000000,
            'price' => 3000000,
        ]);

        $response = $this->get(route('admin.product.index'));

        $response->assertOk();
        $response->assertSee(__('Below purchase price'));
    }

    public function test_dashboard_and_summary_display_below_buy_price_notice(): void
    {
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Dashboard Floor Item',
            'buy_price' => 7000000,
            'price' => 5000000,
        ]);

        $dashResponse = $this->get(route('home'));
        $dashResponse->assertOk();
        $dashResponse->assertSee(__('Price below purchase price notice'));

        $summaryResponse = $this->get(route('admin.summary.index'));
        $summaryResponse->assertOk();
        $summaryResponse->assertSee(__('Price below purchase price notice'));
    }

    public function test_quick_filters_links_are_separate_and_not_merged(): void
    {
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Sample Gold Ring',
            'metal_type' => 'gold',
            'min_stock_level' => 5,
            'stock_quantity' => 2,
            'status' => 1,
        ]);

        $response = $this->get(route('admin.product.index', [
            'filter' => ['metal_type' => 'gold'],
        ]));

        $response->assertOk();
        $content = $response->getContent();

        preg_match('/<div class="wp-quick-filters[^"]*"[^>]*>([\s\S]*?)<\/div>/u', $content, $containerMatch);
        $this->assertNotEmpty($containerMatch, 'wp-quick-filters container should exist');
        $quickFiltersHtml = $containerMatch[1];

        // Ensure Low stock link does NOT contain metal_type
        preg_match('/<a[^>]+href="([^"]+)"[^>]*>\s*'.preg_quote(__('Low stock'), '/').'/u', $quickFiltersHtml, $lowStockMatch);
        $this->assertNotEmpty($lowStockMatch, 'Low stock link should be rendered in wp-quick-filters');
        $lowStockHref = urldecode($lowStockMatch[1]);
        $this->assertStringContainsString('filter[low_stock]=1', $lowStockHref);
        $this->assertStringNotContainsString('metal_type', $lowStockHref);

        // Ensure Published link does NOT contain metal_type
        preg_match('/<a[^>]+href="([^"]+)"[^>]*>\s*'.preg_quote(__('Published'), '/').'/u', $quickFiltersHtml, $pubMatch);
        $this->assertNotEmpty($pubMatch, 'Published link should be rendered in wp-quick-filters');
        $pubHref = urldecode($pubMatch[1]);
        $this->assertStringContainsString('filter[status]=1', $pubHref);
        $this->assertStringNotContainsString('metal_type', $pubHref);

        // Ensure All link does NOT contain filter parameters
        preg_match('/<a[^>]+href="([^"]+)"[^>]*>\s*'.preg_quote(__('All'), '/').'/u', $quickFiltersHtml, $allMatch);
        $this->assertNotEmpty($allMatch, 'All link should be rendered in wp-quick-filters');
        $allHref = urldecode($allMatch[1]);
        $this->assertStringNotContainsString('filter', $allHref);
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
