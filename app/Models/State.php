<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class State extends Model
{
    use HasFactory,HasTranslations,SoftDeletes;

    public $translatable = ['name', 'country'];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function isTehran(): bool
    {
        if ((int) $this->id === 8) {
            return true;
        }

        $name = (string) $this->name;
        $raw = (string) ($this->getAttributes()['name'] ?? '');

        return str_contains($name, 'تهران')
            || str_contains(strtolower($name), 'tehran')
            || str_contains($raw, 'تهران')
            || str_contains(strtolower($raw), 'tehran');
    }
}
