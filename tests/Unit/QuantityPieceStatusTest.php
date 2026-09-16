<?php

namespace Tests\Unit;

use App\Enums\QuantityPieceStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuantityPieceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_piece_status_defaults_to_available_and_is_cast_to_enum(): void
    {
        $product = $this->createProduct();
        $piece = Quantity::create([
            'product_id' => $product->id,
            'weight' => 2.5,
            'count' => 1,
            'code' => 'UG060119-0001',
        ]);

        $this->assertInstanceOf(QuantityPieceStatus::class, $piece->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $piece->fresh()->status);
        $this->assertTrue($piece->isAvailable());
        $this->assertFalse($piece->isScrapped());
        $this->assertFalse($piece->isSold());
    }

    public function test_mark_scrapped_sets_status_scrapped_and_zero_count(): void
    {
        $product = $this->createProduct();
        $piece = Quantity::create([
            'product_id' => $product->id,
            'weight' => 2.5,
            'count' => 1,
            'code' => 'UG060119-0001',
        ]);

        $piece->markScrapped();
        $piece->refresh();

        $this->assertSame(QuantityPieceStatus::Scrapped, $piece->status);
        $this->assertSame(0, $piece->count);
        $this->assertTrue($piece->isScrapped());
        $this->assertFalse($piece->isAvailable());
    }

    public function test_mark_available_restores_status_and_count(): void
    {
        $product = $this->createProduct();
        $piece = Quantity::create([
            'product_id' => $product->id,
            'weight' => 2.5,
            'count' => 0,
            'status' => QuantityPieceStatus::Scrapped,
            'code' => 'UG060119-0001',
        ]);

        $piece->markAvailable();
        $piece->refresh();

        $this->assertSame(QuantityPieceStatus::Available, $piece->status);
        $this->assertSame(1, $piece->count);
        $this->assertTrue($piece->isAvailable());
    }

    public function test_mark_sold_sets_status_sold_and_zero_count(): void
    {
        $product = $this->createProduct();
        $piece = Quantity::create([
            'product_id' => $product->id,
            'weight' => 2.5,
            'count' => 1,
            'code' => 'UG060119-0001',
        ]);

        $piece->markSold();
        $piece->refresh();

        $this->assertSame(QuantityPieceStatus::Sold, $piece->status);
        $this->assertSame(0, $piece->count);
        $this->assertTrue($piece->isSold());
        $this->assertFalse($piece->isAvailable());
    }

    public function test_scrapped_and_sold_pieces_excluded_from_available_scopes_and_weight(): void
    {
        $product = $this->createProduct(['weight' => 2.0, 'stock_quantity' => 1]);

        // Available piece
        Quantity::create([
            'product_id' => $product->id,
            'weight' => 3.0,
            'count' => 1,
            'status' => QuantityPieceStatus::Available,
            'code' => 'UG060119-0001',
        ]);

        // Scrapped piece
        Quantity::create([
            'product_id' => $product->id,
            'weight' => 4.0,
            'count' => 0,
            'status' => QuantityPieceStatus::Scrapped,
            'code' => 'UG060119-0002',
        ]);

        // Sold piece
        Quantity::create([
            'product_id' => $product->id,
            'weight' => 5.0,
            'count' => 0,
            'status' => QuantityPieceStatus::Sold,
            'code' => 'UG060119-0003',
        ]);

        $this->assertSame(1, $product->availableQuantities()->count());
        $this->assertSame(1, $product->scrappedQuantities()->count());
        $this->assertSame(1, $product->soldQuantities()->count());
        $this->assertSame(3, $product->totalOrderedCount());

        // Total stock weight must only calculate available piece (3.0g), excluding scrapped (4.0g) and sold (5.0g)
        $this->assertEquals(3.0, $product->totalStockWeight());
    }

    protected function createProduct(array $attributes = []): Product
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sku' => 'UG060119',
            'slug' => 'product-'.uniqid(),
            'status' => 1,
            'stock_status' => 'IN_STOCK',
        ], $attributes));
    }
}
