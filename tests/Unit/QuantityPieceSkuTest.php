<?php

namespace Tests\Unit;

use App\Models\Quantity;
use PHPUnit\Framework\TestCase;

class QuantityPieceSkuTest extends TestCase
{
    public function test_formats_piece_sku_with_five_digit_suffix(): void
    {
        $this->assertSame('F1A0001-00004', Quantity::pieceSku('F1A0001', 4));
        $this->assertSame('F1A0001-00001', Quantity::pieceSku('F1A0001', 1));
    }

    public function test_reads_piece_number_from_matching_code(): void
    {
        $this->assertSame(4, Quantity::pieceNumberFromCode('F1A0001-00004', 'F1A0001'));
        $this->assertSame(5, Quantity::pieceNumberFromCode('F1A000100005', 'F1A0001')); // contiguous per sku-2.md example
        $this->assertSame(4, Quantity::pieceNumberFromCode('F1A0001-0004', 'F1A0001')); // legacy 4-digit
        $this->assertNull(Quantity::pieceNumberFromCode('OTHER-00004', 'F1A0001'));
        $this->assertNull(Quantity::pieceNumberFromCode('', 'F1A0001'));
    }

    public function test_assigns_sequential_skus_to_empty_codes(): void
    {
        $items = Quantity::assignPieceSkus([
            ['weight' => 1],
            ['weight' => 1.1, 'code' => ''],
            ['weight' => 1.2],
            ['weight' => 1.3],
        ], 'F1A0001');

        $this->assertSame('F1A0001-00001', $items[0]['code']);
        $this->assertSame('F1A0001-00002', $items[1]['code']);
        $this->assertSame('F1A0001-00003', $items[2]['code']);
        $this->assertSame('F1A0001-00004', $items[3]['code']);
    }

    public function test_keeps_existing_codes_and_continues_the_sequence(): void
    {
        $items = Quantity::assignPieceSkus([
            ['code' => 'F1A0001-00001'],
            ['code' => 'F1A0001-00002'],
            ['code' => 'F1A0001-00003'],
            ['code' => ''],
        ], 'F1A0001');

        $this->assertSame('F1A0001-00004', $items[3]['code']);
    }
}
