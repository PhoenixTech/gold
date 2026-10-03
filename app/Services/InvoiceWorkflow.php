<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceWorkflow
{
    public const STEP_PAYMENT = 1;

    public const STEP_REVIEW = 2;

    public const STEP_PREPARE = 3;

    public const STEP_HANDOVER = 4;

    public const STEP_DONE = 5;

    /**
     * @return list<string>
     */
    public static function finalStatuses(): array
    {
        return [Invoice::COMPLETED, Invoice::FAILED, Invoice::CANCELED];
    }

    /**
     * @return list<string>
     */
    public static function cancelableStatuses(): array
    {
        return [
            Invoice::PENDING,
            Invoice::AWAITING_PAYMENT,
            Invoice::PAID,
            Invoice::PROCESSING,
            Invoice::READY_FOR_PICKUP,
            Invoice::OUT_FOR_DELIVERY,
        ];
    }

    /**
     * Statuses that mean money was received, so a cancel must refund.
     *
     * @return list<string>
     */
    public static function refundableStatuses(): array
    {
        return [
            Invoice::PAID,
            Invoice::PROCESSING,
            Invoice::READY_FOR_PICKUP,
            Invoice::OUT_FOR_DELIVERY,
        ];
    }

    /**
     * Statuses the fulfillment form is allowed to submit.
     *
     * @return list<string>
     */
    public static function fulfillmentStatuses(): array
    {
        return [
            Invoice::PROCESSING,
            Invoice::READY_FOR_PICKUP,
            Invoice::OUT_FOR_DELIVERY,
            Invoice::COMPLETED,
        ];
    }

    public function currentStep(Invoice $invoice): int
    {
        return match ($invoice->displayStatusKey()) {
            Invoice::WAITING_RECEIPT => self::STEP_PAYMENT,
            Invoice::WAITING_CONFIRMATION => self::STEP_REVIEW,
            Invoice::PAID, Invoice::PROCESSING => self::STEP_PREPARE,
            Invoice::READY_FOR_PICKUP, Invoice::OUT_FOR_DELIVERY => self::STEP_HANDOVER,
            Invoice::COMPLETED => self::STEP_DONE,
            default => 0,
        };
    }

    /**
     * @return list<array{number: int, label: string, state: string}>
     */
    public function steps(Invoice $invoice): array
    {
        $current = $this->currentStep($invoice);
        $isPickup = $invoice->isPickup();

        $labels = [
            self::STEP_PAYMENT => __('Payment'),
            self::STEP_REVIEW => __('Receipt review'),
            self::STEP_PREPARE => __('Preparing order'),
            self::STEP_HANDOVER => $isPickup ? __('Customer pickup') : __('Courier delivery'),
        ];

        $steps = [];
        foreach ($labels as $number => $label) {
            $state = match (true) {
                $current === 0 => 'todo',
                $number < $current => 'done',
                $number === $current => $this->isWaitingOnOthers($invoice) ? 'waiting' : 'current',
                default => 'todo',
            };

            $steps[] = ['number' => $number, 'label' => $label, 'state' => $state];
        }

        return $steps;
    }

    public function isWaitingOnOthers(Invoice $invoice): bool
    {
        return match ($invoice->displayStatusKey()) {
            Invoice::WAITING_RECEIPT => true,
            Invoice::OUT_FOR_DELIVERY => true,
            Invoice::READY_FOR_PICKUP => false,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public function allowedFulfillmentTargets(Invoice $invoice): array
    {
        $isPickup = $invoice->isPickup();

        return match ($invoice->status) {
            Invoice::PAID => $isPickup
                ? [Invoice::PROCESSING, Invoice::READY_FOR_PICKUP]
                : [Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, ...$this->legacyHandoverTargets($invoice)],
            Invoice::PROCESSING => $isPickup
                ? [Invoice::PROCESSING, Invoice::READY_FOR_PICKUP]
                : [Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, ...$this->legacyHandoverTargets($invoice)],
            Invoice::READY_FOR_PICKUP => [Invoice::READY_FOR_PICKUP, Invoice::PROCESSING, Invoice::COMPLETED],
            Invoice::OUT_FOR_DELIVERY => [Invoice::OUT_FOR_DELIVERY, Invoice::PROCESSING],
            default => [],
        };
    }

    public function canMove(Invoice $invoice, string $to): bool
    {
        if ($invoice->status === $to) {
            return true;
        }

        if (in_array($invoice->status, self::finalStatuses(), true)) {
            return false;
        }

        if (in_array($to, [Invoice::CANCELED, Invoice::FAILED], true)) {
            return true;
        }

        return in_array($to, $this->allowedFulfillmentTargets($invoice), true);
    }

    /**
     * Old postal invoices have no courier code, so the admin can still close
     * them directly. New invoices never go through here.
     *
     * @return list<string>
     */
    private function legacyHandoverTargets(Invoice $invoice): array
    {
        return $invoice->requiresDeliveryCode() ? [] : [Invoice::COMPLETED];
    }
}
