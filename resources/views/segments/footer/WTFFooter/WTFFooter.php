<?php

namespace Resources\Views\Segments;

use App\Models\Category;
use App\Models\Part;
use App\Models\Setting;

class WTFFooter
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

    }
    public static function onRemove(Part $part = null)
    {
        Setting::where('key',$part->area_name . '_' . $part->part.'_categories')->first()?->delete();

    }
    public static function onMount(Part $part = null)
    {
        return $part;
    }
}
