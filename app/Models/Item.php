<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Item extends Model
{
    use HasFactory,HasTranslations;

    public $translatable = ['title'];

    protected $guarded = ['id'];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }


    public function parent()
    {
        return $this->belongsTo(Item::class, 'parent');
    }

    public function children()
    {
        return $this->hasMany(Item::class, 'parent');
    }

    public function dest(){
        return $this->morphTo('menuable','menuable_type','menuable_id');
    }

    public function webUrl()
    {
        if ($this->dest) {
            return $this->dest->webUrl();
        }

        if (!empty($this->meta)) {
            $url = $this->meta;

            // If the stored URL contains a local/test domain, convert to current domain
            $parsed = parse_url($url);
            if (isset($parsed['host']) && in_array($parsed['host'], ['zhonella.test', 'localhost', '127.0.0.1'])) {
                $path = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
                $url = url($path);
            }

            if (config('app.xlang.active') && app()->getLocale() != config('app.xlang.main')) {
                if ($url[0] != '/') {
                    $welcome = \route('client.welcome');
                    return str_replace($welcome, $welcome . '/' . app()->getLocale(), $url);
                } else {
                    return '/' . app()->getLocale() . $url;
                }
            }
            return $url;
        }

        return '#';
    }
}
