<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeclineInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ($user->role === 'ADMIN'
                || $user->role === 'DEVELOPER'
                || $user->hasRole('admin')
                || $user->hasRole('developer')
                || $user->hasAccess('admin.invoice.decline-payment'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => __('Decline reason')];
    }
}
