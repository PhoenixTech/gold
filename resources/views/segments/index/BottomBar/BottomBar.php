<?php

namespace Resources\Views\Segments;

use App\Models\Part;
use App\Models\Setting;

class BottomBar
{
    public static function onAdd(Part $part = null)
    {
        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_color1';
        $setting->value = '#b39243';
        $setting->type = 'COLOR';
        $setting->data = json_encode(['name' => 'bottom-bar-color']);
        $setting->size = 6;
        $setting->title =  $part->area_name . ' ' . $part->part .' second gradiant color ';
        $setting->save();

    }
    public static function onRemove(Part $part = null)
    {
        Setting::where('key',$part->area_name . '_' . $part->part.'_color')->first()?->delete();

    }
    public static function onMount(Part $part = null)
    {
        return $part;
    }
}
