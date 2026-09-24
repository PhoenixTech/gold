<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\ProductPriceCalculator;

class SettingObserver
{
    public function creating(Setting $setting): void
    {
        $setting->raw = $setting->value;
    }

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
}
