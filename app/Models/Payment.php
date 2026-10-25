<?php

namespace App\Models;

use App\Enums\ShopPaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    const PENDING = 'PENDING';

    const SUCCESS = 'SUCCESS';

    const FAIL = 'FAIL';

    const CANCEL = 'CANCEL';

    const CHANNEL_IN_STORE = 'in_store';

    protected $casts = [
        'meta' => 'array',
    ];

    public static $types = ['ONLINE', 'CHEQUE', 'CASH', 'CARD', 'CASH_ON_DELIVERY'];

    public static $status = ['PENDING', 'SUCCESS', 'FAIL', 'CANCEL'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeInStore(Builder $query): Builder
    {
        return $query->where('status', self::SUCCESS)
            ->where('meta->channel', self::CHANNEL_IN_STORE);
    }

    public function scopePhysical(Builder $query): Builder
    {
        return $query->where('meta->channel', self::CHANNEL_IN_STORE);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('meta->channel')->orWhere('meta->channel', '!=', self::CHANNEL_IN_STORE);
        });
    }

    public function isPhysical(): bool
    {
        return ($this->meta['channel'] ?? null) === self::CHANNEL_IN_STORE;
    }

    public function methodLabel(): string
    {
        $method = $this->meta['method'] ?? null;
        if (is_string($method) && $method !== '') {
            $enum = ShopPaymentMethod::tryFrom($method);
            if ($enum !== null) {
                return $enum->label();
            }
        }

        return match ($this->type) {
            'ONLINE' => __('Online Gateway'),
            'CARD' => __('Card to card'),
            'CASH' => __('Cash'),
            'CHEQUE' => __('Cheque'),
            'CASH_ON_DELIVERY' => __('Cash on delivery'),
            default => (string) ($this->type ?: '—'),
        };
    }

    public function formattedPaymentDate(): string
    {
        $metaDate = $this->meta['payment_date'] ?? null;
        $metaTime = $this->meta['payment_time'] ?? null;

        if (! empty($metaDate)) {
            return ! empty($metaTime) ? "{$metaDate} {$metaTime}" : (string) $metaDate;
        }

        return $this->created_at ? $this->created_at->jdate('Y/m/d H:i') : '—';
    }
}
