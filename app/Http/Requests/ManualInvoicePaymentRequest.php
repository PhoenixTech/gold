<?php

namespace App\Http\Requests;

use App\Enums\ShopPaymentMethod;
use App\Services\ManualInvoiceDraft;
use App\Services\ManualInvoiceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->hasRole('admin|developer') || $user->hasAnyAccess('invoice'));
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('payments');
        if (! is_array($raw)) {
            $raw = [];
        }

        $cleaned = [];
        foreach ($raw as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawAmount = self::cleanDigits((string) ($item['amount'] ?? ''));
            $method = trim((string) ($item['method'] ?? ''));
            $supplierId = ! empty($item['supplier_id']) ? (int) $item['supplier_id'] : null;
            $bankAccountId = ! empty($item['bank_account_id']) ? (int) $item['bank_account_id'] : null;
            $trackingNumber = trim(self::cleanDigits((string) ($item['tracking_number'] ?? '')));
            $paymentDate = trim(self::cleanDigits((string) ($item['payment_date'] ?? '')));
            $paymentTime = trim(self::cleanDigits((string) ($item['payment_time'] ?? '')));

            if ($rawAmount === '' && $method === '' && $supplierId === null && $bankAccountId === null) {
                continue;
            }

            $cleaned[] = [
                'method' => $method ?: ShopPaymentMethod::Pos->value,
                'amount' => $rawAmount !== '' ? (int) $rawAmount : null,
                'supplier_id' => $supplierId,
                'bank_account_id' => $bankAccountId,
                'tracking_number' => $trackingNumber ?: null,
                'payment_date' => $paymentDate ?: null,
                'payment_time' => $paymentTime ?: null,
            ];
        }

        $this->merge([
            'payments' => $cleaned,
            'note' => trim((string) $this->input('note')),
        ]);
    }

    public function rules(): array
    {
        return [
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required', Rule::enum(ShopPaymentMethod::class)],
            'payments.*.amount' => ['required', 'integer', 'min:1000'],
            'payments.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'payments.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'payments.*.tracking_number' => ['nullable', 'string', 'max:255'],
            'payments.*.payment_date' => ['nullable', 'string', 'max:50'],
            'payments.*.payment_time' => ['nullable', 'string', 'max:50'],
            'payments.*.slip' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            'handover' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'payments.*.method.required' => __('Choose a payment method.'),
            'payments.*.method.enum' => __('Choose a payment method.'),
            'payments.*.amount.required' => __('Enter the payment amount.'),
            'payments.*.amount.integer' => __('Payment amount must be a number.'),
            'payments.*.amount.min' => __('Payment amount must be at least 1,000 Toman.'),
            'payments.*.supplier_id.exists' => __('The selected supplier does not exist.'),
            'payments.*.bank_account_id.exists' => __('The selected bank account does not exist.'),
            'payments.*.slip.mimes' => __('Receipts must be images or PDF files.'),
            'payments.*.slip.max' => __('Each receipt may not be larger than 5MB.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $payments = (array) $this->input('payments', []);
            $paidTotal = array_sum(array_map(fn ($p) => (int) ($p['amount'] ?? 0), $payments));

            $draft = app(ManualInvoiceDraft::class)->get();
            $lines = app(ManualInvoiceService::class)->lines($draft['quantity_ids']);
            $orderTotal = app(ManualInvoiceService::class)->total($lines);

            if ($paidTotal > $orderTotal && $orderTotal > 0) {
                $validator->errors()->add(
                    'payments',
                    __('Total payments (:paid Toman) cannot exceed the invoice total (:total Toman).', [
                        'paid' => number_format($paidTotal),
                        'total' => number_format($orderTotal),
                    ])
                );
            }

            if ($this->boolean('handover') && $paidTotal < $orderTotal) {
                $validator->errors()->add(
                    'handover',
                    __('In-store handover requires the invoice to be fully paid.')
                );
            }
        });
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
