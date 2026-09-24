<?php

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'IN_STOCK';
    case OutOfStock = 'OUT_STOCK';
    case BackOrder = 'BACK_ORDER';

    public function label(): string
    {
        return match ($this) {
            self::InStock => __('In Stock'),
            self::OutOfStock => __('Out of Stock'),
            self::BackOrder => __('Back Order'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
