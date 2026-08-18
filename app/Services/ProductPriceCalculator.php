<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use Illuminate\Support\Collection;

class ProductPriceCalculator
{
    public function baseMetalPrice(Product $product): int
    {
        $key = $product->metal_type === 'silver' ? 'silver' : 'gold';

        return (int) $this->settingValue($key);
    }

    public function feePercent(Product $product): float
    {
        $fee = $product->labor_charge_1;

        if ($fee === null || $fee === '') {
            $fee = $product->wage;
        }

        return (float) ($fee ?? 0);
    }

    public function profitRate(Product $product): float
    {
        if ($product->profit === null || $product->profit === '') {
            return 0.07;
        }

        return ((float) $product->profit) / 100;
    }

    public function taxRate(Product $product): float
    {
        if ($product->tax === null || $product->tax === '') {
            return (float) config('app.xshop.vat', 0.09);
        }

        return ((float) $product->tax) / 100;
    }

    /**
     * Calculate stored unit price for a weight using product jewelry fields.
     */
    public function calculate(Product $product, float $weight): int
    {
        return $this->calculateFromParts(
            $this->baseMetalPrice($product),
            $weight,
            $this->feePercent($product),
            $this->profitRate($product),
            $this->taxRate($product),
            (int) ($product->addon ?? 0),
        );
    }

    public function calculateFromParts(
        int|float $metalPrice,
        float $weight,
        float $feePercent,
        float $profitRate,
        float $taxRate,
        int $addon = 0,
    ): int {
        $p = (float) $metalPrice;
        $n1 = $p + ($p * ($feePercent / 100));
        $n2 = ($n1 + ($n1 * $profitRate) - $p);
        $n3 = ($n2 * $taxRate) + $n2;
        $complete = ($n3 + $p) * $weight;

        return ((int) floor($complete / 1000) * 1000) + $addon;
    }

    public function priceForQuantity(Product $product, Quantity $quantity): int
    {
        $weight = $quantity->weight;

        if ($weight === null || $weight === '') {
            $data = json_decode((string) $quantity->data, true);
            $weight = is_array($data) ? ($data['weight'] ?? 0) : 0;
        }

        return $this->calculate($product, (float) $weight);
    }

    public function repriceProducts(?string $metalType = null): void
    {
        $query = Product::query()->where('status', 1);

        if ($metalType !== null) {
            $query->where('metal_type', $metalType);
        }

        $query->with('quantities')->each(function (Product $product): void {
            $this->repriceProduct($product);
        });
    }

    public function repriceProduct(Product $product): Product
    {
        $product->loadMissing('quantities');

        /** @var Collection<int, Quantity> $available */
        $available = $product->quantities->where('count', '>', 0);

        foreach ($product->quantities as $quantity) {
            if ((float) ($quantity->weight ?? 0) <= 0 && empty($quantity->data)) {
                continue;
            }

            $quantity->price = $this->priceForQuantity($product, $quantity);
            $quantity->save();
        }

        $product->stock_quantity = $available->sum('count');
        $firstAvailable = $available->sortBy('id')->first();
        $product->price = $firstAvailable !== null ? (int) $firstAvailable->price : 0;

        $minPercent = (int) $this->settingValue('min', 105);

        if ($product->price > 0 && (($product->price * $minPercent) / 100) < (int) $product->buy_price) {
            $product->stock_status = 'OUT_STOCK';
        } elseif ($product->stock_quantity > 0 && $product->price > 0) {
            $product->stock_status = 'IN_STOCK';
        } elseif ($product->stock_quantity <= 0) {
            $product->stock_status = 'OUT_STOCK';
        }

        $product->save();

        return $product;
    }

    public function syncProductAggregates(Product $product): Product
    {
        $available = $product->quantities()->where('count', '>', 0)->orderBy('id');

        $product->stock_quantity = (int) $available->sum('count');
        $product->price = (int) ($available->clone()->value('price') ?? 0);

        if ($product->stock_quantity <= 0 || $product->price <= 0) {
            $product->stock_status = 'OUT_STOCK';
        }

        $product->save();

        return $product;
    }

    protected function settingValue(string $key, mixed $default = 0): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        $value = $setting->value;

        if ($value === null || $value === '') {
            $value = $setting->raw;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return $value;
    }
}
