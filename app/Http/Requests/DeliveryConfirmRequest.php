<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCourier();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('code')) {
            return;
        }

        $this->merge([
            'code' => $this->toEnglishDigits((string) $this->input('code')),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('Enter the 4-digit code the customer received by SMS.'),
            'code.size' => __('Enter the 4-digit code the customer received by SMS.'),
            'code.regex' => __('Enter the 4-digit code the customer received by SMS.'),
        ];
    }

    private function toEnglishDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $value = str_replace($persian, $english, $value);
        $value = str_replace($arabic, $english, $value);

        return preg_replace('/\s+/', '', $value) ?? '';
    }
}
