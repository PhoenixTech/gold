<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCourier();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => __('Please explain why you are rejecting this delivery.'),
            'reason.min' => __('Please explain why you are rejecting this delivery.'),
        ];
    }
}
