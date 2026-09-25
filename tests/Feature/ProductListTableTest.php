<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductListTableTest extends TestCase
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

    protected function makeProduct(array $attributes = []): Product
    {
        $user = User::query()->first() ?? User::factory()->create();
        $category = Category::query()->first() ?? Category::factory()->create();

        return Product::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'product-'.uniqid(),
        ], $attributes));
    }

    public function test_product_table_omits_id_metal_type_and_target_group_columns(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $product = $this->makeProduct([
            'name' => 'Diamond Gold Ring',
            'weight' => 2.350,
            'metal_type' => 'gold',
            'target_group' => 'women',
        ]);

        $response = $this->get(route('admin.product.index'));

        $response->assertOk();
        $response->assertSee('chkall');
        $response->assertSee('chk-'.$product->id);
        $response->assertDontSee('<label class="form-check-label ms-1" for="chk-'.$product->id.'">', false);
        $response->assertDontSee('<th>'.__('metal_type').'</th>', false);
        $response->assertDontSee('<th>'.__('target_group').'</th>', false);
    }

    public function test_product_table_shows_weight_of_first_available_piece_with_tilde(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $product = $this->makeProduct([
            'name' => 'Pendant Charm',
            'weight' => 3.000,
        ]);

        Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1.450,
            'price' => 5000000,
            'status' => \App\Enums\QuantityPieceStatus::Available,
        ]);

        Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 1.800,
            'price' => 6000000,
            'status' => \App\Enums\QuantityPieceStatus::Available,
        ]);

        $response = $this->get(route('admin.product.index'));

        $response->assertOk();
        $response->assertSee('~ 1.450');
    }

    public function test_product_table_shows_product_weight_with_tilde_when_no_pieces(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $this->makeProduct([
            'name' => 'Custom Earring',
            'weight' => 2.750,
        ]);

        $response = $this->get(route('admin.product.index'));

        $response->assertOk();
        $response->assertSee('~ 2.750');
    }
}
