<?php

namespace App\Services;

use App\Enums\MetalType;
use App\Models\Campaign;
use Illuminate\Support\Collection;

/**
 * Decides which campaign owns the 12th home page cell, per metal.
 *
 * The home page renders the same categories twice (gold tab / silver tab), so
 * resolution happens per metal and yields at most one campaign per metal. When
 * two campaigns overlap, the winner is deterministic: highest `priority` first,
 * then the most recently created one. Nothing random, nothing implicit.
 *
 * The candidate set is deliberately not cached. The `campaigns` table holds a
 * handful of rows per event and the query is three cheap predicates on indexed
 * columns; caching it would only add staleness on the one screen a shop manager
 * is staring at while configuring a campaign. The expensive part — the product
 * set behind each tile — is cached separately in {@see CampaignProductSource}.
 */
class CampaignResolver
{
    /**
     * Every published campaign whose schedule window is currently open,
     * already ordered by the slot precedence.
     *
     * @return Collection<int, Campaign>
     */
    public function candidates(): Collection
    {
        return Campaign::query()
            ->published()
            ->live()
            ->get()
            ->sort(function (Campaign $a, Campaign $b): int {
                return [$b->priority, $b->getKey()] <=> [$a->priority, $a->getKey()];
            })
            ->values();
    }

    /**
     * @param  list<string>  $metals
     * @return array<string, Campaign|null> metal => campaign
     */
    public function forMetals(array $metals): array
    {
        $metals = array_values(array_unique($metals));

        if ($metals === []) {
            return [];
        }

        $candidates = $this->candidates();

        $resolved = [];

        foreach ($metals as $metal) {
            $resolved[$metal] = $candidates->first(
                fn (Campaign $campaign) => $campaign->appliesToMetal($metal)
            );
        }

        return $resolved;
    }

    public function forMetal(string $metal): ?Campaign
    {
        return $this->forMetals([$metal])[$metal] ?? null;
    }

    /**
     * The campaign owning the event slot of every home page tab.
     *
     * @return array<string, Campaign|null>
     */
    public function forHome(): array
    {
        return $this->forMetals(MetalType::values());
    }
}
