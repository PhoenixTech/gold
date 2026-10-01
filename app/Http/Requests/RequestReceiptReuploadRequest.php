<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RequestReceiptReuploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ($user->role === 'ADMIN'
                || $user->role === 'DEVELOPER'
                || $user->hasRole('admin')
                || $user->hasRole('developer')
                || $user->hasAccess('admin.invoice.request-receipt-reupload'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => __('Reason for re-upload')];
    }
}
