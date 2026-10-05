<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache store for the home page campaign slot.
 *
 * A single monotonic version number is embedded in every campaign cache key, so
 * busting the whole namespace is one write instead of a wildcard forget (which
 * no cache driver supports). Flushing also has to happen when a product's
 * occasions, metal or publication status change, because those decide which
 * products fill the tile.
 */
class CampaignCache
{
    public const VERSION_KEY = 'home:campaign:version';

    public const TTL = 300;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function key(string $suffix): string
    {
        return 'home:campaign:v'.self::version().':'.$suffix;
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
