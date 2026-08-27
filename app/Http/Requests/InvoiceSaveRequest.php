<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\Transport;
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
            'courier_id' => [
                Rule::requiredIf(fn () => $this->input('status') === Invoice::OUT_FOR_DELIVERY),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'COURIER'),
            ],
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
            'courier_id.required' => __('Select a courier for this delivery.'),
            'courier_id.exists' => __('Select a courier for this delivery.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('status') !== Invoice::OUT_FOR_DELIVERY) {
                return;
            }

            $transport = Transport::query()->find($this->input('transport_id'));
            if ($transport === null || ! $transport->requires_delivery_code) {
                $validator->errors()->add(
                    'status',
                    __('Motorcycle delivery is only available for courier transports.')
                );
            }
        });
    }
}
