<?php

namespace App\Enums;

enum MetalType: string
{
    case Gold = 'gold';
    case Silver = 'silver';

    public function label(): string
    {
        return match ($this) {
            self::Gold => __('Gold'),
            self::Silver => __('Silver'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
