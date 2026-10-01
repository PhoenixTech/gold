<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->canManagePayments();
    }

    public function rules(): array
    {
        return [
            'receipt_info_checked' => ['accepted'],
            'account_selected' => ['accepted'],
            'bank_verified' => ['accepted'],
            'zero_balance' => ['accepted'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'receipt_info_checked' => __('Receipt Info Checked'),
            'account_selected' => __('Account Selected'),
            'bank_verified' => __('Bank Verification'),
            'zero_balance' => __('Zero Balance'),
            'bank_account_id' => __('Destination bank account'),
        ];
    }

    private function canManagePayments(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->role === 'ADMIN'
            || $user->role === 'DEVELOPER'
            || $user->hasRole('admin')
            || $user->hasRole('developer')
            || $user->hasAccess('admin.invoice.confirm-payment');
    }
}
