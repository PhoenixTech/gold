<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;
use Tests\TestCase;

class TagControllerTest extends TestCase
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

    public function test_admin_can_view_tags_index(): void
    {
        $this->actingAsAdmin();

        Tag::findOrCreate('GoldTag', 'products');

        $response = $this->get(route('admin.tag.index'));

        $response->assertOk();
        $response->assertViewIs('admin.tags.tag-list');
        $response->assertViewHas('items');
        $response->assertViewHas('cols');
    }

    public function test_admin_can_filter_tags_by_search(): void
    {
        $this->actingAsAdmin();

        Tag::findOrCreate('UniqueSearchTag', 'products');

        $response = $this->get(route('admin.tag.index', ['q' => 'UniqueSearchTag']));

        $response->assertOk();
        $response->assertSee('UniqueSearchTag');
    }
}
