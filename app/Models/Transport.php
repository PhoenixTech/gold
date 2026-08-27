<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Transport extends Model
{
    use HasFactory,HasTranslations,SoftDeletes;

    public $translatable = ['title', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'requires_delivery_code' => 'boolean',
        ];
    }
}
