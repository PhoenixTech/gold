<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Jobs\SendDeliveryCodeSms;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public static function codeCacheKey(Delivery $delivery): string
    {
        return 'delivery.sms_code.'.$delivery->id;
    }

    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function applyAdminStatus(Invoice $invoice, string $newStatus, ?User $courier): void
    {
        $requiresCode = $invoice->requiresDeliveryCode();

        if ($newStatus === Invoice::COMPLETED) {
            $hasOpenDelivery = $invoice->deliveries()->open()->exists();
            if (($requiresCode || $hasOpenDelivery) && ! $invoice->hasSuccessfulDelivery()) {
                throw ValidationException::withMessages([
                    'status' => __('This invoice can only be completed after the courier confirms the delivery code.'),
                ]);
            }
        }

        if ($newStatus === Invoice::OUT_FOR_DELIVERY) {
            if (! $requiresCode) {
                throw ValidationException::withMessages([
                    'status' => __('Motorcycle delivery is only available for courier transports.'),
                ]);
            }
            if ($courier === null || ! $courier->isCourier()) {
                throw ValidationException::withMessages([
                    'courier_id' => __('Select a courier for this delivery.'),
                ]);
            }
            $this->dispatch($invoice, $courier);

            return;
        }

        if ($invoice->status === Invoice::OUT_FOR_DELIVERY && $newStatus !== Invoice::COMPLETED) {
            $this->cancelOpenDeliveries($invoice, __('Cancelled by admin'));
        }

        $invoice->status = $newStatus;
        $invoice->save();
    }

    public function dispatch(Invoice $invoice, User $courier): Delivery
    {
        $open = $invoice->activeDelivery;
        if ($open !== null && $open->belongsToCourier($courier)) {
            $invoice->status = Invoice::OUT_FOR_DELIVERY;
            $invoice->save();

            return $open;
        }

        $code = $this->generateCode();

        $delivery = DB::transaction(function () use ($invoice, $courier, $open, $code): Delivery {
            if ($open !== null) {
                $this->markRejected($open, __('Reassigned to another courier'));
            }

            $created = new Delivery;
            $created->invoice_id = $invoice->id;
            $created->courier_id = $courier->id;
            $created->code_hash = Hash::make($code);
            $created->status = DeliveryStatus::Pending;
            $created->failed_attempts = 0;
            $created->save();

            $invoice->status = Invoice::OUT_FOR_DELIVERY;
            $invoice->save();

            return $created;
        });

        $this->queueSms($delivery, $code);

        return $delivery;
    }

    public function resendCode(Delivery $delivery): void
    {
        if (! $delivery->isOpen()) {
            throw ValidationException::withMessages([
                'delivery' => __('This delivery can no longer receive a new code.'),
            ]);
        }

        $code = $this->generateCode();
        $delivery->code_hash = Hash::make($code);
        $delivery->failed_attempts = 0;
        $delivery->sms_sent_at = null;
        $delivery->save();

        $this->queueSms($delivery, $code);
    }

    public function accept(Delivery $delivery, User $courier): void
    {
        $this->assertOwned($delivery, $courier);

        if (! $delivery->isPending()) {
            throw ValidationException::withMessages([
                'delivery' => __('This delivery can no longer be accepted.'),
            ]);
        }

        $delivery->status = DeliveryStatus::Accepted;
        $delivery->accepted_at = now();
        $delivery->save();
    }

    public function reject(Delivery $delivery, User $courier, string $reason): void
    {
        $this->assertOwned($delivery, $courier);

        if (! $delivery->isOpen()) {
            throw ValidationException::withMessages([
                'delivery' => __('This delivery can no longer be rejected.'),
            ]);
        }

        DB::transaction(function () use ($delivery, $reason): void {
            $this->markRejected($delivery, $reason);
            $invoice = $delivery->invoice;
            $invoice->status = Invoice::PROCESSING;
            $invoice->save();
        });
    }

    public function fail(Delivery $delivery, User $courier, ?string $reason = null): void
    {
        $this->assertOwned($delivery, $courier);

        if (! $delivery->isAccepted()) {
            throw ValidationException::withMessages([
                'delivery' => __('Only an accepted delivery can be marked as failed.'),
            ]);
        }

        DB::transaction(function () use ($delivery, $reason): void {
            $delivery->status = DeliveryStatus::Failed;
            $delivery->failed_at = now();
            $delivery->reject_reason = $reason;
            $delivery->save();

            $invoice = $delivery->invoice;
            $invoice->status = Invoice::PROCESSING;
            $invoice->save();
        });
    }

    public function confirm(Delivery $delivery, User $courier, string $code): void
    {
        $this->assertOwned($delivery, $courier);

        if (! $delivery->isAccepted()) {
            throw ValidationException::withMessages([
                'code' => __('Accept the delivery before confirming the code.'),
            ]);
        }

        if ($delivery->isLocked()) {
            throw ValidationException::withMessages([
                'code' => __('Too many incorrect attempts. Ask the admin to send a new code.'),
            ]);
        }

        if (! Hash::check($code, $delivery->code_hash)) {
            $delivery->increment('failed_attempts');
            $delivery->refresh();

            if ($delivery->isLocked()) {
                throw ValidationException::withMessages([
                    'code' => __('Too many incorrect attempts. Ask the admin to send a new code.'),
                ]);
            }

            $remaining = Delivery::MAX_PIN_ATTEMPTS - $delivery->failed_attempts;

            throw ValidationException::withMessages([
                'code' => __('The delivery code is incorrect. :count attempts remaining.', ['count' => $remaining]),
            ]);
        }

        DB::transaction(function () use ($delivery): void {
            $delivery->status = DeliveryStatus::Delivered;
            $delivery->delivered_at = now();
            $delivery->save();

            $invoice = $delivery->invoice;
            $invoice->status = Invoice::COMPLETED;
            $invoice->save();
        });
    }

    private function queueSms(Delivery $delivery, string $code): void
    {
        Cache::put(self::codeCacheKey($delivery), $code, now()->addMinutes(10));
        SendDeliveryCodeSms::dispatch($delivery);
    }

    private function assertOwned(Delivery $delivery, User $courier): void
    {
        if (! $delivery->belongsToCourier($courier)) {
            abort(403, __('You don\'t have access this action'));
        }
    }

    private function markRejected(Delivery $delivery, string $reason): void
    {
        $delivery->status = DeliveryStatus::Rejected;
        $delivery->rejected_at = now();
        $delivery->reject_reason = $reason;
        $delivery->save();
    }

    private function cancelOpenDeliveries(Invoice $invoice, string $reason): void
    {
        $invoice->deliveries()
            ->open()
            ->get()
            ->each(fn (Delivery $delivery) => $this->markRejected($delivery, $reason));
    }
}
