<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $appends = ['is_tehran'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function isTehran(): bool
    {
        if ($this->state_id) {
            if ((int) $this->state_id === 8) {
                return true;
            }
            $state = $this->relationLoaded('state') ? $this->state : $this->state()->first();
            if ($state) {
                return $state->isTehran();
            }
        }

        $addressText = (string) ($this->address ?? '');

        return str_contains($addressText, 'تهران') || str_contains(strtolower($addressText), 'tehran');
    }

    public function getIsTehranAttribute(): bool
    {
        return $this->isTehran();
    }
}
