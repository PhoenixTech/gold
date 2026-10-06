<?php

namespace App\Services;

use App\Enums\CampaignProductRole;
use App\Enums\MetalType;
use App\Enums\Occasion;
use App\Http\Requests\CampaignSaveRequest;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        private readonly AdminMediaService $media
    ) {}

    public function save(Campaign $campaign, CampaignSaveRequest $request, SlugService $slug): Campaign
    {
        return DB::transaction(function () use ($campaign, $request, $slug) {
            $this->fillFromRequest($campaign, $request, $slug);
            $this->handleUploads($campaign, $request);
            $campaign->save();

            $this->syncProductLinks($campaign, $request);

            return $campaign;
        });
    }

    public function formData(?Campaign $campaign = null): array
    {
        $includedIds = [];
        $excludedIds = [];
        $clashes = collect();

        if ($campaign && $campaign->exists) {
            $links = $campaign->productLinks()->get(['product_id', 'role', 'sort']);
            $includedIds = $links->where('role', CampaignProductRole::Include->value)
                ->sortBy('sort')
                ->pluck('product_id')
                ->all();
            $excludedIds = $links->where('role', CampaignProductRole::Exclude->value)
                ->pluck('product_id')
                ->all();
            $clashes = $campaign->clashingCampaigns();
        }

        $allSelected = array_merge(
            (array) old('included_products', $includedIds),
            (array) old('excluded_products', $excludedIds)
        );

        $productOptions = Product::query()
            ->published()
            ->where(function ($q) use ($allSelected) {
                if ($allSelected !== []) {
                    $q->whereIn('id', $allSelected);
                }
            })
            ->orWhere(function ($q) {
                $q->published()->orderByDesc('id')->limit(500);
            })
            ->get(['id', 'name', 'sku', 'metal_type'])
            ->unique('id')
            ->values();

        return [
            'includedIds' => $includedIds,
            'excludedIds' => $excludedIds,
            'includedValue' => json_encode(old('included_products', $includedIds)),
            'excludedValue' => json_encode(old('excluded_products', $excludedIds)),
            'productOptions' => $productOptions,
            'clashes' => $clashes,
        ];
    }

    public function fillFromRequest(
        Campaign $campaign,
        CampaignSaveRequest $request,
        SlugService $slug
    ): Campaign {
        $campaign->name = $request->input('name');
        $campaign->subtitle = $request->input('subtitle');
        $campaign->description = $request->input('description');
        $campaign->badge_text = $request->input('badge_text');

        $campaign->status = $request->input('status');
        $campaign->priority = (int) ($request->input('priority') ?? 10);
        $campaign->limit = (int) ($request->input('limit') ?? 6);

        $campaign->starts_at = $request->filled('starts_at') ? $request->input('starts_at') : null;
        $campaign->ends_at = $request->filled('ends_at') ? $request->input('ends_at') : null;

        // Unknown occasion keys are dropped rather than stored, so a retired
        // occasion can never leak into a storefront query.
        $campaign->occasions = Occasion::normalize($request->input('occasions', []));

        // An empty scope means "every tab", which `appliesToMetal` reads as null
        // rather than an empty array.
        $metals = array_values(array_intersect((array) $request->input('metal_scope', []), MetalType::values()));
        $campaign->metal_scope = $metals === [] ? null : $metals;

        if ($request->filled('canonical')) {
            $campaign->canonical = $request->input('canonical');
        }

        $campaign->slug = $slug->makeUnique(
            Campaign::class,
            $request->filled('slug') ? $request->input('slug') : (string) $request->input('name'),
            $campaign->id
        );

        return $campaign;
    }

    /**
     * Replace the include/exclude rows, preserving the admin's pick order as the
     * `sort` column.
     */
    public function syncProductLinks(Campaign $campaign, CampaignSaveRequest $request): void
    {
        $rows = array_merge(
            $this->rows($request->input('included_products', []), CampaignProductRole::Include),
            $this->rows($request->input('excluded_products', []), CampaignProductRole::Exclude)
        );

        DB::transaction(function () use ($campaign, $rows): void {
            $campaign->productLinks()->delete();
            $campaign->productLinks()->createMany($rows);
        });

        // A query-builder delete fires no model events, so an empty include list
        // would otherwise leave the cached tile untouched.
        CampaignCache::flush();
    }

    /**
     * The optional artwork of the tile. When an image is uploaded it takes the
     * place of the product thumbnail stack, so an event can be art-directed
     * instead of being purely catalogue driven.
     */
    public function handleUploads(Campaign $campaign, CampaignSaveRequest $request): void
    {
        foreach (['image', 'mobile_image'] as $field) {
            $this->media->handleOptimizedImage($request, $campaign, $field, 'campaigns');
        }
    }

    /**
     * @return list<array{product_id:int, role:string, sort:int}>
     */
    private function rows(mixed $ids, CampaignProductRole $role): array
    {
        $rows = [];

        foreach (array_values(array_unique(array_map('intval', (array) $ids))) as $position => $id) {
            if ($id > 0) {
                $rows[] = ['product_id' => $id, 'role' => $role->value, 'sort' => $position];
            }
        }

        return $rows;
    }
}
