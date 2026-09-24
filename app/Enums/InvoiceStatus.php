<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Pending = 'PENDING';
    case AwaitingPayment = 'AWAITING_PAYMENT';
    case WaitingReceipt = 'WAITING_RECEIPT';
    case WaitingConfirmation = 'WAITING_CONFIRMATION';
    case Paid = 'PAID';
    case Processing = 'PROCESSING';
    case OutForDelivery = 'OUT_FOR_DELIVERY';
    case Completed = 'COMPLETED';
    case Canceled = 'CANCELED';
    case Failed = 'FAILED';

    public function label(): string
    {
        return __($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::WaitingReceipt => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::WaitingConfirmation => 'badge bg-primary-subtle text-primary border border-primary-subtle',
            self::Paid => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Processing => 'badge bg-info-subtle text-info border border-info-subtle',
            self::OutForDelivery => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::Completed => 'badge bg-success text-white',
            self::Failed => 'badge bg-danger-subtle text-danger border border-danger-subtle',
            self::Canceled => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            default => 'badge bg-info-subtle text-info border border-info-subtle',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
