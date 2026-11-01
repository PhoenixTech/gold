<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_loads_with_empty_query(): void
    {
        $response = $this->get(route('client.search'));

        $response->assertStatus(200);
        $response->assertViewIs('client.search');
        $response->assertSee(__('Search'));
        $response->assertSee(__('Search products, articles, categories...'));
    }

    public function test_search_page_loads_with_short_query_without_aborting_403(): void
    {
        $response = $this->get(route('client.search', ['q' => 'a']));

        $response->assertStatus(200);
        $response->assertViewIs('client.search');
        $response->assertSee(__('Search word is too short'));
    }

    public function test_search_page_displays_results_and_accessible_tabs(): void
    {
        Category::factory()->create();
        Product::factory()->create([
            'name' => 'Golden Ring',
            'status' => 1,
        ]);

        $response = $this->get(route('client.search', ['q' => 'Golden Ring']));

        $response->assertStatus(200);
        $response->assertViewIs('client.search');
        $response->assertSee('Golden Ring');
        $response->assertSee(__('Products'));
        $response->assertSee(__('Posts'));
        $response->assertSee(__('Video clips'));
        $response->assertSee('role="tablist"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('role="tabpanel"', false);
    }
}
