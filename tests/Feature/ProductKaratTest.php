<?php

namespace Tests\Feature;

use App\Enums\GoldKarat;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductKaratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMetalSettings();
    }

    public function test_admin_can_save_product_with_custom_gold_karat(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->post(route('admin.product.store'), [
            'name' => 'Gold Ring 24K',
            'slug' => 'gold-ring-24k',
            'excerpt' => 'A fine luxury gold ring.',
            'category_id' => $category->id,
            'metal_type' => 'gold',
            'karat' => 24,
            'weight' => 2.5,
            'labor_charge_1' => 10,
            'profit' => 7,
            'tax' => 9,
            'status' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'slug' => 'gold-ring-24k',
            'metal_type' => 'gold',
            'karat' => 24,
        ]);

        $product = Product::where('slug', 'gold-ring-24k')->firstOrFail();
        $this->assertSame(GoldKarat::K24, $product->karat);
        $this->assertSame(999, $product->getGoldKarat()->coefficient());
    }

    public function test_validation_rejects_unsupported_karat_values(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->from(route('admin.product.create'))->post(route('admin.product.store'), [
            'name' => 'Invalid Karat Product',
            'slug' => 'invalid-karat-product',
            'excerpt' => 'Invalid karat testing.',
            'category_id' => $category->id,
            'metal_type' => 'gold',
            'karat' => 17,
        ]);

        $response->assertRedirect(route('admin.product.create'));
        $response->assertSessionHasErrors('karat');
    }

    public function test_product_pricing_subpage_renders_all_karat_options_with_coefficients(): void
    {
        $product = $this->makeProduct(['metal_type' => 'gold', 'karat' => 18]);

        $html = view('admin.products.sub-pages.product-step-pricing', [
            'item' => $product,
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('name="karat"', $html);
        $this->assertStringContainsString('id="karat"', $html);

        foreach (GoldKarat::cases() as $karatCase) {
            $this->assertStringContainsString('value="'.$karatCase->value.'"', $html);
            $this->assertStringContainsString('data-coefficient="'.$karatCase->coefficient().'"', $html);
        }
    }

    public function test_client_product_page_displays_gold_karat_in_specifications(): void
    {
        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'karat' => 24,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
        ]);

        $response = $this->get(route('client.product', $product->slug));

        $response->assertOk();
        $response->assertSee('عیار 24');
        $response->assertSee('999');
    }

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    protected function seedMetalSettings(): void
    {
        foreach ([
            'gold' => '2000000',
            'silver' => '80000',
            'min' => '105',
        ] as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->section = 'General';
            $setting->type = 'TEXT';
            $setting->title = $key;
            $setting->ltr = true;
            $setting->size = 12;
            $setting->value = $value;
            $setting->raw = $value;
            $setting->save();
        }
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
}
