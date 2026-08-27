<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Waiting for courier'),
            self::Accepted => __('Courier accepted'),
            self::Rejected => __('Courier rejected'),
            self::Delivered => __('Delivered'),
            self::Failed => __('Delivery failed'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::Accepted => 'badge bg-info-subtle text-info border border-info-subtle',
            self::Rejected => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::Delivered => 'badge bg-success text-white',
            self::Failed => 'badge bg-danger-subtle text-danger border border-danger-subtle',
        };
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::Accepted];
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_map(fn (self $status) => $status->value, self::open());
    }
}
