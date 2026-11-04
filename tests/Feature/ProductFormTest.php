<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    public function test_failed_save_keeps_description_and_tags(): void
    {
        $this->from(route('admin.product.create'))
            ->post(route('admin.product.store'), ['name' => 'abc', 'desc' => '<p>متن توضیحات من</p>', 'tags' => 'طلا,,هدیه'])
            ->assertSessionHasErrors(['name', 'excerpt', 'category_id']);

        $this->get(route('admin.product.create'))
            ->assertSee('متن توضیحات من', false)
            ->assertSee('طلا,,هدیه', false);
    }

    public function test_failed_save_marks_the_tab_and_names_the_field(): void
    {
        $this->from(route('admin.product.create'))
            ->post(route('admin.product.store'), ['name' => 'abc']);

        $this->get(route('admin.product.create'))
            ->assertSee('data-tab="step1" class="active has-error"', false)
            ->assertSee('data-tab="step5" class=" "', false)
            ->assertSee(__('validation.required', ['attribute' => __('Excerpt')]), false);
    }

    public function test_discounts_and_attachments_appear_only_after_the_product_exists(): void
    {
        $this->get(route('admin.product.create'))
            ->assertSee(__('Save the product first to add discounts and attachments.'), false)
            ->assertDontSee('<fast-attaching', false);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('admin.product.edit', $product->slug))
            ->assertOk()
            ->assertSee('<fast-attaching', false);
    }
}
