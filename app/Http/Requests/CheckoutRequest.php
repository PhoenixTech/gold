<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        $isPickup = $this->input('delivery_type', 'address') === 'pickup';

        $rules = [
            'product_id' => ['required', 'array'],
            'product_id.*' => ['required', 'exists:products,id'],
            'count' => ['required', 'array'],
            'count.*' => ['required', 'integer', 'min:1'],
            'quantity_id' => ['nullable', 'array'],
            'delivery_type' => ['nullable', 'in:address,pickup'],
            'payment_method' => ['required', 'in:card'],
            'discount_id' => ['nullable', 'exists:discounts,id'],
            'desc' => ['nullable', 'string'],
        ];

        if (! $isPickup) {
            $rules['address_id'] = ['required', 'exists:addresses,id'];
            $rules['transport_id'] = ['required', 'exists:transports,id'];
            $rules['is_third_party'] = ['nullable', 'boolean'];

            if ($this->boolean('is_third_party')) {
                $rules['recipient_name'] = ['required', 'string', 'min:2', 'max:255'];
                $rules['recipient_mobile'] = ['required', 'string', 'regex:/^09\d{9}$/'];
                $rules['recipient_national_id'] = ['required', 'string', 'regex:/^\d{10}$/'];
            }
        } else {
            $rules['address_id'] = ['nullable'];
            $rules['transport_id'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'recipient_mobile.regex' => __('Recipient mobile number format is invalid'),
            'recipient_national_id.regex' => __('Recipient national ID format is invalid'),
        ];
    }
}
