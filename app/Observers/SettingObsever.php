<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Setting;

class SettingObsever
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        //
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        if ($setting->key == 'gold' && $setting->wasChanged('value')) {
//            $p = (float)str_replace(',', '', $setting->value);
//            if ($setting->value != $p) {
//                $setting->value = $p;
//                $setting->save();
//                return;
//            }
            $pros = Product::where('status', 1)->get();
            foreach ($pros as $pro) {
                $low = [];
                if ($pro->quantities()->count() > 0) {
                    foreach ($pro->quantities as $q) {
                        $data = json_decode($q->data);
                        $q->price = CalcPrice($setting->value,$data->weight, $pro->wage) + $pro->addon;
                        $low[] = $q->price;
                        $q->save();
                    }
                    $pro->price = min($low);
                }else{
                    $pro->price = 0;
                }
                if ( ( ($pro->price *  (int) getSetting('min') ) / 100) < $pro->buy_price) {
                    $pro->stock_status = 'OUT_STOCK';
                } else{
                    $pro->stock_status = 'IN_STOCK';
                }
                $pro->save();
            }
        }
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        //
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        //
    }
}
