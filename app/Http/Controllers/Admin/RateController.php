<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\RateSaveRequest;
use App\Models\Rate;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Rate::class)
            ->columns(['rateable_type', 'rateable_id', 'rater_type', 'rater_id', 'rate', 'evaluation_id'], ['id'])
            ->searchable(['rate', 'rateable_type', 'rateable_id', 'rater_type', 'rater_id'])
            ->buttons([])
            ->build($request);

        return view('admin.rates.rate-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.rates.rate-form');
    }

    public function store(RateSaveRequest $request): JsonResponse|RedirectResponse
    {
        $rate = new Rate;
        $rate->fill($request->validated());
        $rate->save();

        logAdmin(__METHOD__, Rate::class, $rate->id);

        return $this->respondAfterSave($request, $rate, __('As you wished created successfully'), 'admin.rate.index');
    }

    public function edit(Rate|string|int $item): View
    {
        $item = $this->resolveRate($item);

        return view('admin.rates.rate-form', compact('item'));
    }

    public function update(RateSaveRequest $request, Rate|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveRate($item);
        $item->fill($request->validated());
        $item->save();

        logAdmin(__METHOD__, Rate::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.rate.index');
    }

    public function destroy(Rate|string|int $item): RedirectResponse
    {
        $item = $this->resolveRate($item);

        logAdmin(__METHOD__, Rate::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Rate::class, $request->input('action'), (array) $request->input('id', []));
    }

    protected function resolveRate(Rate|string|int $item): Rate
    {
        if ($item instanceof Rate) {
            return $item;
        }

        return Rate::where('id', $item)->firstOrFail();
    }
}
