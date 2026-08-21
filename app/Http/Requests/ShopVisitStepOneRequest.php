<?php

namespace App\Http\Requests;

use App\Models\ShopVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShopVisitStepOneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isVisitor();
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'mobile' => ShopVisit::toEnglishDigits($this->input('mobile')),
            'has_own_workshop' => $this->boolean('has_own_workshop'),
        ];

        if ($this->exists('has_purchase')) {
            $payload['has_purchase'] = $this->boolean('has_purchase');
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'has_purchase' => ['required', 'boolean'],
            'has_own_workshop' => ['sometimes', 'boolean'],
            'other_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile.required' => __('Mobile number is required.'),
            'mobile.regex' => __('Enter a valid Iranian mobile number.'),
            'first_name.required' => __('First name is required.'),
            'last_name.required' => __('Last name is required.'),
            'has_purchase.required' => __('Please choose whether they buy.'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('has_purchase')) {
                return;
            }

            $hasWorkshop = $this->boolean('has_own_workshop');
            $otherReason = trim((string) $this->input('other_reason', ''));
            if (! $hasWorkshop && $otherReason === '') {
                $validator->errors()->add(
                    'other_reason',
                    __('Choose the workshop reason or explain why they do not buy.')
                );
            }
        });
    }
}
