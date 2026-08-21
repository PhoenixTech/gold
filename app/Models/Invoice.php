<?php

namespace App\Models;

use App\Events\InvoiceFailed;
use App\Events\InvoiceSucceed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory,SoftDeletes;

    const PENDING = 'PENDING';

    const AWAITING_PAYMENT = 'AWAITING_PAYMENT';

    const PROCESSING = 'PROCESSING';

    const COMPLETED = 'COMPLETED';

    const CANCELED = 'CANCELED';

    const FAILED = 'FAILED';

    const PAID = 'PAID';

    const WAITING_RECEIPT = 'WAITING_RECEIPT';

    const WAITING_CONFIRMATION = 'WAITING_CONFIRMATION';

    protected $casts = [
        'meta' => 'array',
    ];

    public static $invoiceStatus = ['PENDING', 'AWAITING_PAYMENT', 'CANCELED', 'FAILED', 'PAID', 'PROCESSING', 'COMPLETED'];

    /**
     * Statuses shown in the admin filter. PENDING is a leftover online-payment
     * state; offline invoices use waiting-for-receipt vs waiting-for-confirmation.
     *
     * @return list<string>
     */
    public static function adminFilterStatuses(): array
    {
        return [
            self::WAITING_RECEIPT,
            self::WAITING_CONFIRMATION,
            self::PAID,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::CANCELED,
        ];
    }

    /**
     * Statuses an admin can save on the invoice form.
     *
     * @return list<string>
     */
    public static function editableStatuses(): array
    {
        return [
            self::AWAITING_PAYMENT,
            self::PAID,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::CANCELED,
        ];
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeAwaitingOfflinePayment(Builder $query): Builder
    {
        return $query->whereIn('status', [self::AWAITING_PAYMENT, self::PENDING]);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeWaitingReceipt(Builder $query): Builder
    {
        return $query->awaitingOfflinePayment()->whereDoesntHave('paymentReceipts');
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeWaitingConfirmation(Builder $query): Builder
    {
        return $query->awaitingOfflinePayment()->whereHas('paymentReceipts');
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeNeedProcessing(Builder $query): Builder
    {
        return $query->where('status', self::PAID);
    }

    /**
     * Paid or fulfilled invoices created this calendar month.
     *
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeSoldThisMonth(Builder $query): Builder
    {
        return $query->whereIn('status', [self::PAID, self::PROCESSING, self::COMPLETED])
            ->where('created_at', '>=', now()->startOfMonth());
    }

    public function getRouteKeyName()
    {
        return 'hash';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentReceipts()
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function cardPayment(): ?Payment
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->where('type', 'CARD')->sortByDesc('id')->first();
        }

        return $this->payments()->where('type', 'CARD')->latest('id')->first();
    }

    public function isOfflineCardPayment(): bool
    {
        return $this->cardPayment() !== null;
    }

    /**
     * Number of hours a customer has to pay and upload a receipt for an offline invoice.
     * Configurable via the dashboard setting "offline_payment_hours".
     */
    public static function offlinePaymentHours(): int
    {
        $hours = (int) getSetting('offline_payment_hours');

        return $hours > 0 ? $hours : 3;
    }

    public function offlinePaymentDeadline(): ?\Carbon\Carbon
    {
        if ($this->created_at === null) {
            return null;
        }

        return $this->created_at->copy()->addHours(self::offlinePaymentHours());
    }

    /**
     * Whether this offline invoice exceeded its payment deadline without a receipt upload.
     */
    public function isOfflinePaymentExpired(): bool
    {
        if ($this->status !== self::AWAITING_PAYMENT || ! $this->isOfflineCardPayment()) {
            return false;
        }

        $deadline = $this->offlinePaymentDeadline();
        if ($deadline === null) {
            return false;
        }

        return now()->gt($deadline);
    }

    /**
     * Fail the offline payment and the invoice when the deadline passes without a receipt.
     */
    public function expireOfflinePayment(): bool
    {
        $payment = $this->cardPayment();

        if ($payment !== null && $payment->status === Payment::PENDING) {
            $payment->status = Payment::FAIL;
            $payment->comment = 'Expired: no payment receipt uploaded within the offline payment deadline.';
            $payment->save();
        }

        $this->status = self::FAILED;
        $this->save();
        $this->releaseReservedStock();

        event(new InvoiceFailed($this, $payment ?? new Payment));

        return true;
    }

    public function releaseReservedStock(): void
    {
        $calculator = app(\App\Services\ProductPriceCalculator::class);

        foreach ($this->orders as $order) {
            if (! $order->quantity_id) {
                continue;
            }

            $quantity = \App\Models\Quantity::query()->find($order->quantity_id);
            if ($quantity === null) {
                continue;
            }

            $quantity->count = 1;
            $quantity->save();
            $calculator->syncProductAggregates($quantity->product);
        }
    }

    public function needsReceiptUpload(): bool
    {
        if ($this->status !== self::AWAITING_PAYMENT) {
            return false;
        }

        $payment = $this->cardPayment();

        return $payment !== null && $payment->status === Payment::PENDING;
    }

    public function isWaitingPaymentConfirmation(): bool
    {
        return $this->needsReceiptUpload() && $this->hasUploadedReceipt();
    }

    public function hasUploadedReceipt(): bool
    {
        if (array_key_exists('payment_receipts_count', $this->attributes)) {
            return (int) $this->attributes['payment_receipts_count'] > 0;
        }

        if ($this->relationLoaded('paymentReceipts')) {
            return $this->paymentReceipts->isNotEmpty();
        }

        return $this->paymentReceipts()->exists();
    }

    /**
     * Human-facing status for offline payments. PENDING and AWAITING_PAYMENT
     * both mean "not paid yet"; the receipt is what splits them.
     */
    public function displayStatusKey(): string
    {
        if (in_array($this->status, [self::AWAITING_PAYMENT, self::PENDING], true)) {
            return $this->hasUploadedReceipt()
                ? self::WAITING_CONFIRMATION
                : self::WAITING_RECEIPT;
        }

        return (string) $this->status;
    }

    public function statusLabel(): string
    {
        return __($this->displayStatusKey());
    }

    public function statusBadgeClass(): string
    {
        return match ($this->displayStatusKey()) {
            self::WAITING_RECEIPT => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::WAITING_CONFIRMATION => 'badge bg-primary-subtle text-primary border border-primary-subtle',
            self::PAID => 'badge bg-success-subtle text-success border border-success-subtle',
            self::PROCESSING => 'badge bg-info-subtle text-info border border-info-subtle',
            self::COMPLETED => 'badge bg-success text-white',
            self::FAILED => 'badge bg-danger-subtle text-danger border border-danger-subtle',
            self::CANCELED => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            default => 'badge bg-info-subtle text-info border border-info-subtle',
        };
    }

    public static function formatPersianDateTime(?\Carbon\Carbon $date): ?string
    {
        if ($date === null || $date->timestamp === 0) {
            return null;
        }

        return $date->jdate('Y/m/d H:i');
    }

    public function formattedDeadline(): ?string
    {
        return self::formatPersianDateTime($this->offlinePaymentDeadline());
    }

    public function successPayments()
    {
        return $this->hasMany(Payment::class)->whereIn('status', ['SUCCESS', 'COMPLETED']);
    }

    public function payByBankUrl($gateway)
    {
        return route('redirect.bank', ['invoice' => $this->id, 'gateway' => $gateway]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_product')
            ->withPivot(
                'count',
                'price_total',
                'data',
                'quantity_id'
            );
    }

    public function isCompleted()
    {
        return $this->status == 'COMPLETED' or $this->status == 'PROCESSING';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->hash = generateUniqueID((strlen(Invoice::count()) + 2));
        });
    }

    public function storePaymentRequest($orderId, $amount, $token = null, $type = 'ONLINE', $bank = null): \App\Models\Payment
    {
        $payment = new Payment;
        $payment->order_id = $orderId;
        $payment->type = $type ? $type : 'ONLINE';
        $payment->amount = $amount;
        $payment->meta = [
            'fingerprint' => \Request::fingerprint(),
            'bank' => $bank,
            'token' => $token,
            'ip' => \Request::ip(),
            'auth_user' => \Auth::id(),
            'user_agent' => \Request::userAgent(),
        ];
        /** @var \App\Models\Invoice $this */
        $this->payments()->save($payment);

        //        $payment->save();

        return $payment;
    }

    public function storeSuccessPayment($paymentId, $referenceId, $cardNumber = null): \App\Models\Payment
    {
        /** @var Payment $payment */
        $payment = Payment::findOrFail($paymentId);
        $payment->reference_id = $referenceId;
        $meta = $payment->meta ?? [];
        if ($cardNumber !== null) {
            $meta['card_number'] = $cardNumber;
        }
        $payment->meta = $meta;
        $payment->status = 'SUCCESS';
        $payment->save();
        /** @var \App\Models\Invoice $this */
        $this->status = self::PAID;
        $this->save();
        if (config('app.sms.driver') == 'Kavenegar') {
            $args = [
                'receptor' => $this->customer->mobile,
                'template' => trim(getSetting('order')),
                'token10' => $this->customer->name,
                'token' => $this->hash,
                'token2' => number_format($this->total_price),
            ];
        } else {
            $args = array_merge($this->toArray(), $this->customer->toArray());
        }

        sendingSMS(getSetting('order'), $this->customer->mobile, $args);

        try {
            event(new InvoiceSucceed($this, $payment));
        } catch (\Throwable $exception) {
            \Log::debug('Error In Event OrderSucceed. But Process Continued!', compact('payment'));
            \Log::warning($exception->getMessage(), [$exception->getTraceAsString()]);
        }

        return $payment;
    }

    public function storeFailPayment($paymentId, $message = null): \App\Models\Payment
    {
        try {
            /** @var Payment $payment */
            $payment = Payment::findOrFail($paymentId);
            if ($payment->status === Payment::SUCCESS) {
                return $payment;
            }
            $payment->status = Payment::FAIL;
            $payment->comment = $message;
            $payment->save();
        } catch (\Throwable $exception) {
            $payment = new Payment;
        }
        $this->status = 'FAILED';
        /** @var \App\Models\Invoice $this */
        $this->save();
        event(new InvoiceFailed($this, $payment));

        return $payment;
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    public function evaluations()
    {

        return Evaluation::where(function ($query) {
            $query->whereNull('evaluationable_type')
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Invoice::class)
                ->whereNull('evaluationable_id');
        })->orWhere(function ($query) {
            $query->where('evaluationable_type', Invoice::class)
                ->where('evaluationable_id', $this->id);
        })->get();
    }
}
