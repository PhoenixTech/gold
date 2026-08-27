<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMenuTest extends TestCase
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

    public function test_dashboard_sidebar_groups_shop_items_together(): void
    {
        $this->actingAsAdmin();
        App::setLocale('en');

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString('id="shop"', $html);
        $this->assertStringContainsString(__('Shop'), $html);
        $this->assertStringContainsString(__('Products'), $html);
        $this->assertStringContainsString(__('Categories'), $html);
        $this->assertStringContainsString(__('Invoices'), $html);
        $this->assertStringContainsString(__('Bank accounts'), $html);
        $this->assertStringContainsString(__('Transports'), $html);
        $this->assertStringContainsString(route('admin.product.index'), $html);
        $this->assertStringContainsString(route('admin.invoice.index'), $html);
        $this->assertStringContainsString(route('admin.bank-account.index'), $html);
        $this->assertStringContainsString(route('admin.transport.index'), $html);
    }

    public function test_dashboard_sidebar_uses_clear_group_names(): void
    {
        $this->actingAsAdmin();
        App::setLocale('en');

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString(__('View Website'), $html);
        $this->assertStringContainsString(__('Website content'), $html);
        $this->assertStringContainsString(__('Appearance'), $html);
        $this->assertStringContainsString(__('Customer support'), $html);
        $this->assertStringContainsString(__('Shop visits'), $html);
        $this->assertStringContainsString(route('admin.shop-visit.index'), $html);
        $this->assertStringContainsString(__('Staff and logs'), $html);
        $this->assertStringContainsString(__('Settings'), $html);
        $this->assertStringContainsString(__('Help'), $html);
        $this->assertGreaterThan(
            strpos($html, route('admin.setting.index')),
            strpos($html, route('admin.help'))
        );

        $this->assertStringNotContainsString(__('Shopping card'), $html);
        $this->assertStringNotContainsString(__('Catalog'), $html);
        $this->assertStringNotContainsString('xShop', $html);
        $this->assertStringNotContainsString(__('Managing'), $html);
        $this->assertStringNotContainsString(__('Interaction'), $html);
        $this->assertStringNotContainsString(__('Theme'), $html);
        $this->assertStringNotContainsString(__('Reports'), $html);
    }

    public function test_dashboard_sidebar_uses_persian_labels_for_the_shop(): void
    {
        $this->actingAsAdmin();
        App::setLocale('fa');

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString('فروشگاه', $html);
        $this->assertStringContainsString('محصولات', $html);
        $this->assertStringContainsString('صورت‌حساب‌ها', $html);
        $this->assertStringContainsString('حساب‌های بانکی', $html);
        $this->assertStringContainsString('روش‌های ارسال', $html);
        $this->assertStringContainsString('مشاهده وب‌سایت', $html);
        $this->assertStringContainsString('محتوای سایت', $html);
        $this->assertStringContainsString('پشتیبانی', $html);
        $this->assertStringContainsString('ویزیت فروشگاه‌ها', $html);
        $this->assertStringContainsString('راهنما', $html);
        $this->assertStringNotContainsString('سبد خرید', $html);
    }
}
