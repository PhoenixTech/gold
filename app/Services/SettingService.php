<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingService
{
    protected static ?Collection $settings = null;

    public function clearCache(): void
    {
        self::$settings = null;
    }

    public function all(bool $fresh = false): Collection
    {
        if ($fresh || self::$settings === null) {
            try {
                if (! Schema::hasTable('settings')) {
                    self::$settings = collect();

                    return self::$settings;
                }
                self::$settings = Setting::all()->keyBy('key');
            } catch (Throwable) {
                self::$settings = collect();
            }
        }

        return self::$settings;
    }

    public function get(string $key): mixed
    {
        $settings = $this->all();
        if ($settings->isEmpty() && ! Schema::hasTable('settings')) {
            return false;
        }

        $x = $settings->get($key);
        if ($x === null) {
            return '';
        }

        $txtType = ['TEXT', 'LONGTEXT', 'EDITOR'];
        if (config('app.xlang') && ! in_array($x->type, $txtType, true)) {
            return $x->raw;
        }

        return $x->value;
    }

    public function group(string $group): array
    {
        $settings = $this->all();
        $result = [];

        foreach ($settings as $r) {
            if (str_starts_with($r->key, $group) && $r->value !== null && $r->value !== '') {
                $result[substr($r->key, mb_strlen($group))] = $r->value;
            }
        }

        return $result;
    }
}
