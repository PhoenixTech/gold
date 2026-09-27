<?php

namespace App\Enums;

enum GoldKarat: int
{
    case K6 = 6;
    case K8 = 8;
    case K9 = 9;
    case K10 = 10;
    case K12 = 12;
    case K14 = 14;
    case K15 = 15;
    case K18 = 18;
    case K20 = 20;
    case K21 = 21;
    case K22 = 22;
    case K24 = 24;

    public const BASE_COEFFICIENT = 750;
    public const DEFAULT_KARAT = 18;

    public function coefficient(): int
    {
        return match ($this) {
            self::K6 => 250,
            self::K8 => 333,
            self::K9 => 375,
            self::K10 => 417,
            self::K12 => 500,
            self::K14 => 583,
            self::K15 => 625,
            self::K18 => 750,
            self::K20 => 833,
            self::K21 => 875,
            self::K22 => 916,
            self::K24 => 999,
        };
    }

    public function purityPercentage(): string
    {
        return match ($this) {
            self::K6 => '25%',
            self::K8 => '33.3%',
            self::K9 => '37.5%',
            self::K10 => '41.7%',
            self::K12 => '50%',
            self::K14 => '58.3%',
            self::K15 => '62.5%',
            self::K18 => '75%',
            self::K20 => '83.3%',
            self::K21 => '87.5%',
            self::K22 => '91.6%',
            self::K24 => '99.9%',
        };
    }

    public function pureGoldRatio(): string
    {
        return match ($this) {
            self::K6 => '6/24',
            self::K8 => '8/24',
            self::K9 => '9/24',
            self::K10 => '10/24',
            self::K12 => '12/24',
            self::K14 => '7/12',
            self::K15 => '5/8',
            self::K18 => '3/4',
            self::K20 => '5/6',
            self::K21 => '7/8',
            self::K22 => '11/12',
            self::K24 => '24/24',
        };
    }

    public function ratio(): float
    {
        return $this->coefficient() / self::BASE_COEFFICIENT;
    }

    public function label(): string
    {
        return __(':karat Karat', ['karat' => $this->value]);
    }

    public function optionLabel(): string
    {
        $label = $this->label();
        $code = $this->coefficient();
        $purity = $this->purityPercentage();

        if ($this === self::K18) {
            return "{$label} ({$code} - {$purity}) - " . __('Default');
        }

        return "{$label} ({$code} - {$purity})";
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
