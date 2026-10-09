<?php

namespace App\Http\Requests;

use App\Enums\ShopPaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->hasRole('admin|developer') || $user->hasAnyAccess('invoice'));
    }

    protected function prepareForValidation(): void
    {
        $rawAmount = self::cleanDigits((string) $this->input('amount'));

        $this->merge([
            'amount' => $rawAmount !== '' ? (int) $rawAmount : null,
            'tracking_number' => trim(self::cleanDigits((string) $this->input('tracking_number'))),
            'payment_date' => trim(self::cleanDigits((string) $this->input('payment_date'))),
            'payment_time' => trim(self::cleanDigits((string) $this->input('payment_time'))),
            'note' => trim((string) $this->input('note')),
        ]);
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::enum(ShopPaymentMethod::class)],
            'amount' => ['required', 'integer', 'min:1000'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'string', 'max:50'],
            'payment_time' => ['nullable', 'string', 'max:50'],
            'slip' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => __('Choose a payment method.'),
            'method.enum' => __('Choose a payment method.'),
            'amount.required' => __('Enter the payment amount.'),
            'amount.integer' => __('Payment amount must be a number.'),
            'amount.min' => __('Payment amount must be at least 1,000 Toman.'),
            'supplier_id.exists' => __('The selected supplier does not exist.'),
            'bank_account_id.exists' => __('The selected bank account does not exist.'),
            'slip.mimes' => __('Receipts must be images or PDF files.'),
            'slip.max' => __('Each receipt may not be larger than 5MB.'),
        ];
    }

    private static function cleanDigits(string $value): string
    {
        $digits = strtr(preg_replace('/\s+/u', '', $value) ?? '', [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return str_replace(',', '', $digits);
    }
}
