<?php

namespace App\Services;

use App\Enums\CampaignProductRole;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * What a campaign contains.
 *
 *   pinned (hand picked, admin order)
 * + occasion matches (newest first)
 * − excluded
 *   restricted to one metal, so the gold tab shows gold and the silver tab silver
 *
 * The ordered id list is the single source of truth. The home tile, the campaign
 * product page and the admin preview all project from it, so they can never
 * disagree about what the campaign shows.
 *
 * A product holds exactly one role per campaign (the unique index on
 * campaign_id + product_id makes "both included and excluded" impossible), so
 * there is no precedence question to answer.
 *
 * Performance: `products.occasions` is a JSON array, and MySQL can only index
 * JSON_CONTAINS through MEMBER OF, which this project does not use
 * (`Product::scopeFilterCatalog` already scans that column). A campaign asks for
 * a handful of rows out of a few thousand products, so the scan stays cheap and
 * the result is cached. Past roughly 50k products, migrate the column to a
 * `product_occasion` pivot; `CampaignProductSourceTest` pins the behaviour.
 */
class CampaignProductSource
{
    /**
     * Relations the storefront product card needs.
     *
     * @var list<string>
     */
    public const EAGER = ['category', 'availableQuantities', 'activeDiscounts', 'media'];

    /**
     * Ordered ids of the whole campaign, uncapped.
     *
     * @return Collection<int, int>
     */
    public function ids(Campaign $campaign, string $metal): Collection
    {
        return Cache::remember(
            CampaignCache::key("ids:{$campaign->getKey()}:{$metal}"),
            CampaignCache::TTL,
            fn () => $this->pipeline($campaign, $metal)
        );
    }

    /**
     * The products for the 12th home cell, capped at the campaign limit.
     *
     * @return Collection<int, Product>
     */
    public function tile(Campaign $campaign, string $metal): Collection
    {
        return $this->hydrate($this->ids($campaign, $metal)->take($campaign->limit));
    }

    /**
     * The base query of the campaign product page, campaign order included.
     */
    public function query(Campaign $campaign, string $metal): Builder
    {
        return Product::query()
            ->published()
            ->with(self::EAGER)
            ->whereIn('id', $this->ids($campaign, $metal));
    }

    /**
     * The campaign ordered ids, keyed so a caller can sort products back into
     * campaign order without an O(n²) lookup.
     *
     * @return array<int, int>
     */
    public function orderMap(Campaign $campaign, string $metal): array
    {
        return array_flip($this->ids($campaign, $metal)->all());
    }

    /**
     * What the admin form needs: the tile exactly as the storefront will render
     * it, plus how the three inputs contributed to it.
     *
     * @return array{products: Collection<int, Product>, pinnedIds: list<int>, pinned: int, excluded: int, occasions: int, final: int}
     */
    public function preview(Campaign $campaign, string $metal): array
    {
        $ids = $this->ids($campaign, $metal);
        $pinned = $ids->intersect($this->linkIds($campaign, CampaignProductRole::Include));

        return [
            'products' => $this->hydrate($ids->take($campaign->limit)),
            'pinnedIds' => $pinned->values()->all(),
            'pinned' => $pinned->count(),
            'excluded' => $this->linkIds($campaign, CampaignProductRole::Exclude)->count(),
            'occasions' => $ids->diff($pinned)->count(),
            'final' => min($ids->count(), $campaign->limit),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Pipeline
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection<int, int>
     */
    private function pipeline(Campaign $campaign, string $metal): Collection
    {
        $pinned = $this->pinnedIds($campaign, $metal);
        $excluded = $this->linkIds($campaign, CampaignProductRole::Exclude);
        $occasions = $campaign->occasionList();

        $auto = $occasions === []
            ? collect()
            : $this->occasionQuery($occasions, $metal)
                ->whereNotIn('id', $excluded->merge($pinned))
                ->orderByDesc('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        // `unique` because the two inputs are independent sets.
        return $pinned->concat($auto)->unique()->values();
    }

    /**
     * Hand picked products that are still sellable, in the admin's order. A
     * pinned product that was unpublished or moved to the other metal silently
     * drops out rather than leaving a hole in the tile.
     *
     * @return Collection<int, int>
     */
    private function pinnedIds(Campaign $campaign, string $metal): Collection
    {
        $ids = $this->linkIds($campaign, CampaignProductRole::Include);

        if ($ids->isEmpty()) {
            return $ids;
        }

        $order = array_flip($ids->all());

        return Product::query()
            ->published()
            ->where('metal_type', $metal)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $order[$id])
            ->values();
    }

    /**
     * Published products carrying any of the given occasions. OR semantics, so a
     * product tagged with two selected occasions is still matched once.
     *
     * @param  list<Occasion>  $occasions
     */
    private function occasionQuery(array $occasions, string $metal): Builder
    {
        return Product::query()
            ->published()
            ->where('metal_type', $metal)
            ->where(function (Builder $query) use ($occasions): void {
                foreach ($occasions as $occasion) {
                    $query->orWhereJsonContains('occasions', $occasion->value);
                }
            });
    }

    /**
     * Fetch products by id and restore the campaign's order.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, Product>
     */
    private function hydrate(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $order = array_flip($ids->all());

        return Product::query()
            ->with(self::EAGER)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => $order[$product->getKey()] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function linkIds(Campaign $campaign, CampaignProductRole $role): Collection
    {
        return $campaign->productLinks()
            ->where('role', $role->value)
            ->orderBy('sort')
            ->orderBy('id')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
