<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentReceiptsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    /**
     * Every receipt needs a slip and an amount: an empty row would flip the
     * invoice to "waiting for confirmation", dodge the expiry job and hold
     * the reserved stock forever while the admin can never approve it.
     */
    public function rules(): array
    {
        return [
            'receipts' => ['required', 'array', 'min:1', 'max:10'],
            'receipts.*' => ['array'],
            'receipts.*.amount' => ['required', 'integer', 'min:1'],
            'receipts.*.payment_date' => ['nullable', 'string', 'max:255'],
            'receipts.*.payment_time' => ['nullable', 'string', 'max:255'],
            'receipts.*.tracking_number' => ['nullable', 'string', 'max:255'],
            'receipts.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'receipts.*.slip' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipts.required' => __('Please enter at least one payment receipt.'),
            'receipts.*.amount.required' => __('Enter the amount of each receipt.'),
            'receipts.*.amount.min' => __('Enter the amount of each receipt.'),
            'receipts.*.slip.required' => __('Attach the receipt image for each payment.'),
            'receipts.*.slip.mimes' => __('Receipts must be images or PDF files.'),
            'receipts.*.slip.max' => __('Each receipt may not be larger than 5MB.'),
        ];
    }
}
