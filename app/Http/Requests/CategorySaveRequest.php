<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategorySaveRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:128'],
            'code' => ['nullable', 'string', 'max:10'],
            'subtitle' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp'],
            'silver_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp'],
            'bg' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'canonical' => ['nullable', 'url', 'min:5', 'max:128'],
        ];
    }
}
