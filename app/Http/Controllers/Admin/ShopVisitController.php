<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Models\ShopVisit;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopVisitController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(ShopVisit::query()->with(['user', 'state', 'city'])->completed())
            ->columns(['mobile', 'first_name', 'last_name', 'has_purchase', 'mall', 'user_id', 'submitted_at'], ['id', 'created_at'])
            ->searchable(['mobile', 'first_name', 'last_name', 'mall', 'address'])
            ->buttons([
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.shop-visits.shop-visit-list', $tableData);
    }

    public function show($item): View
    {
        $visit = ShopVisit::query()
            ->with(['user', 'state', 'city'])
            ->where('id', $item)
            ->firstOrFail();

        return view('admin.shop-visits.shop-visit-show', ['item' => $visit]);
    }

    public function destroy(ShopVisit|string|int $item): RedirectResponse
    {
        $visit = $item instanceof ShopVisit ? $item : ShopVisit::where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, ShopVisit::class, $visit->id);
        $visit->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse|StreamedResponse
    {
        $action = (string) $request->input('action');
        $ids = (array) $request->input('id', []);

        if ($action === 'export') {
            return $this->export(count($ids) > 0 ? array_values($ids) : null);
        }

        return $bulkService->handle(ShopVisit::class, $action, $ids);
    }

    public function export(?array $ids = null): StreamedResponse
    {
        $filename = 'shop-visits-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($ids): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'mobile',
                'first_name',
                'last_name',
                'has_purchase',
                'has_own_workshop',
                'other_reason',
                'categories',
                'work_styles',
                'province',
                'city',
                'mall',
                'address',
                'visitor',
                'submitted_at',
            ]);

            $query = ShopVisit::query()
                ->with(['user', 'state', 'city'])
                ->completed();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            $query->orderByDesc('id')
                ->chunk(200, function ($visits) use ($handle): void {
                    foreach ($visits as $visit) {
                        fputcsv($handle, [
                            $visit->mobile,
                            $visit->first_name,
                            $visit->last_name,
                            $visit->has_purchase ? __('Yes') : __('No'),
                            $visit->has_own_workshop ? __('Yes') : __('No'),
                            $visit->other_reason,
                            implode('|', $visit->categoryLabels()),
                            implode('|', $visit->workStyleLabels()),
                            $visit->state?->name,
                            $visit->city?->name,
                            $visit->mall,
                            $visit->address,
                            $visit->user?->name,
                            $visit->submittedAtLabel(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
