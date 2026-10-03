<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Services\InvoiceWorkflow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceSaveRequest extends FormRequest
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
            'delivery_type' => ['prohibited'],
            'transport_id' => ['prohibited'],
            'status' => ['required', 'string', Rule::in(InvoiceWorkflow::fulfillmentStatuses())],
            'courier_id' => [
                Rule::requiredIf(fn () => $this->input('status') === Invoice::OUT_FOR_DELIVERY),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'COURIER'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => __('Status is required.'),
            'status.in' => __('The selected invoice status is invalid.'),
            'courier_id.required' => __('Select a courier for this delivery.'),
            'courier_id.exists' => __('Select a courier for this delivery.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $invoice = $this->invoiceFromRoute();
            if ($invoice === null) {
                return;
            }

            $status = $this->input('status');

            if ($status === Invoice::READY_FOR_PICKUP && ! $invoice->isPickup()) {
                $validator->errors()->add(
                    'status',
                    __('Only store pickup invoices can be marked ready for pickup.')
                );

                return;
            }

            if ($invoice->isPickup() && $status === Invoice::OUT_FOR_DELIVERY) {
                $validator->errors()->add(
                    'status',
                    __('Store pickup invoices cannot be sent for motorcycle delivery.')
                );

                return;
            }

            if ($invoice->isPickup() && $status === Invoice::COMPLETED && $invoice->status !== Invoice::READY_FOR_PICKUP) {
                $validator->errors()->add(
                    'status',
                    __('Pickup orders must be marked ready before they can be completed.')
                );

                return;
            }

            if ($invoice->isPickup() && $status === Invoice::READY_FOR_PICKUP
                && ! in_array($invoice->status, [Invoice::PAID, Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, Invoice::READY_FOR_PICKUP], true)) {
                $validator->errors()->add(
                    'status',
                    __('Pickup orders can only be marked ready after payment is confirmed.')
                );

                return;
            }
        });
    }

    private function invoiceFromRoute(): ?Invoice
    {
        $item = $this->route('item');
        if ($item instanceof Invoice) {
            return $item;
        }

        if (! is_string($item) && ! is_int($item)) {
            return null;
        }

        if (is_numeric($item)) {
            $invoice = Invoice::query()->find($item);
            if ($invoice !== null) {
                return $invoice;
            }
        }

        return Invoice::query()->where('hash', $item)->first();
    }
}
