<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'phone',
        'account_number',
        'iban',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    public function quantities(): HasMany
    {
        return $this->hasMany(Quantity::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function toSelectOptions(bool $includeTrashed = false): array
    {
        $query = static::query();

        if ($includeTrashed) {
            $query->withTrashed();
        }

        return $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'deleted_at'])
            ->map(function (self $supplier): array {
                $label = trim("{$supplier->first_name} {$supplier->last_name}");
                if ($supplier->company_name) {
                    $label .= " — {$supplier->company_name}";
                }
                if ($supplier->trashed()) {
                    $label .= ' ('.__('Trashed').')';
                }

                return [
                    'id' => $supplier->id,
                    'label' => $label,
                    'disabled' => $supplier->trashed(),
                ];
            })
            ->all();
    }
}
