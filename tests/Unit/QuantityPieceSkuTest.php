<?php

namespace Tests\Unit;

use App\Models\Quantity;
use PHPUnit\Framework\TestCase;

class QuantityPieceSkuTest extends TestCase
{
    public function test_formats_piece_sku_with_four_digit_suffix(): void
    {
        $this->assertSame('UG060119-0004', Quantity::pieceSku('UG060119', 4));
        $this->assertSame('UG060119-0001', Quantity::pieceSku('UG060119', 1));
    }

    public function test_reads_piece_number_from_matching_code(): void
    {
        $this->assertSame(4, Quantity::pieceNumberFromCode('UG060119-0004', 'UG060119'));
        $this->assertNull(Quantity::pieceNumberFromCode('OTHER-0004', 'UG060119'));
        $this->assertNull(Quantity::pieceNumberFromCode('', 'UG060119'));
    }

    public function test_assigns_sequential_skus_to_empty_codes(): void
    {
        $items = Quantity::assignPieceSkus([
            ['weight' => 1],
            ['weight' => 1.1, 'code' => ''],
            ['weight' => 1.2],
            ['weight' => 1.3],
        ], 'UG060119');

        $this->assertSame('UG060119-0001', $items[0]['code']);
        $this->assertSame('UG060119-0002', $items[1]['code']);
        $this->assertSame('UG060119-0003', $items[2]['code']);
        $this->assertSame('UG060119-0004', $items[3]['code']);
    }

    public function test_keeps_existing_codes_and_continues_the_sequence(): void
    {
        $items = Quantity::assignPieceSkus([
            ['code' => 'UG060119-0001'],
            ['code' => 'UG060119-0002'],
            ['code' => 'UG060119-0003'],
            ['code' => ''],
        ], 'UG060119');

        $this->assertSame('UG060119-0004', $items[3]['code']);
    }
}
