<?php

namespace App\Enums;

enum TargetGroup: string
{
    case Men = 'men';
    case Women = 'women';
    case Children = 'children';
    case Unisex = 'unisex';

    public function label(): string
    {
        return match ($this) {
            self::Men => __('Men'),
            self::Women => __('Women'),
            self::Children => __('Children'),
            self::Unisex => __('Unisex'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
