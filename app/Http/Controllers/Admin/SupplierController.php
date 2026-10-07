<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierSaveRequest;
use App\Models\Supplier;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $this->authorizeSupplierAccess();

        $tableData = $this->buildTable($request, $tableService, Supplier::query(), [
            'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
            'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
        ]);

        return view('admin.suppliers.supplier-list', $tableData);
    }

    public function create(): View
    {
        $this->authorizeSupplierAccess();

        return view('admin.suppliers.supplier-form');
    }

    public function store(SupplierSaveRequest $request): JsonResponse|RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        logAdmin(__METHOD__, Supplier::class, $supplier->id);

        return $this->respondAfterSave($request, $supplier, __('As you wished created successfully'), 'admin.supplier.edit');
    }

    public function edit(Supplier|string|int $item): View
    {
        $this->authorizeSupplierAccess();

        $supplier = $this->resolveSupplier($item);

        return view('admin.suppliers.supplier-form', ['item' => $supplier]);
    }

    public function update(SupplierSaveRequest $request, Supplier|string|int $item): JsonResponse|RedirectResponse
    {
        $supplier = $this->resolveSupplier($item);
        $supplier->update($request->validated());

        logAdmin(__METHOD__, Supplier::class, $supplier->id);

        return $this->respondAfterSave($request, $supplier, __('As you wished updated successfully'), 'admin.supplier.edit');
    }

    public function destroy(Supplier|string|int $item): RedirectResponse
    {
        $this->authorizeSupplierAccess();

        $supplier = $this->resolveSupplier($item);
        $supplier->delete();

        logAdmin(__METHOD__, Supplier::class, $supplier->id);

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $this->authorizeSupplierAccess();

        $tableData = $this->buildTable($request, $tableService, Supplier::onlyTrashed(), [
            'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
        ], ['id', 'deleted_at']);

        return view('admin.suppliers.supplier-list', $tableData);
    }

    public function restore(Supplier|string|int $item): RedirectResponse
    {
        $this->authorizeSupplierAccess();

        $supplier = $this->resolveSupplier($item, true);
        $supplier->restore();

        logAdmin(__METHOD__, Supplier::class, $supplier->id);

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        $this->authorizeSupplierAccess();

        return $bulkService->handle(Supplier::class, $request->input('action'), (array) $request->input('id', []));
    }

    protected function resolveSupplier(Supplier|string|int $item, bool $withTrashed = false): Supplier
    {
        return $this->resolveModel(Supplier::class, $item, $withTrashed);
    }

    private function authorizeSupplierAccess(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->hasRole('admin|developer') || auth()->user()->hasAnyAccess('supplier')),
            403
        );
    }

    private function buildTable(
        Request $request,
        AdminTableService $tableService,
        $source,
        array $buttons,
        array $extraCols = ['id']
    ): array {
        return $tableService->for($source)
            ->columns(['first_name', 'last_name', 'company_name', 'phone', 'account_number', 'iban'], $extraCols)
            ->columnLabels([
                'first_name' => __('First name'),
                'last_name' => __('Last name'),
                'company_name' => __('Company name'),
                'phone' => __('Phone number'),
                'account_number' => __('Account number'),
                'iban' => __('IBAN'),
            ])
            ->searchable(['first_name', 'last_name', 'company_name', 'phone', 'account_number', 'iban'])
            ->buttons($buttons)
            ->build($request);
    }
}
