<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Group;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientWebPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_client_index(): void
    {
        if (Category::count() == 0) {
            Category::factory(2)->create();
        }

        $response = $this->get(route('client.welcome'));

        $response->assertStatus(200);
        $response->assertSee('ZarMenu');
        $response->assertSee('WTFIndex');
        $response->assertSee('WTFFooter');
    }

    public function test_web_client_homev1(): void
    {
        if (Category::count() == 0) {
            Category::factory(2)->create();
        }

        $response = $this->get(route('client.homev1'));

        $response->assertStatus(200);
        $response->assertSee('AplMenu');
        $response->assertSee('WTFIndex');
    }

    public function test_web_client_old_home(): void
    {
        $response = $this->get(route('client.old'));

        $response->assertStatus(200);
        $response->assertSee('ZarMenu');
        $response->assertSee('WTFIndex');
    }

    public function test_web_client_posts(): void
    {
        $response = $this->get(route('client.posts'));

        $response->assertStatus(200);
    }

    public function test_web_client_products(): void
    {
        $response = $this->get(route('client.products'));

        $response->assertStatus(200);
    }

    public function test_web_client_product(): void
    {

        if (Product::count() == 0) {
            Product::factory(1)->create();
        }
        $response = $this->get(Product::first()->webUrl());
        $response->assertStatus(200);
    }

    public function test_web_client_post(): void
    {

        if (Post::count() == 0) {
            Post::factory(1)->create();
        }
        $response = $this->get(Post::first()->webUrl());
        $response->assertStatus(200);
    }

    public function test_web_client_group(): void
    {

        if (Group::count() == 0) {
            Group::factory(1)->create();
        }
        $response = $this->get(Group::first()->webUrl());
        $response->assertStatus(200);
    }

    public function test_web_client_category(): void
    {

        if (Category::count() == 0) {
            Category::factory(1)->create();
        }
        $response = $this->get(Category::first()->webUrl());
        $response->assertStatus(200);
    }

    public function test_web_client_sign_in(): void
    {
        $response = $this->get(route('client.sign-in'));
        $response->assertStatus(200);
    }

    public function test_web_client_sign_up(): void
    {
        $response = $this->get(route('client.sign-up'));
        $response->assertStatus(200);
    }

    public function test_wtf_footer_hidden_on_card_profile_sign_in_and_login(): void
    {
        if (Category::count() == 0) {
            Category::factory(2)->create();
        }

        // Welcome page should see WTFFooter
        $this->get(route('client.welcome'))
            ->assertStatus(200)
            ->assertSee('WTFFooter');

        // /card page should not see WTFFooter
        $this->get(route('client.card'))
            ->assertStatus(200)
            ->assertDontSee('WTFFooter');

        // /customer/sign-in page should not see WTFFooter
        $this->get(route('client.sign-in'))
            ->assertStatus(200)
            ->assertDontSee('WTFFooter');

        // /login page should not see WTFFooter
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertDontSee('WTFFooter');

        // /profile page should not see WTFFooter (as authenticated customer)
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer')
            ->get(route('client.profile'))
            ->assertStatus(200)
            ->assertDontSee('WTFFooter');
    }
}
