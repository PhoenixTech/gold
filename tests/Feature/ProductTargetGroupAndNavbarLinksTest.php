<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTargetGroupAndNavbarLinksTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['code' => 'A', 'name' => 'انگشتر']);
    }

    public function test_products_filters_by_metal_and_target_group(): void
    {
        $womenGold = Product::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'انگشتر زنانه طلا',
            'metal_type' => 'gold',
            'target_group' => 'women',
            'status' => 1,
        ]);

        $menGold = Product::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'انگشتر مردانه طلا',
            'metal_type' => 'gold',
            'target_group' => 'men',
            'status' => 1,
        ]);

        $womenSilver = Product::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'انگشتر زنانه نقره',
            'metal_type' => 'silver',
            'target_group' => 'women',
            'status' => 1,
        ]);

        $response = $this->get(route('client.products', ['metal' => 'gold', 'target_group' => 'women']));

        $response->assertOk();
        $response->assertSee($womenGold->name);
        $response->assertDontSee($menGold->name);
        $response->assertDontSee($womenSilver->name);
        $response->assertSee('طلا زنانه');
    }

    public function test_legacy_category_slugs_redirect_to_filtered_products(): void
    {
        $response = $this->get(route('client.category', 'women-gold'));
        $response->assertRedirect(route('client.products', ['metal' => 'gold', 'target_group' => 'women']));

        $response = $this->get(route('client.category', 'child-gold'));
        $response->assertRedirect(route('client.products', ['metal' => 'gold', 'target_group' => 'children']));

        $response = $this->get(route('client.category', 'men-silver'));
        $response->assertRedirect(route('client.products', ['metal' => 'silver', 'target_group' => 'men']));
    }

    public function test_menu_seeder_creates_direct_filter_urls(): void
    {
        $this->seed(MenuSeeder::class);

        $menu = Menu::where('name', 'main-menu')->first();
        $this->assertNotNull($menu);

        $items = $menu->items()->orderBy('sort')->get();
        $this->assertCount(8, $items);

        $womenGoldItem = $items->firstWhere('meta', '/products?metal=gold&target_group=women');
        $this->assertNotNull($womenGoldItem);
        $this->assertSame('/products?metal=gold&target_group=women', $womenGoldItem->webUrl());

        $menGoldItem = $items->firstWhere('meta', '/products?metal=gold&target_group=men');
        $this->assertNotNull($menGoldItem);
        $this->assertSame('/products?metal=gold&target_group=men', $menGoldItem->webUrl());
    }

    public function test_sidebar_includes_metal_and_target_group_filter_links(): void
    {
        $response = $this->get(route('client.products'));

        $response->assertOk();
        $response->assertSee('نوع فلز (طلا/نقره)');
        $response->assertSee('مخاطب');
        $response->assertSee('زنانه');
        $response->assertSee('مردانه');
        $response->assertSee('اسپرت');
    }

    public function test_category_page_renders_sidebar_filters_without_route_exceptions(): void
    {
        $category = Category::where('slug', 'earrings')->first() ?? Category::factory()->create([
            'slug' => 'test-earrings',
            'code' => 'E',
            'name' => 'گوشواره',
        ]);

        $response = $this->get(route('client.category', $category->slug));

        $response->assertOk();
        $response->assertSee('گوشواره');
        $response->assertSee(route('client.category', ['category' => $category->slug, 'metal' => 'gold']));
    }

    public function test_wtffooter_renders_expected_bottom_navbar_items(): void
    {
        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('WTFFooter');
        $response->assertSee('طلا زنانه');
        $response->assertSee('طلا مردانه');
        $response->assertSee('طلا بچه‌گانه');
        $response->assertSee('هدیه طلا');
        $response->assertSee('/products?metal=gold&amp;target_group=women', false);
        $response->assertSee('/products?metal=gold&amp;target_group=men', false);
        $response->assertSee('/products?metal=gold&amp;target_group=children', false);
    }

    public function test_category_page_renders_seo_title_with_metal_and_target_group_filters(): void
    {
        $category = $this->category;

        $response = $this->get(route('client.category', ['category' => $category->slug, 'metal' => 'silver', 'target_group' => 'women']));

        $response->assertOk();
        $response->assertSee('انگشتر نقره زنانه');

        $responseGoldMen = $this->get(route('client.category', ['category' => $category->slug, 'metal' => 'gold', 'target_group' => 'men']));
        $responseGoldMen->assertOk();
        $responseGoldMen->assertSee('انگشتر طلا مردانه');

        $responseGoldOnly = $this->get(route('client.category', ['category' => $category->slug, 'metal' => 'gold']));
        $responseGoldOnly->assertOk();
        $responseGoldOnly->assertSee('انگشتر طلا');

        $responseWomenOnly = $this->get(route('client.category', ['category' => $category->slug, 'target_group' => 'women']));
        $responseWomenOnly->assertOk();
        $responseWomenOnly->assertSee('انگشتر زنانه');
    }

    public function test_products_redirects_category_query_param_to_category_route(): void
    {
        $response = $this->get(route('client.products', ['category' => $this->category->slug, 'metal' => 'silver', 'target_group' => 'women']));

        $response->assertStatus(301);
        $response->assertRedirect(route('client.category', ['category' => $this->category->slug, 'metal' => 'silver', 'target_group' => 'women']));
    }
}
