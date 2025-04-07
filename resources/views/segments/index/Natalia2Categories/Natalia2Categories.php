<?php

namespace Resources\Views\Segments;

use App\Models\Category;
use App\Models\Part;
use App\Models\Setting;
use Illuminate\Support\Facades\File;

class Natalia2Categories
{
    public static function onAdd(Part $part = null)
    {
        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_categories';
        $setting->value = json_encode([Category::first()->id]);
        $setting->size = 12;
        $setting->type = 'CATEGORY_SET';
//        $setting->data = json_encode(['xmin' => 2, 'xmax' => 90]);
        $setting->title =  $part->area_name . ' ' . $part->part. ' categories';
        $setting->save();


        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_title';
        $setting->value = 'Modern categories';
        $setting->type = 'TEXT';
        $setting->size = 6;
        $setting->title =  $part->area_name . ' ' . $part->part .' modern categories title';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_subtitle';
        $setting->value = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit';
        $setting->type = 'TEXT';
        $setting->size = 12;
        $setting->title =  $part->area_name . ' ' . $part->part .' modern categories subtitle';
        $setting->save();


        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_webp';
        $setting->value = null;
        $setting->type = 'FILE';
        $setting->size = 12;
        $setting->title =  $part->area_name . ' ' . $part->part.' background image';
        $setting->save();


        File::copy(__DIR__.'/assets/womenx.webp',public_path('upload/images/').$part->area_name . '.' . $part->part.'.webp');
    }
    public static function onRemove(Part $part = null)
    {
        Setting::where('key',$part->area_name . '_' . $part->part.'_categories')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_title')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_subtitle')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_webp')->first()?->delete();

    }
    public static function onMount(Part $part = null)
    {
        return $part;
    }
}
