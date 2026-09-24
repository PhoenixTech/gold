<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Collection;

class MenuService
{
    protected static mixed $primaryMenu = false;

    public function getBySetting(string $key): ?Menu
    {
        $val = app(SettingService::class)->get($key);

        return Menu::find($val);
    }

    public function getItemsBySetting(string $key)
    {
        $menu = $this->getBySetting($key);
        if (! $menu) {
            $menu = Menu::first();
        }

        return ($menu && $menu->items) ? collect($menu->items) : collect();
    }

    public function getPrimaryMenu(bool $fresh = false): ?Menu
    {
        if ($fresh || self::$primaryMenu === false) {
            self::$primaryMenu = Menu::with(['items.dest'])->first();
        }

        return self::$primaryMenu;
    }

    public function clearMenuCache(): void
    {
        self::$primaryMenu = false;
        $this->getPrimaryMenu(true);
    }

    public function getPrimaryMenuItems(bool $fresh = false): Collection
    {
        $menu = $this->getPrimaryMenu($fresh);

        return ($menu && $menu->items) ? collect($menu->items) : collect();
    }
}
