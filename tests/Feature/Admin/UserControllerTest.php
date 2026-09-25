<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_view_user_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.user.index'));

        $response->assertOk();
        $response->assertViewIs('admin.users.user-list');
        $response->assertViewHas('items');
        $response->assertViewHas('cols');
    }

    public function test_admin_can_edit_user(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create(['role' => 'USER']);

        $response = $this->get(route('admin.user.edit', $target->email));

        $response->assertOk();
        $response->assertViewIs('admin.users.user-form');
        $response->assertViewHas('item');
        $response->assertViewHas('routes');
    }

    public function test_admin_can_update_user(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create(['name' => 'Old Name', 'role' => 'USER']);

        $response = $this->post(route('admin.user.update', $target->email), [
            'name' => 'New Name',
            'email' => $target->email,
            'mobile' => '09121112233',
            'role' => 'USER',
        ]);

        $response->assertRedirect();
        $target->refresh();
        $this->assertEquals('New Name', $target->name);
    }

    public function test_admin_can_delete_and_restore_user(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create(['role' => 'USER']);

        $responseDelete = $this->get(route('admin.user.destroy', $target->email));
        $responseDelete->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $responseRestore = $this->get(route('admin.user.restore', $target->email));
        $responseRestore->assertRedirect();
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_admin_can_bulk_delete_and_change_role(): void
    {
        $this->actingAsAdmin();

        $u1 = User::factory()->create(['role' => 'USER']);
        $u2 = User::factory()->create(['role' => 'USER']);

        $responseRole = $this->post(route('admin.user.bulk'), [
            'action' => 'role.ADMIN',
            'id' => [$u1->id, $u2->id],
        ]);
        $responseRole->assertRedirect();
        $this->assertEquals('ADMIN', $u1->fresh()->role);
        $this->assertEquals('ADMIN', $u2->fresh()->role);

        $responseDelete = $this->post(route('admin.user.bulk'), [
            'action' => 'delete',
            'id' => [$u1->id, $u2->id],
        ]);
        $responseDelete->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $u1->id]);
        $this->assertSoftDeleted('users', ['id' => $u2->id]);
    }
}
