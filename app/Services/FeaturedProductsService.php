<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Tags\Tag;

/**
 * Builds the product grid of the storefront home page.
 *
 * The grid is driven by tags: the admin picks up to MAX_TAGS tags in the
 * "index_FeaturedProducts_tags" setting and every published product carrying
 * any of them is shown. With no tag selected the newest products of the shop
 * are shown, so the section is never empty on a fresh install.
 */
class FeaturedProductsService
{
    public const SETTING_KEY = 'index_FeaturedProducts_tags';

    public const TAG_TYPE = 'product';

    public const MAX_TAGS = 3;

    public const DEFAULT_LIMIT = 8;

    public function query(?string $settingKey = null): Builder
    {
        $tags = $this->tags($settingKey);

        return Product::query()
            ->published()
            ->with(['category', 'availableQuantities', 'activeDiscounts', 'media'])
            ->when($tags->isNotEmpty(), fn (Builder $query) => $query->withAnyTags($tags))
            ->orderByDesc('id');
    }

    public function get(?int $limit = null, ?string $settingKey = null): Collection
    {
        return $this->query($settingKey)
            ->take($limit ?? self::DEFAULT_LIMIT)
            ->get();
    }

    /**
     * The tags selected by the admin, in the order they were picked and
     * capped to MAX_TAGS.
     */
    public function tags(?string $settingKey = null): Collection
    {
        $ids = $this->tagIds($settingKey);

        if ($ids === []) {
            return collect();
        }

        return Tag::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Tag $tag) => array_search($tag->id, $ids, true))
            ->values();
    }

    /**
     * @return list<int>
     */
    public function tagIds(?string $settingKey = null): array
    {
        $value = getSetting($settingKey ?? self::SETTING_KEY);

        $ids = match (true) {
            is_string($value) => json_decode($value, true),
            is_array($value) => $value,
            default => null,
        };

        return collect($ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(self::MAX_TAGS)
            ->values()
            ->all();
    }
}
