<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_access_product_list(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $response = $this->get(route('admin.product.index'));
        $response->assertOk();
    }

    public function test_admin_can_create_product(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $response = $this->post(route('admin.product.store'), [
            'name' => 'Brand New Gold Bangle',
            'category_id' => $category->id,
            'excerpt' => 'A luxury gold bangle for women',
            'desc' => 'Detailed product description goes here',
            'metal_type' => 'gold',
            'target_group' => 'women',
            'price' => 15000000,
            'weight' => 5.250,
            'status' => 1,
        ]);

        $response->assertRedirect();
        $product = Product::latest('id')->first();
        $this->assertNotNull($product);
        $this->assertSame('Brand New Gold Bangle', $product->name);
        $this->assertEquals($category->id, $product->category_id);
        $this->assertSame('gold', $product->metal_type);
    }

    public function test_admin_can_soft_delete_and_restore_product(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'name' => 'Temporary Product Item',
            'slug' => 'temp-prod-'.uniqid(),
            'sku' => 'SKU-TEMP-'.uniqid(),
        ]);

        $deleteResponse = $this->get(route('admin.product.destroy', $product->slug));
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $restoreResponse = $this->get(route('admin.product.restore', $product->slug));
        $restoreResponse->assertRedirect();
        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_bulk_publish_and_draft_products(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $p1 = Product::factory()->create(['status' => 0, 'sku' => 'B-1-'.uniqid(), 'slug' => 'b-1-'.uniqid()]);
        $p2 = Product::factory()->create(['status' => 0, 'sku' => 'B-2-'.uniqid(), 'slug' => 'b-2-'.uniqid()]);

        $publishResponse = $this->post(route('admin.product.bulk'), [
            'action' => 'publish',
            'id' => [$p1->id, $p2->id],
        ]);
        $publishResponse->assertRedirect();
        $this->assertSame(1, $p1->fresh()->status);
        $this->assertSame(1, $p2->fresh()->status);

        $draftResponse = $this->post(route('admin.product.bulk'), [
            'action' => 'draft',
            'id' => [$p1->id, $p2->id],
        ]);
        $draftResponse->assertRedirect();
        $this->assertSame(0, $p1->fresh()->status);
        $this->assertSame(0, $p2->fresh()->status);
    }
}
