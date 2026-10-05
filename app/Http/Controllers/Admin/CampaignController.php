<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignSaveRequest;
use App\Models\Campaign;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\CampaignProductSource;
use App\Services\CampaignService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Campaign::query())
            ->columns(['name', 'status', 'schedule', 'occasions', 'metal_scope'], ['id', 'slug', 'priority', 'starts_at', 'ends_at'])
            ->searchable(['name', 'slug', 'subtitle'])
            ->columnLabels([
                'name' => 'Campaign',
                'schedule' => 'Schedule',
                'occasions' => 'Occasions',
                'metal_scope' => 'Tabs',
            ])
            // "schedule" is computed from the dates, so it cannot be ordered by name.
            ->withCustomSort(function ($query, $sort, $sortType): bool {
                if ($sort !== 'schedule') {
                    return false;
                }

                $query->orderBy('starts_at', $sortType)->orderBy('id', $sortType);

                return true;
            })
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.campaigns.campaign-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.campaigns.campaign-form');
    }

    public function store(CampaignSaveRequest $request, CampaignService $service, SlugService $slug): JsonResponse|RedirectResponse
    {
        $campaign = new Campaign;

        $service->fillFromRequest($campaign, $request, $slug);
        $campaign->save();

        $service->handleUploads($campaign, $request);
        $campaign->save();

        $service->syncProductLinks($campaign, $request);

        logAdmin(__METHOD__, Campaign::class, $campaign->id);

        return $this->respondAfterSave($request, $campaign, __('As you wished created successfully'), 'admin.campaign.edit');
    }

    public function edit(Campaign|string|int $item, CampaignProductSource $source): View
    {
        $item = $this->resolveCampaign($item);

        return view('admin.campaigns.campaign-form', [
            'item' => $item,
            'preview' => $source->preview($item, 'gold'),
        ]);
    }

    public function update(CampaignSaveRequest $request, Campaign|string|int $item, CampaignService $service, SlugService $slug): JsonResponse|RedirectResponse
    {
        $item = $this->resolveCampaign($item);

        $service->fillFromRequest($item, $request, $slug);
        $item->save();

        $service->handleUploads($item, $request);
        $item->save();

        $service->syncProductLinks($item, $request);

        logAdmin(__METHOD__, Campaign::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.campaign.edit');
    }

    /**
     * End a campaign right now, freeing the slot immediately instead of waiting
     * for `ends_at`. The model hook handles cache invalidation.
     */
    public function endNow(Campaign|string|int $item): RedirectResponse
    {
        $campaign = $this->resolveCampaign($item);

        logAdmin(__METHOD__, Campaign::class, $campaign->id);

        $campaign->update([
            'ends_at' => now()->subSecond(),
            'status' => CampaignStatus::Disabled,
        ]);

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function destroy(Campaign|string|int $item): RedirectResponse
    {
        $campaign = $this->resolveCampaign($item);

        logAdmin(__METHOD__, Campaign::class, $campaign->id);
        $campaign->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Campaign::onlyTrashed())
            ->columns(['name', 'status', 'schedule'], ['id', 'slug', 'starts_at', 'ends_at', 'deleted_at'])
            ->searchable(['name', 'slug'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.campaigns.campaign-list', $tableData);
    }

    public function restore(Campaign|string|int $item): RedirectResponse
    {
        $target = Campaign::withTrashed()->where('slug', $item)->first();

        abort_if($target === null, 404);

        logAdmin(__METHOD__, Campaign::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Campaign::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action !== 'end-now') {
                    return null;
                }

                Campaign::query()->whereKey($ids)->get()->each(function (Campaign $campaign): void {
                    logAdmin(__METHOD__, Campaign::class, $campaign->id);
                    $campaign->update([
                        'ends_at' => now()->subSecond(),
                        'status' => CampaignStatus::Disabled,
                    ]);
                });

                return __(':COUNT campaigns ended successfully', ['COUNT' => count($ids)]);
            }
        );
    }

    protected function resolveCampaign(Campaign|string|int $item): Campaign
    {
        return $this->resolveModel(Campaign::class, $item);
    }
}
