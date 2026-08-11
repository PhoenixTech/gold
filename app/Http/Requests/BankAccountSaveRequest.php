<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BankAccountSaveRequest extends FormRequest
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
            'bank_name' => ['required', 'string', 'min:2', 'max:191'],
            'account_holder_name' => ['required', 'string', 'min:2', 'max:191'],
            'card_number' => ['nullable', 'string', 'max:32'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:34'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cardNumber = trim((string) $this->input('card_number', ''));
            $accountNumber = trim((string) $this->input('account_number', ''));
            $iban = trim((string) $this->input('iban', ''));

            if ($cardNumber === '' && $accountNumber === '' && $iban === '') {
                $validator->errors()->add(
                    'card_number',
                    __('At least one of card number, account number, or IBAN is required.')
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_name.required' => __('Bank name is required.'),
            'account_holder_name.required' => __('Account holder name is required.'),
        ];
    }
}
