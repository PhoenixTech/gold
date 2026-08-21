<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopVisitSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('developer|admin') || auth()->user()->hasAnyAccess('shop-visit'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
