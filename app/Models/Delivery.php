<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryFactory> */
    use HasFactory;

    public const MAX_PIN_ATTEMPTS = 5;

    protected $hidden = [
        'code_hash',
    ];

    protected $fillable = [
        'invoice_id',
        'courier_id',
        'code_hash',
        'status',
        'failed_attempts',
        'reject_reason',
        'sms_sent_at',
        'accepted_at',
        'rejected_at',
        'delivered_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'failed_attempts' => 'integer',
            'sms_sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, DeliveryStatus::open(), true);
    }

    public function isLocked(): bool
    {
        return $this->failed_attempts >= self::MAX_PIN_ATTEMPTS;
    }

    public function isPending(): bool
    {
        return $this->status === DeliveryStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === DeliveryStatus::Accepted;
    }

    public function belongsToCourier(User $courier): bool
    {
        return (int) $this->courier_id === (int) $courier->id;
    }

    /**
     * @param  Builder<Delivery>  $query
     * @return Builder<Delivery>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', DeliveryStatus::openValues());
    }

    /**
     * @param  Builder<Delivery>  $query
     * @return Builder<Delivery>
     */
    public function scopeForCourier(Builder $query, User $courier): Builder
    {
        return $query->where('courier_id', $courier->id);
    }
}
