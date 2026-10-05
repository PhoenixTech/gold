<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
            self::Disabled => __('Disabled'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            self::Published => 'badge bg-success-subtle text-success-emphasis border border-success-subtle',
            self::Disabled => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle',
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
