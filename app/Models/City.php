<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasTranslations,SoftDeletes;

    public $translatable = ['name'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();
        $attributes['name'] = $this->name;

        return $attributes;
    }
}
