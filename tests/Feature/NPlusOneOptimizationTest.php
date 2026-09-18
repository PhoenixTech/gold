<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminDashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NPlusOneOptimizationTest extends TestCase
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

    public function test_product_list_query_count_does_not_grow_linearly_with_products(): void
    {
        $admin = $this->actingAsAdmin();
        $category = Category::create(['name' => 'Gold Category', 'slug' => 'gold-cat']);

        for ($i = 1; $i <= 10; $i++) {
            Product::forceCreate([
                'name' => "Product $i",
                'slug' => "product-$i",
                'category_id' => $category->id,
                'user_id' => $admin->id,
                'status' => 1,
                'stock_status' => 'IN_STOCK',
                'stock_quantity' => 5,
                'price' => 1000000,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('client.products'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $queries = array_map(fn ($q) => $q['query'], DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $queryCount, "Product list executed {$queryCount} queries");
    }

    public function test_admin_dashboard_recent_invoices_does_not_fire_receipt_queries(): void
    {
        $customer = Customer::forceCreate([
            'name' => 'John Doe',
            'mobile' => '09123456789',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Invoice::forceCreate([
                'customer_id' => $customer->id,
                'status' => Invoice::PENDING,
                'total_price' => 100000,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $dashboard = app(AdminDashboardStats::class);
        $data = $dashboard->data();

        // Ensure recentInvoices has payment_receipts_count loaded
        $this->assertNotEmpty($data['recentInvoices']);
        foreach ($data['recentInvoices'] as $invoice) {
            $this->assertArrayHasKey('payment_receipts_count', $invoice->getAttributes());
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $queryCount);
    }

    public function test_frontend_header_renders_submenus_with_eager_loaded_relations(): void
    {
        $admin = $this->actingAsAdmin();
        $category = Category::create(['name' => 'Jewelry', 'slug' => 'jewelry']);
        $menu = Menu::forceCreate(['name' => 'Main Menu', 'user_id' => $admin->id]);
        $menu->items()->create([
            'user_id' => $admin->id,
            'title' => 'Jewelry Menu',
            'menuable_id' => $category->id,
            'menuable_type' => Category::class,
            'sort' => 1,
        ]);

        clearMenuCache();
        $html = view('client.partials.header')->render();
        $this->assertStringContainsString('sub-menu', $html);
        $this->assertStringContainsString('Jewelry', $html);
    }
}
