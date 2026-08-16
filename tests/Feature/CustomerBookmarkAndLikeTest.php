<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookmarkAndLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_bookmark_and_unbookmark_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        // Toggle bookmark (add)
        $this->actingAs($customer, 'customer')
            ->getJson(route('client.product-bookmark-toggle', $product->slug))
            ->assertStatus(200)
            ->assertJson([
                'OK' => true,
                'data' => '1',
            ]);

        $this->assertTrue($customer->bookmarks()->where('product_id', $product->id)->exists());
        $this->assertEquals(1, $product->isBookmarked());

        // Toggle bookmark again (remove)
        $this->actingAs($customer, 'customer')
            ->getJson(route('client.product-bookmark-toggle', $product->slug))
            ->assertStatus(200)
            ->assertJson([
                'OK' => true,
                'data' => '0',
            ]);

        $this->assertFalse($customer->bookmarks()->where('product_id', $product->id)->exists());
        $this->assertEquals(0, $product->isBookmarked());
    }

    public function test_customer_can_like_and_unlike_product(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        // Toggle favorite (add)
        $this->actingAs($customer, 'customer')
            ->getJson(route('client.product-fav-toggle', $product->slug))
            ->assertStatus(200)
            ->assertJson([
                'OK' => true,
                'data' => '1',
            ]);

        $this->assertTrue($customer->favorites()->where('product_id', $product->id)->exists());

        // Toggle favorite (remove)
        $this->actingAs($customer, 'customer')
            ->getJson(route('client.product-fav-toggle', $product->slug))
            ->assertStatus(200)
            ->assertJson([
                'OK' => true,
                'data' => '0',
            ]);

        $this->assertFalse($customer->favorites()->where('product_id', $product->id)->exists());
    }

    public function test_guest_can_submit_product_comment(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(route('client.comment.submit'), [
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test comment for product.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'OK' => true,
            ]);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'body' => 'This is a test comment for product.',
        ]);
    }

    public function test_customer_can_submit_product_comment(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('client.comment.submit'), [
                'commentable_type' => Product::class,
                'commentable_id' => $product->id,
                'message' => 'Customer review on product.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'OK' => true,
            ]);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'commentator_type' => Customer::class,
            'commentator_id' => $customer->id,
            'body' => 'Customer review on product.',
        ]);
    }
}
