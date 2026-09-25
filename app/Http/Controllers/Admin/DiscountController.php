<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountSaveRequest;
use App\Models\Discount;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Discount::class)
            ->columns(['title', 'code', 'expire', 'product_id'], ['id'])
            ->searchable(['title', 'code', 'body'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.discounts.discount-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.discounts.discount-form');
    }

    public function store(DiscountSaveRequest $request): JsonResponse|RedirectResponse
    {
        $discount = new Discount;
        $this->saveDiscountData($discount, $request);

        logAdmin(__METHOD__, Discount::class, $discount->id);

        return $this->respondAfterSave($request, $discount, __('As you wished created successfully'), 'admin.discount.edit');
    }

    public function edit(Discount|string|int $item): View
    {
        $item = $this->resolveDiscount($item);

        return view('admin.discounts.discount-form', compact('item'));
    }

    public function update(DiscountSaveRequest $request, Discount|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveDiscount($item);
        $this->saveDiscountData($item, $request);

        logAdmin(__METHOD__, Discount::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.discount.edit');
    }

    public function destroy(Discount|string|int $item): RedirectResponse
    {
        $item = $this->resolveDiscount($item);

        logAdmin(__METHOD__, Discount::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Discount::onlyTrashed())
            ->columns(['title', 'code', 'expire', 'product_id'], ['id', 'deleted_at'])
            ->searchable(['title', 'code', 'body'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.discounts.discount-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Discount::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Discount::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Discount::class, $request->input('action'), (array) $request->input('id', []));
    }

    protected function resolveDiscount(Discount|string|int $item): Discount
    {
        if ($item instanceof Discount) {
            return $item;
        }

        return Discount::where('id', $item)->firstOrFail();
    }

    protected function saveDiscountData(Discount $discount, Request $request): void
    {
        $discount->product_id = $request->filled('product_id') ? $request->input('product_id') : null;
        $discount->title = $request->input('title');
        $discount->body = $request->input('body');
        $discount->amount = $request->input('amount');
        $discount->expire = $request->filled('expire') ? date('Y-m-d H:i:s', floor((float) $request->input('expire'))) : null;
        $discount->code = $request->input('code');
        $discount->type = $request->input('type');
        $discount->save();
    }
}
