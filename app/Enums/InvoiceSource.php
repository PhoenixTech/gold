<?php

namespace App\Enums;

enum InvoiceSource: string
{
    case Checkout = 'CHECKOUT';
    case Manual = 'MANUAL';

    public function label(): string
    {
        return match ($this) {
            self::Checkout => __('Customer checkout'),
            self::Manual => __('Shop sale'),
        };
    }
}
