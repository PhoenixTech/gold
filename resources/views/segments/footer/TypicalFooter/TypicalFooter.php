<?php

namespace Resources\Views\Segments;

use App\Models\Menu;
use App\Models\Part;
use App\Models\Setting;

class TypicalFooter
{
    public static function onAdd(Part $part = null)
    {
        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part . '_menu';
        $setting->value = Menu::first()->id ?? 1;
        $setting->size = 12;
        $setting->type = 'MENU';
//        $setting->data = json_encode(['xmin' => 2, 'xmax' => 90]);
        $setting->title =  $part->area_name . ' ' . $part->part . ' quick links menu';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part . '_about';
        $setting->value = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus aliquid consequuntur culpa cupiditate dignissimos dolor doloremque error facilis ipsum iure officia quam qui, tempora! Fuga harum impedit iusto magnam veniam.';
        $setting->size = 12;
        $setting->type = 'EDITOR';
        $setting->title =  $part->area_name . ' ' . $part->part . ' about text';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part . '_address';
        $setting->value = '';
        $setting->size = 12;
        $setting->type = 'TEXT';
        $setting->title =  $part->area_name . ' ' . $part->part . ' address';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part . '_bg';
        $setting->value = '#23272e';
        $setting->size = 3;
        $setting->type = 'COLOR';
//        $setting->data = json_encode(['name' => 'typical-footer-bg']);
        $setting->title =  $part->area_name . ' ' . $part->part . ' background';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part . '_accent';
        $setting->value = '#db9a00';
        $setting->size = 3;
        $setting->type = 'COLOR';
//        $setting->data = json_encode(['name' => 'typical-footer-accent']);
        $setting->title =  $part->area_name . ' ' . $part->part . ' accent color';
        $setting->save();
    }
    public static function onRemove(Part $part = null)
    {
        Setting::where('key', $part->area_name . '_' . $part->part . '_menu')->first()?->delete();
        Setting::where('key', $part->area_name . '_' . $part->part . '_about')->first()?->delete();
        Setting::where('key', $part->area_name . '_' . $part->part . '_address')->first()?->delete();
        Setting::where('key', $part->area_name . '_' . $part->part . '_bg')->first()?->delete();
        Setting::where('key', $part->area_name . '_' . $part->part . '_accent')->first()?->delete();
    }
    public static function onMount(Part $part = null)
    {
        return $part;
    }
}
