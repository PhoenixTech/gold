<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceCancellationService
{
    public function __construct(
        protected DeliveryService $deliveries,
        protected CreditService $credits,
    ) {}

    /**
     * @return int the amount returned to the customer credit (0 when nothing was paid)
     */
    public function cancel(Invoice $invoice, ?string $reason, ?User $admin = null): int
    {
        return DB::transaction(function () use ($invoice, $reason, $admin): int {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($locked->status, InvoiceWorkflow::cancelableStatuses(), true)) {
                throw ValidationException::withMessages([
                    'status' => __('This invoice can no longer be canceled.'),
                ]);
            }

            $meta = $locked->meta ?? [];
            $alreadyRefunded = (int) ($meta['refunded_amount'] ?? 0) > 0;
            $shouldRefund = in_array($locked->status, InvoiceWorkflow::refundableStatuses(), true)
                && ! $alreadyRefunded;
            $amount = $shouldRefund ? (int) $locked->total_price : 0;

            $this->deliveries->applyAdminStatus($locked, Invoice::CANCELED, null);

            if ($amount > 0 && $locked->customer !== null) {
                $this->credits->refund(
                    $locked->customer,
                    $amount,
                    $locked,
                    __('Refund for canceled invoice :hash', ['hash' => $locked->hash]),
                    $admin
                );
            }

            $meta['canceled_at'] = now()->toDateTimeString();
            $meta['cancel_reason'] = $reason;
            $meta['canceled_by'] = $admin?->id;
            if ($amount > 0) {
                $meta['refunded_amount'] = $amount;
            }
            $locked->meta = $meta;
            $locked->save();

            $invoice->setRawAttributes($locked->getAttributes(), true);

            return $amount;
        });
    }
}
