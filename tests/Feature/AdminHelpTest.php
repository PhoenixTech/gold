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
        App::setLocale('en');

        $this->get(route('admin.help'))
            ->assertOk()
            ->assertSee(__('Help topics'), false)
            ->assertSee(__('How motorcycle delivery works'), false)
            ->assertSee(__('Enable the courier transport'), false)
            ->assertSee(__('Needs delivery confirmation code'), false)
            ->assertSee(__('Create a courier user'), false)
            ->assertSee(__('Hand a paid order to a courier'), false)
            ->assertSee(__('The courier completes the delivery'), false)
            ->assertSee(__('Safety lock:'), false)
            ->assertSee(route('admin.help', ['topic' => 'delivery']), false)
            ->assertSee(route('admin.help', ['topic' => 'gold-price']), false)
            ->assertSee(route('admin.help', ['topic' => 'checkout']), false)
            ->assertSee(route('admin.help', ['topic' => 'shop-settings']), false)
            ->assertSee(route('admin.help', ['topic' => 'sku-generation']), false)
            ->assertSee(__('How gold price is calculated'), false)
            ->assertSee(__('How customer checkout works'), false)
            ->assertSee(__('Gold, checkout, and bank card options'), false)
            ->assertSee(__('How product and piece SKU codes work'), false);
    }

    public function test_clicking_the_delivery_topic_opens_the_guide(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $this->get(route('admin.help', ['topic' => 'delivery']))
            ->assertOk()
            ->assertSee('تحویل با پیک — چگونه کار می‌کند؟', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('روش ارسال پیک را فعال کنید', false)
            ->assertSee('قفل ایمنی:', false);
    }

    public function test_gold_price_topic_explains_the_formula(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $this->get(route('admin.help', ['topic' => 'gold-price']))
            ->assertOk()
            ->assertSee('قیمت طلا چطور حساب می‌شود؟', false)
            ->assertSee('از نرخ روز فلز شروع کنید', false)
            ->assertSee('حداقل درصد سود', false)
            ->assertSee('اجرت، سود و مالیات را اضافه کنید', false)
            ->assertSee('وزن و رند کردن', false);
    }

    public function test_checkout_topic_explains_the_customer_flow(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $this->get(route('admin.help', ['topic' => 'checkout']))
            ->assertOk()
            ->assertSee('راهنمای جامع مراحل خرید مشتری و مدیریت سفارشات', false)
            ->assertSee('مرحله ۱ سبد: اقلام و تثبیت قیمت زنده طلا', false);
    }

    public function test_shop_settings_topic_covers_gold_checkout_and_bank_card(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $this->get(route('admin.help', ['topic' => 'shop-settings']))
            ->assertOk()
            ->assertSee('تنظیمات طلا، تسویه و کارت بانکی', false)
            ->assertSee('تنظیمات فروشگاه طلا و نقره', false)
            ->assertSee('نرخ بازار خودش به‌روز می‌شود', false)
            ->assertSee('یک کارت بانکی فعال', false)
            ->assertSee('حساب‌های بانکی', false);
    }

    public function test_sku_generation_topic_explains_the_structure(): void
    {
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $this->get(route('admin.help', ['topic' => 'sku-generation']))
            ->assertOk()
            ->assertSee('شناسه کالا (SKU) و کد قطعات چطور کار می‌کنند؟', false)
            ->assertSee('بخش ۱: جنسیت (حرف)', false)
            ->assertSee('بخش ۲: جنس فلز (عدد)', false)
            ->assertSee('بخش ۳: دسته‌بندی اصلی (کد)', false)
            ->assertSee('بخش ۴: شماره محصول در دسته (۴ رقم)', false)
            ->assertSee('بخش ۵: شمارنده قطعات انبار (۵ رقم)', false)
            ->assertSee('F1A0001-00005', false);
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
