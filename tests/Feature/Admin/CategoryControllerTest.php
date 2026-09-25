<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
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

    public function test_admin_can_view_category_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.category.index'));

        $response->assertOk();
        $response->assertViewIs('admin.categories.category-list');
        $response->assertViewHas('items');
        $response->assertViewHas('cols');
    }

    public function test_admin_can_view_create_category_form(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.category.create'));

        $response->assertOk();
        $response->assertViewIs('admin.categories.category-form');
        $response->assertViewHas('cats');
    }

    public function test_admin_can_store_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.category.store'), [
            'name' => 'New Special Category',
            'code' => 'NSC',
            'subtitle' => 'Special items',
            'description' => 'A description',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'code' => 'NSC',
        ]);

        $cat = Category::where('code', 'NSC')->first();
        $this->assertNotNull($cat);
        $this->assertEquals('new-special-category', $cat->slug);
    }

    public function test_admin_can_store_category_via_ajax(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('admin.category.store'), [
            'name' => 'Ajax Category',
            'code' => 'AJX',
        ]);

        $response->assertOk();
        $response->assertJson([
            'OK' => true,
        ]);
        $response->assertJsonStructure(['OK', 'message', 'id', 'data', 'url']);
    }

    public function test_admin_can_edit_category(): void
    {
        $this->actingAsAdmin();

        $cat = Category::create(['name' => 'Editable Cat', 'slug' => 'editable-cat-'.uniqid()]);

        $response = $this->get(route('admin.category.edit', $cat->id));

        $response->assertOk();
        $response->assertViewIs('admin.categories.category-form');
        $response->assertViewHas('item');
    }

    public function test_admin_can_update_category(): void
    {
        $this->actingAsAdmin();

        $cat = Category::create(['name' => 'Old Title', 'slug' => 'old-title-'.uniqid()]);

        $response = $this->post(route('admin.category.update', $cat->id), [
            'name' => 'Updated Title',
            'subtitle' => 'Updated Subtitle',
        ]);

        $response->assertRedirect();
        $cat->refresh();
        $this->assertEquals('Updated Title', $cat->name);
    }

    public function test_admin_can_delete_and_restore_category(): void
    {
        $this->actingAsAdmin();

        $cat = Category::create(['name' => 'To Delete', 'slug' => 'to-delete-'.uniqid()]);

        $responseDelete = $this->get(route('admin.category.destroy', $cat->id));
        $responseDelete->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $cat->id]);

        $responseRestore = $this->get(route('admin.category.restore', $cat->id));
        $responseRestore->assertRedirect();
        $this->assertNotSoftDeleted('categories', ['id' => $cat->id]);
    }

    public function test_admin_can_bulk_delete_and_restore_categories(): void
    {
        $this->actingAsAdmin();

        $cat1 = Category::create(['name' => 'Bulk 1', 'slug' => 'bulk-1-'.uniqid()]);
        $cat2 = Category::create(['name' => 'Bulk 2', 'slug' => 'bulk-2-'.uniqid()]);

        $responseBulkDelete = $this->post(route('admin.category.bulk'), [
            'action' => 'delete',
            'id' => [$cat1->id, $cat2->id],
        ]);
        $responseBulkDelete->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('categories', ['id' => $cat2->id]);

        $responseBulkRestore = $this->post(route('admin.category.bulk'), [
            'action' => 'restore',
            'id' => [$cat1->id, $cat2->id],
        ]);
        $responseBulkRestore->assertRedirect();
        $this->assertNotSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertNotSoftDeleted('categories', ['id' => $cat2->id]);
    }

    public function test_admin_can_view_and_save_sort(): void
    {
        $this->actingAsAdmin();

        $cat1 = Category::create(['name' => 'Sort 1', 'slug' => 'sort-1-'.uniqid(), 'sort' => 0]);
        $cat2 = Category::create(['name' => 'Sort 2', 'slug' => 'sort-2-'.uniqid(), 'sort' => 1]);

        $responseView = $this->get(route('admin.category.sort'));
        $responseView->assertOk();
        $responseView->assertViewIs('admin.commons.sort');

        $responseSave = $this->postJson(route('admin.category.sort-save'), [
            'items' => [
                ['id' => $cat2->id, 'parentId' => null],
                ['id' => $cat1->id, 'parentId' => null],
            ],
        ]);

        $responseSave->assertOk();
        $this->assertEquals(0, $cat2->fresh()->sort);
        $this->assertEquals(1, $cat1->fresh()->sort);
    }
}
