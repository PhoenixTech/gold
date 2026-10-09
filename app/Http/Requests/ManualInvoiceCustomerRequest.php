<?php

namespace App\Http\Requests;

use App\Services\ManualInvoiceService;
use Illuminate\Foundation\Http\FormRequest;

class ManualInvoiceCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->hasRole('admin|developer') || $user->hasAnyAccess('invoice'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => self::englishDigits((string) $this->input('mobile')),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => __('Enter the customer mobile number.'),
            'mobile.regex' => __('Enter a valid mobile number.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $mobile = (string) $this->input('mobile');
            if ($mobile === '' || ! preg_match('/^[0-9]{10,15}$/', $mobile)) {
                return;
            }

            $service = app(ManualInvoiceService::class);
            $existing = $service->customerByMobile($mobile);

            if ($existing !== null) {
                return;
            }

            if ($service->mobileBelongsToRemovedCustomer($mobile)) {
                $validator->errors()->add(
                    'mobile',
                    __('This mobile belongs to a removed customer. Restore it from the customers list first.')
                );

                return;
            }

            if ((string) $this->input('name') === '') {
                $validator->errors()->add('name', __('Enter the customer name for a new customer.'));
            }
        });
    }

    private static function englishDigits(string $value): string
    {
        return strtr(preg_replace('/\s+/u', '', $value) ?? '', [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
