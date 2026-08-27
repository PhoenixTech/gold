<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSaveTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('visitor', 'web');
        Role::findOrCreate('courier', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_create_a_visitor_user(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.user.store'), [
            'name' => 'Field Visitor',
            'email' => 'field-visitor@example.com',
            'mobile' => '09121234567',
            'role' => 'VISITOR',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'field-visitor@example.com',
            'role' => 'VISITOR',
        ]);
        $this->assertTrue(User::query()->where('email', 'field-visitor@example.com')->first()->hasRole('visitor'));
    }

    public function test_admin_can_create_a_visitor_user_with_translated_role_label(): void
    {
        $this->actingAsAdmin();
        App::setLocale('fa');

        $response = $this->post(route('admin.user.store'), [
            'name' => 'ویزیتور فروشگاه',
            'email' => 'visitor-fa@example.com',
            'mobile' => '09127654321',
            'role' => __('VISITOR'),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'visitor-fa@example.com',
            'role' => 'VISITOR',
        ]);
    }

    public function test_admin_can_create_a_courier_user(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.user.store'), [
            'name' => 'پیک شمال',
            'email' => 'courier-staff@example.com',
            'mobile' => '09120001122',
            'role' => 'COURIER',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'courier-staff@example.com',
            'role' => 'COURIER',
        ]);
        $this->assertTrue(User::query()->where('email', 'courier-staff@example.com')->first()->hasRole('courier'));
    }
}
