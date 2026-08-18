<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceSaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'tracking_code' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Invoice::editableStatuses())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => __('Status is required.'),
            'status.in' => __('The selected invoice status is invalid.'),
        ];
    }
}
