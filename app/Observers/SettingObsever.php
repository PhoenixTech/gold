<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\ProductPriceCalculator;

class SettingObsever
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        $setting->raw = $setting->value;
        $setting->save();
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        if (! $setting->wasChanged('value')) {
            return;
        }

        if ($setting->key === 'gold') {
            app(ProductPriceCalculator::class)->repriceProducts('gold');
        }

        if ($setting->key === 'silver') {
            app(ProductPriceCalculator::class)->repriceProducts('silver');
        }

        if ($setting->key === 'min') {
            app(ProductPriceCalculator::class)->repriceProducts();
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
