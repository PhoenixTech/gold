<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Services\DeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SendDeliveryCodeSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Delivery $delivery) {}

    public function handle(): void
    {
        $code = Cache::pull(DeliveryService::codeCacheKey($this->delivery));
        if ($code === null || $code === '') {
            logger()->warning('Delivery SMS skipped: code missing from cache', [
                'delivery_id' => $this->delivery->id,
            ]);

            return;
        }

        $this->delivery->loadMissing(['invoice.customer']);
        $invoice = $this->delivery->invoice;
        $mobile = $invoice?->customer?->mobile;
        if ($mobile === null || $mobile === '') {
            return;
        }

        if (config('app.sms.driver') == 'Kavenegar') {
            $args = [
                'receptor' => $mobile,
                'template' => trim((string) getSetting('delivery_code')),
                'token' => $code,
                'token2' => $invoice->hash,
            ];
        } else {
            $args = [
                'code' => $code,
                'hash' => $invoice->hash,
            ];
        }

        $sent = sendingSMS(getSetting('delivery_code'), $mobile, $args);
        if ($sent) {
            $this->delivery->sms_sent_at = now();
            $this->delivery->save();
        }
    }

    public function failed(Throwable $exception): void
    {
        logger()->error('SendDeliveryCodeSms failed', [
            'delivery_id' => $this->delivery->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
