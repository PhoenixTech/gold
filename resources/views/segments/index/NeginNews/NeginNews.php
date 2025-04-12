<?php

namespace Resources\Views\Segments;

use App\Models\Category;
use App\Models\Group;
use App\Models\Part;
use App\Models\Setting;
use Illuminate\Support\Facades\File;

class NeginNews
{
    public static function onAdd(Part $part = null)
    {
//        $setting = new Setting();
//        $setting->section = 'theme';
//        $setting->key = $part->area_name . '_' . $part->part.'_groups';
//        $setting->value = json_encode([Group::first()->id]);
//        $setting->size = 12;
//        $setting->type = 'GROUP_SET';
////        $setting->data = json_encode(['xmin' => 2, 'xmax' => 90]);
//        $setting->title =  $part->area_name . ' ' . $part->part. ' groups';
//        $setting->save();
//
//        $setting = new Setting();
//        $setting->section = 'theme';
//        $setting->key = $part->area_name . '_' . $part->part.'_group2';
//        $setting->value = json_encode([Group::first()->id]);
//        $setting->size = 12;
//        $setting->type = 'GROUP_SET';
////        $setting->data = json_encode(['xmin' => 2, 'xmax' => 90]);
//        $setting->title =  $part->area_name . ' ' . $part->part. ' groups 2';
//        $setting->save();


        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_title';
        $setting->value = '<ul> <li> <a href="#"> test </a></li> <li> <a href="#"> test2 </a></li> </ul>';
        $setting->type = 'EDITOR';
        $setting->size = 12;
        $setting->title =  $part->area_name . ' ' . $part->part .' text 1';
        $setting->save();

        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_text';
        $setting->value = '<ul> <li> <a href="#"> test </a></li> <li> <a href="#"> test2 </a></li> </ul>';
        $setting->type = 'EDITOR';
        $setting->size = 12;
        $setting->title =  $part->area_name . ' ' . $part->part .' text 2';
        $setting->save();



        $setting = new Setting();
        $setting->section = 'theme';
        $setting->key = $part->area_name . '_' . $part->part.'_webp';
        $setting->value = null;
        $setting->type = 'FILE';
        $setting->size = 12;
        $setting->title =  $part->area_name . ' ' . $part->part.'  image';
        $setting->save();


        File::copy(__DIR__.'/assets/ses.webp',public_path('upload/images/').$part->area_name . '.' . $part->part.'.webp');
    }
    public static function onRemove(Part $part = null)
    {
//        Setting::where('key',$part->area_name . '_' . $part->part.'_group')->first()?->delete();
//        Setting::where('key',$part->area_name . '_' . $part->part.'_group2')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_title')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_text')->first()?->delete();
        Setting::where('key',$part->area_name . '_' . $part->part.'_webp')->first()?->delete();
    }
    public static function onMount(Part $part = null)
    {
        return $part;
    }
}
