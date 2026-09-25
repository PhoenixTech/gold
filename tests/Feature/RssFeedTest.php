<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_rss_feed_only_includes_published_posts(): void
    {
        $publishedPost = Post::factory()->create([
            'title' => 'Special Published Post Title',
            'status' => 1,
        ]);

        $draftPost = Post::factory()->create([
            'title' => 'Secret Draft Post Title',
            'status' => 0,
        ]);

        $response = $this->get(route('rss.post'));

        $response->assertStatus(200);
        $response->assertSee('Special Published Post Title');
        $response->assertDontSee('Secret Draft Post Title');
    }

    public function test_product_rss_feed_only_includes_published_products(): void
    {
        $publishedProduct = Product::factory()->create([
            'name' => 'Special Published Product Name',
            'status' => 1,
        ]);

        $draftProduct = Product::factory()->create([
            'name' => 'Secret Draft Product Name',
            'status' => 0,
        ]);

        $response = $this->get(route('rss.product'));

        $response->assertStatus(200);
        $response->assertSee('Special Published Product Name');
        $response->assertDontSee('Secret Draft Product Name');
    }
}
