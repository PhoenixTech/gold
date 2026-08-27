<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryFailRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
