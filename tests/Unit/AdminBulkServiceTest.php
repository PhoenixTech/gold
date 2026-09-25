<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use App\Services\Admin\AdminBulkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBulkServiceTest extends TestCase
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

    public function test_it_bulk_deletes_records(): void
    {
        $this->actingAsAdmin();

        $cat1 = Category::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
        $cat2 = Category::create(['name' => 'Cat 2', 'slug' => 'cat-2']);

        $service = new AdminBulkService;
        $response = $service->handle(Category::class, 'delete', [$cat1->id, $cat2->id]);

        $this->assertTrue($response->isRedirection());
        $this->assertSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('categories', ['id' => $cat2->id]);
    }

    public function test_it_bulk_restores_records(): void
    {
        $this->actingAsAdmin();

        $cat1 = Category::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
        $cat2 = Category::create(['name' => 'Cat 2', 'slug' => 'cat-2']);
        $cat1->delete();
        $cat2->delete();

        $service = new AdminBulkService;
        $response = $service->handle(Category::class, 'restore', [$cat1->id, $cat2->id]);

        $this->assertTrue($response->isRedirection());
        $this->assertNotSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertNotSoftDeleted('categories', ['id' => $cat2->id]);
    }
}
