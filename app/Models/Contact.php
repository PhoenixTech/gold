<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    public const HASH_MIN = 10_000_000;

    public const HASH_MAX = 99_999_999;

    public function getRouteKeyName()
    {
        return 'hash';
    }

    public static function generateUniqueHash(): string
    {
        do {
            $hash = (string) random_int(self::HASH_MIN, self::HASH_MAX);
        } while (static::query()->where('hash', $hash)->exists());

        return $hash;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->hash === null || $model->hash === '') {
                $model->hash = self::generateUniqueHash();
            }
        });
    }
}
