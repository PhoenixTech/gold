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
     * The receipt screen posts two shapes:
     *   - structured: receipts[0][amount|slip|payment_date|...]
     *   - flat:       receipts[]  (bare files, no split-payment metadata)
     * Detect which one arrived and validate accordingly.
     */
    public function isStructured(): bool
    {
        $raw = $this->input('receipts');

        if ($raw === null) {
            $raw = $this->file('receipts') ?? ($this->all()['receipts'] ?? []);
        }

        return is_array($raw) && isset($raw[0]) && is_array($raw[0]);
    }

    public function rules(): array
    {
        if ($this->isStructured()) {
            return [
                'receipts' => ['required', 'array', 'min:1', 'max:10'],
                'receipts.*.amount' => ['nullable', 'numeric'],
                'receipts.*.payment_date' => ['nullable', 'string', 'max:255'],
                'receipts.*.payment_time' => ['nullable', 'string', 'max:255'],
                'receipts.*.tracking_number' => ['nullable', 'string', 'max:255'],
                'receipts.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
                'receipts.*.slip' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            ];
        }

        return [
            'receipts' => ['required', 'array', 'min:1', 'max:10'],
            'receipts.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        if ($this->isStructured()) {
            return [
                'receipts.required' => __('Please enter at least one payment receipt.'),
                'receipts.*.slip.mimes' => __('Receipts must be images or PDF files.'),
                'receipts.*.slip.max' => __('Each receipt may not be larger than 5MB.'),
            ];
        }

        return [
            'receipts.required' => __('Please select at least one receipt file.'),
            'receipts.*.mimes' => __('Receipts must be images or PDF files.'),
            'receipts.*.max' => __('Each receipt may not be larger than 5MB.'),
        ];
    }
}
