<?php

namespace Tests\Feature;

use App\Enums\QuantityPieceStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockAuditDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSettings();
    }

    public function test_stock_dashboard_renders_with_stocktaking_columns_and_analytics(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create(['name' => 'انگشتر']);
        $product = $this->makeProduct([
            'category_id' => $category->id,
            'name' => 'انگشتر تک تاش',
            'sku' => 'PRD-RING-001',
            'stock_quantity' => 2,
        ]);

        Quantity::create(['product_id' => $product->id, 'weight' => 2, 'count' => 1, 'status' => QuantityPieceStatus::Available, 'code' => 'P-0001']);
        Quantity::create(['product_id' => $product->id, 'weight' => 3, 'count' => 1, 'status' => QuantityPieceStatus::Available, 'code' => 'P-0002']);
        Quantity::create(['product_id' => $product->id, 'weight' => 4, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'P-0003']);
        Quantity::create(['product_id' => $product->id, 'weight' => 5, 'count' => 0, 'status' => QuantityPieceStatus::Sold, 'code' => 'P-0004']);

        $response = $this->get(route('admin.stock.index'));
        $response->assertStatus(200);

        // Verify required columns/headers are present
        $response->assertSee(__('Net stock'));
        $response->assertSee(__('Total ordered'));
        $response->assertSee(__('Scrapped pieces'));
        $response->assertSee(__('Sold pieces'));

        // Verify product row displays correct counts
        $response->assertSee('انگشتر تک تاش');
        $response->assertSee($product->fresh()->sku);

        // Verify modal and piece button are present
        $response->assertSee('stockPiecesModal');
        $response->assertSee('view-pieces-btn');

        // Verify analytics card and redundant filters are removed
        $response->assertDontSee(__('Stock audit reports & business insights'));
        $response->assertDontSee('name="filter[metal_type]"', false);
        $response->assertDontSee('name="filter[low_stock]"', false);
        $response->assertDontSee('name="filter[below_buy_price]"', false);
    }

    public function test_pieces_json_endpoint_returns_all_codes_with_statuses(): void
    {
        $this->actingAsAdmin();

        $product = $this->makeProduct(['name' => 'دستبند طلا']);
        $p1 = Quantity::create(['product_id' => $product->id, 'weight' => 2.5, 'count' => 1, 'status' => QuantityPieceStatus::Available, 'code' => 'D-0001']);
        $p2 = Quantity::create(['product_id' => $product->id, 'weight' => 3.5, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'D-0002']);

        // Test by model instance
        $response = $this->getJson(route('admin.stock.pieces', $product));
        $response->assertStatus(200);
        $response->assertJsonPath('product.id', $product->id);
        $response->assertJsonPath('pieces.0.code', 'D-0001');
        $response->assertJsonPath('pieces.0.status', 'available');
        $response->assertJsonPath('pieces.1.code', 'D-0002');
        $response->assertJsonPath('pieces.1.status', 'scrapped');

        // Test by integer ID explicitly (as used by modal JavaScript)
        $responseById = $this->getJson("/dashboard/stock/product/{$product->id}/pieces");
        $responseById->assertStatus(200);
        $responseById->assertJsonPath('product.id', $product->id);
    }

    public function test_toggle_piece_scrap_endpoint_toggles_status_and_updates_net_stock(): void
    {
        $this->actingAsAdmin();

        $product = $this->makeProduct(['stock_quantity' => 2]);
        $piece1 = Quantity::create(['product_id' => $product->id, 'weight' => 2, 'count' => 1, 'status' => QuantityPieceStatus::Available, 'code' => 'T-0001']);
        $piece2 = Quantity::create(['product_id' => $product->id, 'weight' => 3, 'count' => 1, 'status' => QuantityPieceStatus::Available, 'code' => 'T-0002']);

        // Toggle piece 1 to scrapped
        $response = $this->postJson(route('admin.stock.piece.toggle-scrap', $piece1));
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'scrapped');
        $response->assertJsonPath('product_stock_quantity', 1);

        $this->assertTrue($piece1->fresh()->isScrapped());
        $this->assertSame(0, $piece1->fresh()->count);
        $this->assertSame(1, $product->fresh()->stock_quantity);

        // Toggle back to available
        $response = $this->postJson(route('admin.stock.piece.toggle-scrap', $piece1));
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'available');
        $response->assertJsonPath('product_stock_quantity', 2);

        $this->assertTrue($piece1->fresh()->isAvailable());
        $this->assertSame(1, $piece1->fresh()->count);
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_updating_product_with_scrapped_items_persists_status(): void
    {
        $this->actingAsAdmin();

        $product = $this->makeProduct([
            'metal_type' => 'gold',
            'target_group' => 'unisex',
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'excerpt' => 'توضیحات کوتاه محصول آزمایشی',
        ]);

        $payload = json_encode([
            ['code' => 'U-0001', 'weight' => 2.0, 'status' => 'available'],
            ['code' => 'U-0002', 'weight' => 3.0, 'status' => 'scrapped'],
        ]);

        $response = $this->post(route('admin.product.update', $product), [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'excerpt' => $product->excerpt,
            'category_id' => $product->category_id,
            'metal_type' => 'gold',
            'target_group' => 'unisex',
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'addon' => 0,
            'wage' => 15,
            'labor_charge_1' => 15,
            'profit' => 7,
            'tax' => 9,
            'cat' => [],
            'tags' => '',
            'stock_items' => $payload,
        ]);
        $response->assertRedirect();

        $quantities = $product->fresh()->quantities()->orderBy('id')->get();
        $this->assertCount(2, $quantities);
        $this->assertTrue($quantities[0]->isAvailable());
        $this->assertTrue($quantities[1]->isScrapped());

        // Product net stock should only be 1
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_sorting_by_most_sold_and_most_scrapped(): void
    {
        $this->actingAsAdmin();

        $p1 = $this->makeProduct(['name' => 'محصول پرفروش']);
        $p2 = $this->makeProduct(['name' => 'محصول پرخرابی']);

        // p1 has 3 sold
        Quantity::create(['product_id' => $p1->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Sold, 'code' => 'S1']);
        Quantity::create(['product_id' => $p1->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Sold, 'code' => 'S2']);
        Quantity::create(['product_id' => $p1->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Sold, 'code' => 'S3']);

        // p2 has 4 scrapped
        Quantity::create(['product_id' => $p2->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'X1']);
        Quantity::create(['product_id' => $p2->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'X2']);
        Quantity::create(['product_id' => $p2->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'X3']);
        Quantity::create(['product_id' => $p2->id, 'weight' => 1, 'count' => 0, 'status' => QuantityPieceStatus::Scrapped, 'code' => 'X4']);

        $responseSold = $this->get(route('admin.stock.index', ['sort' => 'most_sold', 'filter' => ['stock_condition' => 'all']]));
        $responseSold->assertStatus(200);

        $responseScrapped = $this->get(route('admin.stock.index', ['sort' => 'most_scrapped', 'filter' => ['stock_condition' => 'all']]));
        $responseScrapped->assertStatus(200);
    }

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    protected function seedSettings(): void
    {
        foreach ([
            'gold' => '2000000',
            'silver' => '80000',
            'min' => '100',
        ] as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->section = 'General';
            $setting->type = 'TEXT';
            $setting->title = $key;
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
            'metal_type' => 'gold',
            'status' => 1,
            'stock_status' => 'IN_STOCK',
        ], $attributes));
    }
}
