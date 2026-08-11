<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'card_number',
        'account_number',
        'iban',
        'bank_name',
        'account_holder_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function activeAccount(): ?self
    {
        return static::query()->active()->latest('id')->first();
    }

    /**
     * @return array{bank_account_id: int|null, bank_name: string|null, account_holder_name: string|null, card_number: string|null, account_number: string|null, iban: string|null}
     */
    public function toPaymentMeta(): array
    {
        return [
            'bank_account_id' => $this->id,
            'bank_name' => $this->bank_name,
            'account_holder_name' => $this->account_holder_name,
            'card_number' => $this->card_number,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
        ];
    }

    /**
     * @return array{bank_name: string|null, account_holder_name: string|null, card_number: string|null, account_number: string|null, iban: string|null}
     */
    public static function displayPayload(?self $account = null): array
    {
        $account ??= static::activeAccount();

        return [
            'bank_name' => $account?->bank_name,
            'account_holder_name' => $account?->account_holder_name,
            'card_number' => $account?->card_number,
            'account_number' => $account?->account_number,
            'iban' => $account?->iban,
        ];
    }
}
