<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_dashboard_sidebar_renders_shop_routes_for_admin(): void
    {
        $this->actingAsAdmin();

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString(route('admin.product.index'), $html);
        $this->assertStringContainsString(route('admin.invoice.index'), $html);
        $this->assertStringContainsString(route('admin.bank-account.index'), $html);
        $this->assertStringContainsString(route('admin.supplier.index'), $html);
        $this->assertStringContainsString(route('admin.transport.index'), $html);
        $this->assertStringContainsString(route('admin.discount.index'), $html);
        $this->assertStringContainsString(route('admin.prop.index'), $html);
        $this->assertStringContainsString(route('admin.rate.index'), $html);
        $this->assertStringContainsString(route('admin.evaluation.index'), $html);
    }

    public function test_dashboard_sidebar_renders_system_routes_for_admin(): void
    {
        $this->actingAsAdmin();

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString(route('admin.user.index'), $html);
        $this->assertStringContainsString(route('admin.adminlog.index'), $html);
        $this->assertStringContainsString(route('admin.lang.index'), $html);
        $this->assertStringContainsString(route('admin.setting.index'), $html);
        $this->assertStringContainsString(route('admin.shop-visit.index'), $html);
        $this->assertStringContainsString(route('admin.help'), $html);
    }
}
