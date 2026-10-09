<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualInvoiceItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->hasRole('admin|developer') || $user->hasAnyAccess('invoice'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['add', 'remove'])],
            'quantity_ids' => [Rule::requiredIf(fn () => $this->input('action') === 'add'), 'array'],
            'quantity_ids.*' => ['integer', 'exists:quantities,id'],
            'quantity_id' => [Rule::requiredIf(fn () => $this->input('action') === 'remove'), 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_ids.required' => __('Select at least one stock piece.'),
            'quantity_ids.*.exists' => __('Selected stock piece is not available'),
        ];
    }
}
