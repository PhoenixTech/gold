<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Http\Requests\BankAccountSaveRequest;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends XController
{
    protected $cols = ['bank_name', 'account_holder_name', 'card_number', 'iban', 'is_active'];

    protected $extra_cols = ['id'];

    protected $searchable = ['bank_name', 'account_holder_name', 'card_number', 'account_number', 'iban'];

    protected $listView = 'admin.bank-accounts.bank-account-list';

    protected $formView = 'admin.bank-accounts.bank-account-form';

    protected $buttons = [
        'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
        'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
    ];

    public function __construct()
    {
        parent::__construct(BankAccount::class, BankAccountSaveRequest::class);
    }

    /**
     * @param  BankAccount  $bankAccount
     * @param  BankAccountSaveRequest  $request
     */
    public function save($bankAccount, $request): BankAccount
    {
        $bankAccount->bank_name = $request->bank_name;
        $bankAccount->account_holder_name = $request->account_holder_name;
        $bankAccount->card_number = $request->filled('card_number') ? $request->card_number : null;
        $bankAccount->account_number = $request->filled('account_number') ? $request->account_number : null;
        $bankAccount->iban = $request->filled('iban') ? $request->iban : null;
        $bankAccount->is_active = $request->boolean('is_active');

        if ($bankAccount->is_active) {
            BankAccount::query()
                ->when($bankAccount->id, fn ($query) => $query->where('id', '<>', $bankAccount->id))
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $bankAccount->save();

        return $bankAccount;
    }

    public function create()
    {
        return view($this->formView);
    }

    public function edit(BankAccount $item)
    {
        return view($this->formView, compact('item'));
    }

    public function bulk(Request $request)
    {
        $data = explode('.', $request->input('action'));
        $action = $data[0];
        $ids = $request->input('id');
        switch ($action) {
            case 'delete':
                $msg = __(':COUNT items deleted successfully', ['COUNT' => count($ids)]);
                $this->_MODEL_::destroy($ids);
                break;
            case 'restore':
                $msg = __(':COUNT items restored successfully', ['COUNT' => count($ids)]);
                foreach ($ids as $id) {
                    $this->_MODEL_::withTrashed()->find($id)->restore();
                }
                break;
            default:
                $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $action]);
        }

        return $this->do_bulk($msg, $action, $ids);
    }

    public function destroy(BankAccount $item)
    {
        return parent::delete($item);
    }

    public function update(Request $request, BankAccount $item)
    {
        return $this->bringUp($request, $item);
    }

    public function restore($item)
    {
        return parent::restoreing(BankAccount::withTrashed()->where('id', $item)->first());
    }

    public function activate(BankAccount $item)
    {
        BankAccount::query()->where('is_active', true)->update(['is_active' => false]);
        $item->is_active = true;
        $item->save();

        return redirect()->back()->with(['message' => __('Active bank account updated successfully')]);
    }
}
