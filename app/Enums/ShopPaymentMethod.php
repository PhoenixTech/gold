<?php

namespace App\Enums;

use App\Models\Payment;

enum ShopPaymentMethod: string
{
    case Pos = 'pos';
    case CardToCard = 'card_to_card';

    public function label(): string
    {
        return match ($this) {
            self::Pos => __('POS terminal'),
            self::CardToCard => __('Card to card'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Pos => __('Paid by the shop card reader.'),
            self::CardToCard => __('Bank transfer (card to card).'),
        };
    }

    public function isPaidInStore(): bool
    {
        return true;
    }

    public function paymentType(): string
    {
        return 'CARD';
    }

    public function paymentStatus(): string
    {
        return Payment::SUCCESS;
    }
}
