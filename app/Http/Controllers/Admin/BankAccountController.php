<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccountSaveRequest;
use App\Models\BankAccount;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    use RespondsWithAdmin;
    use ResolvesAdminModel;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(BankAccount::class)
            ->columns(['bank_name', 'account_holder_name', 'card_number', 'iban', 'is_active'], ['id'])
            ->searchable(['bank_name', 'account_holder_name', 'card_number', 'account_number', 'iban'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.bank-accounts.bank-account-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.bank-accounts.bank-account-form');
    }

    public function store(BankAccountSaveRequest $request): JsonResponse|RedirectResponse
    {
        $bankAccount = new BankAccount;
        $this->saveAccountData($bankAccount, $request);

        logAdmin(__METHOD__, BankAccount::class, $bankAccount->id);

        return $this->respondAfterSave($request, $bankAccount, __('As you wished created successfully'), 'admin.bank-account.edit');
    }

    public function edit(BankAccount|string|int $item): View
    {
        $item = $this->resolveBankAccount($item);

        return view('admin.bank-accounts.bank-account-form', compact('item'));
    }

    public function update(BankAccountSaveRequest $request, BankAccount|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveBankAccount($item);
        $this->saveAccountData($item, $request);

        logAdmin(__METHOD__, BankAccount::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.bank-account.edit');
    }

    public function destroy(BankAccount|string|int $item): RedirectResponse
    {
        $item = $this->resolveBankAccount($item);

        logAdmin(__METHOD__, BankAccount::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(BankAccount::onlyTrashed())
            ->columns(['bank_name', 'account_holder_name', 'card_number', 'iban', 'is_active'], ['id', 'deleted_at'])
            ->searchable(['bank_name', 'account_holder_name', 'card_number', 'account_number', 'iban'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.bank-accounts.bank-account-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = BankAccount::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, BankAccount::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(BankAccount::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function activate(BankAccount|string|int $item): RedirectResponse
    {
        $account = $this->resolveBankAccount($item);
        BankAccount::query()->where('is_active', true)->update(['is_active' => false]);
        $account->is_active = true;
        $account->save();

        logAdmin(__METHOD__, BankAccount::class, $account->id);

        return redirect()->back()->with(['message' => __('The bank account is activated successfully.')]);
    }

    protected function resolveBankAccount(BankAccount|string|int $item): BankAccount
    {
        return $this->resolveModel(BankAccount::class, $item);
    }

    protected function saveAccountData(BankAccount $bankAccount, Request $request): void
    {
        $bankAccount->bank_name = $request->input('bank_name');
        $bankAccount->account_holder_name = $request->input('account_holder_name');
        $bankAccount->card_number = $request->filled('card_number') ? $request->input('card_number') : null;
        $bankAccount->account_number = $request->filled('account_number') ? $request->input('account_number') : null;
        $bankAccount->iban = $request->filled('iban') ? $request->input('iban') : null;
        $bankAccount->is_active = $request->boolean('is_active');

        if ($bankAccount->is_active) {
            BankAccount::query()
                ->when($bankAccount->id, fn ($query) => $query->where('id', '<>', $bankAccount->id))
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $bankAccount->save();
    }
}
