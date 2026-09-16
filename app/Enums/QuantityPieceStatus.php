<?php

namespace App\Enums;

enum QuantityPieceStatus: string
{
    case Available = 'available';
    case Scrapped = 'scrapped';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Available'),
            self::Scrapped => __('Scrapped'),
            self::Sold => __('Sold'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Scrapped => 'badge bg-danger-subtle text-danger border border-danger-subtle',
            self::Sold => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
