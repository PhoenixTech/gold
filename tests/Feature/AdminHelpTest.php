<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminHelpTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function actingAsCourier(): User
    {
        Role::findOrCreate('courier', 'web');
        $courier = User::factory()->courier()->create();
        $courier->assignRole('courier');
        $this->actingAs($courier);

        return $courier;
    }

    public function test_guests_are_redirected_from_help(): void
    {
        $this->get(route('admin.help'))->assertRedirect();
    }

    public function test_admin_sidebar_places_help_below_settings(): void
    {
        $this->actingAsAdmin();
        App::setLocale('en');

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString(__('Help'), $html);
        $this->assertStringContainsString(route('admin.help'), $html);

        $settingsPos = strpos($html, route('admin.setting.index'));
        $helpPos = strpos($html, route('admin.help'));

        $this->assertNotFalse($settingsPos);
        $this->assertNotFalse($helpPos);
        $this->assertGreaterThan($settingsPos, $helpPos);
    }

    public function test_courier_sidebar_does_not_include_help(): void
    {
        $this->actingAsCourier();

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringNotContainsString(route('admin.help'), $html);
        $this->assertStringNotContainsString(__('Help'), $html);
    }

    public function test_courier_is_redirected_away_from_help(): void
    {
        $this->actingAsCourier();

        $this->get(route('admin.help'))
            ->assertRedirect(route('admin.delivery.index'));
    }

    public function test_help_opens_the_delivery_guide_as_the_first_topic(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help'))
            ->assertOk()
            ->assertSee(route('admin.help', ['topic' => 'delivery']), false)
            ->assertSee(route('admin.help', ['topic' => 'gold-price']), false)
            ->assertSee(route('admin.help', ['topic' => 'checkout']), false)
            ->assertSee(route('admin.help', ['topic' => 'shop-settings']), false)
            ->assertSee(route('admin.help', ['topic' => 'sku-generation']), false);
    }

    public function test_clicking_the_delivery_topic_opens_the_guide(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'delivery']))
            ->assertOk();
    }

    public function test_gold_price_topic_explains_the_formula(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'gold-price']))
            ->assertOk();
    }

    public function test_checkout_topic_explains_the_customer_flow(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'checkout']))
            ->assertOk();
    }

    public function test_shop_settings_topic_covers_gold_checkout_and_bank_card(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'shop-settings']))
            ->assertOk();
    }

    public function test_sku_generation_topic_explains_the_structure(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'sku-generation']))
            ->assertOk();
    }

    public function test_order_flow_topic_shows_the_steps_statuses_and_refund_rules(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'order-flow']))
            ->assertOk();
    }

    public function test_unknown_help_topic_returns_not_found(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.help', ['topic' => 'missing']))
            ->assertNotFound();
    }

    public function test_visitor_is_redirected_away_from_help(): void
    {
        Role::findOrCreate('visitor', 'web');
        $visitor = User::factory()->visitor()->create();
        $visitor->assignRole('visitor');
        $this->actingAs($visitor);

        $this->get(route('admin.help'))->assertRedirect(route('admin.home'));
    }
}
