<?php

namespace Tests\Feature;

use App\Http\Controllers\XController;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TestDummyController extends XController
{
    protected $_MODEL_ = Category::class;

    protected $cols = ['name'];

    protected $extra_cols = ['id', 'slug'];

    protected $searchable = ['name'];

    protected $listView = 'admin.categories.category-list';

    protected $formView = 'admin.categories.category-form';

    public function save($item, $request)
    {
        $item->name = $request->input('name', 'Test Dummy');
        $item->slug = $this->getSlug($item, 'slug', 'name');
        $item->save();

        return $item;
    }
}

class XControllerTest extends TestCase
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

    public function test_slug_generator_creates_unique_slugs(): void
    {
        $controller = new TestDummyController;

        Category::create([
            'name' => 'Gold Coin',
            'slug' => 'gold-coin',
        ]);

        $slug1 = $controller->createUniqueSlug('gold-coin');
        $this->assertEquals('gold-coin-1', $slug1);

        Category::create([
            'name' => 'Gold Coin 1',
            'slug' => 'gold-coin-1',
        ]);

        $slug2 = $controller->createUniqueSlug('gold-coin');
        $this->assertEquals('gold-coin-2', $slug2);
    }

    public function test_dynamic_fallback_handles_delete_and_restore(): void
    {
        $this->actingAsAdmin();

        $cat = Category::create([
            'name' => 'Rings',
            'slug' => 'rings',
        ]);

        $controller = new TestDummyController;

        // Test destroy via fallback
        $response = $controller->destroy($cat->id);
        $this->assertTrue($response->isRedirection());
        $this->assertSoftDeleted('categories', ['id' => $cat->id]);

        // Test restore via fallback
        $responseRestore = $controller->restore($cat->id);
        $this->assertTrue($responseRestore->isRedirection());
        $this->assertNotSoftDeleted('categories', ['id' => $cat->id]);
    }

    public function test_default_bulk_action_deletes_and_restores(): void
    {
        $this->actingAsAdmin();

        $cat1 = Category::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
        $cat2 = Category::create(['name' => 'Cat 2', 'slug' => 'cat-2']);

        $controller = new TestDummyController;

        // Bulk delete
        $request = Request::create('/admin/categories/bulk', 'POST', [
            'action' => 'delete',
            'id' => [$cat1->id, $cat2->id],
        ]);

        $response = $controller->bulk($request);
        $this->assertTrue($response->isRedirection());
        $this->assertSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('categories', ['id' => $cat2->id]);

        // Bulk restore
        $requestRestore = Request::create('/admin/categories/bulk', 'POST', [
            'action' => 'restore',
            'id' => [$cat1->id, $cat2->id],
        ]);

        $responseRestore = $controller->bulk($requestRestore);
        $this->assertTrue($responseRestore->isRedirection());
        $this->assertNotSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertNotSoftDeleted('categories', ['id' => $cat2->id]);
    }
}
