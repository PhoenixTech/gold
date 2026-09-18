@extends('layouts.customer')

@section('title')
    {{__("Invoice")}} #{{$invoice->hash}} - {{config('app.name')}}
@endsection

@section('customer-content')
@php
    $cardPayment = $invoice->cardPayment();
    $bank = array_filter($cardPayment?->meta ?? []) ?: \App\Http\Controllers\CardController::activeBankDisplay();
    $receipts = $invoice->paymentReceipts ?? collect();
    $canUploadReceipts = $invoice->needsReceiptUpload();
    $isOfflinePayment = $invoice->isOfflineCardPayment();
    $offlineHours = \App\Models\Invoice::offlinePaymentHours();
    $offlineDeadline = $invoice->offlinePaymentDeadline();
    $offlineIsExpired = $invoice->isOfflinePaymentExpired();
    $offlineRemaining = $offlineDeadline ? max(0, (int) $offlineDeadline->diffInSeconds(now())) : 0;
    $showOfflinePaymentHint = $isOfflinePayment
        && ! in_array($invoice->status, [\App\Models\Invoice::FAILED, \App\Models\Invoice::CANCELED])
        && ! $offlineIsExpired;
    $hasUploadedReceipts = $receipts->isNotEmpty();
    $isWaitingConfirmation = $canUploadReceipts && $hasUploadedReceipts;

    $address = $invoice->address;
    $addressParts = array_filter([
        $address?->state?->name,
        $address?->city?->name,
        $address?->address,
        $address?->zip,
    ], fn ($part) => $part !== null && trim((string) $part) !== '');
@endphp

<div class="avisa-invoice-page">
    {{-- Customer Subnav Header with Back Button --}}
    <div class="avisa-subnav-head mb-3">
        <a href="{{ route('client.profile') }}#invoices" class="avisa-subnav-back" aria-label="{{ __('Back to orders') }}">
            <i class="ri-arrow-right-line"></i>
        </a>
        <div class="d-flex align-items-center justify-content-between flex-grow-1">
            <h4 class="fw-bold mb-0 text-dark">{{ __('Order details') }} <span class="font-fanum">#{{ $invoice->id }}</span></h4>
            <span class="inv-badge inv-{{ $invoice->displayStatusKey() }}">{{ $invoice->statusLabel() }}</span>
        </div>
    </div>

    {{-- Offline Payment Alert Banner --}}
    @if($canUploadReceipts && ! $offlineIsExpired)
        <div class="liana-offline-alert no-print {{ $isWaitingConfirmation ? 'is-waiting' : '' }} mb-3" id="receipt-upload">
            <div class="liana-offline-alert__icon">
                <i class="{{ $isWaitingConfirmation ? 'ri-time-line' : 'ri-bank-card-line' }}"></i>
            </div>
            <div class="liana-offline-alert__body">
                <span class="liana-offline-alert__eyebrow">{{ __('Offline payment') }}</span>
                @if($isWaitingConfirmation)
                    <strong>{{ __('Waiting for payment confirmation') }}</strong>
                    <p>
                        {{ __('Your receipt was received. We are reviewing your offline payment. Please wait for admin confirmation.') }}
                    </p>
                @else
                    <strong>{{ __('This invoice needs a payment receipt') }}</strong>
                    <p>
                        {{ __('Pay by card-to-card using the bank details below, then upload your receipt so we can confirm the order.') }}
                    </p>
                    @if($offlineDeadline)
                        <div class="liana-offline-deadline">
                            <i class="ri-timer-line"></i>
                            <span>
                                {{ __('Pay and upload your receipt within :hours hours.', ['hours' => $offlineHours]) }}
                                {{ __('Deadline:') }}
                                <b>{{ $offlineDeadline->jdate('Y/m/d H:i') }}</b>
                                (<span data-deadline-countdown
                                       data-deadline="{{ $offlineRemaining }}"
                                       data-expired-text="{{ __('Expired') }}">…</span>)
                            </span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @elseif($offlineIsExpired || $invoice->status === \App\Models\Invoice::FAILED)
        <div class="liana-offline-alert no-print is-failed mb-3">
            <div class="liana-offline-alert__icon text-danger">
                <i class="ri-error-warning-line"></i>
            </div>
            <div class="liana-offline-alert__body">
                <span class="liana-offline-alert__eyebrow text-danger">{{ __('Expired invoice') }}</span>
                <strong class="text-danger">{{ __('Deadline passed — this invoice was failed.') }}</strong>
                <p class="mb-0 text-muted fs-13">
                    {{ __('The payment deadline for this order has expired. If you still wish to purchase, please create a new order.') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Courier Delivery Alert --}}
    @if($invoice->status === \App\Models\Invoice::OUT_FOR_DELIVERY)
        <div class="alert alert-warning border border-warning-subtle rounded-3 p-3 mb-3 d-flex align-items-center gap-2.5">
            <i class="ri-motorbike-line fs-4 text-warning"></i>
            <div class="fs-13 text-dark fw-medium">
                {{ __('A 4-digit code was sent to your mobile. Give it only to the courier.') }}
            </div>
        </div>
    @endif

    {{-- Order Meta Summary Card --}}
    <div class="card avisa-card-ref p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between pb-2.5 border-bottom mb-2.5">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-shopping-bag-3-line text-primary fs-5"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark fs-14">{{ __('Order') }} <span class="font-fanum">#{{ $invoice->id }}</span></h6>
                    <span class="text-muted fs-12 font-fanum">{{ $invoice->created_at->jdate('j F Y - H:i') }}</span>
                </div>
            </div>
            <span class="inv-badge inv-{{ $invoice->displayStatusKey() }}">{{ $invoice->statusLabel() }}</span>
        </div>

        <div class="row g-2 text-muted fs-12 font-fanum">
            <div class="col-6">
                <span>{{ __('Order code') }}:</span>
                <b class="text-dark">{{ $invoice->hash }}</b>
            </div>
            <div class="col-6 text-end">
                <span>{{ __('Items count') }}:</span>
                <b class="text-dark">{{ $invoice->orders->count() }} {{ __('item') }}</b>
            </div>
        </div>
    </div>

    {{-- Ordered Products Card --}}
    <div class="card avisa-card-ref p-3 mb-3">
        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center justify-content-between">
            <span class="d-flex align-items-center gap-2">
                <i class="ri-list-check-2 text-primary fs-5"></i>
                <span>{{ __('Ordered items') }}</span>
            </span>
            <span class="badge bg-secondary-subtle text-secondary font-fanum fs-12">{{ $invoice->orders->count() }}</span>
        </h6>

        <div class="d-flex flex-column gap-2.5">
            @foreach($invoice->orders as $k => $order)
                <div class="d-flex align-items-center gap-3 p-2.5 rounded-3 bg-light border border-light-subtle">
                    <img src="{{ $order->product?->thumbUrl() }}"
                         alt="{{ $order->product?->name ?? '' }}"
                         class="rounded-2 border bg-white flex-shrink-0"
                         style="width: 58px; height: 58px; object-fit: cover;"
                         loading="lazy">

                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark fs-13 mb-1 text-truncate">
                            {{ $order->product?->name ?? '-' }}
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-1.5 mb-1">
                            @if($order->quantity)
                                @if($order->quantity->weight !== null)
                                    <span class="badge bg-white text-dark border fs-11 font-fanum">
                                        {{ __('Weight') }}: {{ number_format((float) $order->quantity->weight, 3) }} {{ __('gram') }}
                                    </span>
                                @endif
                                @if($order->quantity->code)
                                    <span class="badge bg-white text-muted border fs-11 font-fanum">
                                        {{ __('Code') }}: {{ $order->quantity->code }}
                                    </span>
                                @endif
                                @foreach(($order->quantity->meta ?? []) as $m)
                                    <span class="badge bg-white text-muted border fs-11">{!! $m['human_value'] ?? '-' !!}</span>
                                @endforeach
                            @endif
                        </div>

                        <div class="d-flex align-items-center justify-content-between text-muted fs-12 font-fanum">
                            <span>{{ number_format($order->count) }} × {{ number_format((int) ($order->price_total / max(1, $order->count))) }} {{ config('app.currency.symbol') }}</span>
                            <b class="text-dark fs-13 font-fanum">{{ number_format($order->price_total) }} {{ config('app.currency.symbol') }}</b>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Delivery Details Card --}}
    <div class="card avisa-card-ref p-3 mb-3">
        <h6 class="fw-bold text-dark mb-2.5 d-flex align-items-center gap-2">
            <i class="ri-map-pin-2-line text-primary fs-5"></i>
            <span>{{ __('Delivery details') }}</span>
        </h6>

        <div class="d-flex flex-column gap-2 fs-13">
            <div class="d-flex align-items-center justify-content-between text-muted">
                <span>{{ __('Customer') }}:</span>
                <b class="text-dark">{{ $invoice->customer->name ?? __('Customer') }}</b>
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted font-fanum">
                <span>{{ __('Customer mobile') }}:</span>
                <b class="text-dark" dir="ltr">{{ $invoice->customer->mobile ?? '-' }}</b>
            </div>
            <div class="d-flex align-items-start justify-content-between text-muted">
                <span class="flex-shrink-0">{{ __('Shipping address') }}:</span>
                <span class="text-dark text-end ms-3">{{ $addressParts ? implode('، ', $addressParts) : __('No address registered.') }}</span>
            </div>
            @if($invoice->transport)
                <div class="d-flex align-items-center justify-content-between text-muted">
                    <span>{{ __('Shipping method') }}:</span>
                    <b class="text-dark">{{ $invoice->transport->title }}</b>
                </div>
            @endif
        </div>
    </div>

    {{-- Financial & Payment Breakdown Card --}}
    <div class="card avisa-card-ref p-3 mb-3">
        <h6 class="fw-bold text-dark mb-2.5 d-flex align-items-center gap-2">
            <i class="ri-wallet-3-line text-primary fs-5"></i>
            <span>{{ __('Payment details') }}</span>
        </h6>

        <div class="d-flex flex-column gap-2 fs-13 border-bottom pb-2.5 mb-2.5">
            <div class="d-flex align-items-center justify-content-between text-muted">
                <span>{{ __('Subtotal') }}:</span>
                <span class="font-fanum">{{ number_format($invoice->orders->sum('price_total') ?: $invoice->total_price - $invoice->transport_price) }} {{ config('app.currency.symbol') }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted">
                <span>{{ __('Shipping cost') }}:</span>
                <span class="font-fanum">{{ number_format($invoice->transport_price) }} {{ config('app.currency.symbol') }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted">
                <span>{{ __('Payment method') }}:</span>
                <span class="fw-medium text-dark">
                    @if($showOfflinePaymentHint)
                        <i class="ri-exchange-funds-line me-1"></i>{{ __('Card to card') }}
                    @else
                        <i class="ri-bank-card-line me-1"></i>{{ __('Online payment') }}
                    @endif
                </span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark fs-14">{{ __('Total price') }}:</span>
            <div class="d-flex align-items-baseline gap-1">
                <b class="fs-16 text-primary font-fanum">{{ number_format($invoice->total_price) }}</b>
                <span class="text-muted fs-12">{{ config('app.currency.symbol') }}</span>
            </div>
        </div>
    </div>

    {{-- Offline Card-to-Card Payment Section --}}
    @if($showOfflinePaymentHint)
        <div class="liana-payment-panel card avisa-card-ref p-3 mb-3 no-print">
            @include('components.err')

            <div class="liana-payment-panel__title d-flex align-items-start gap-2.5 mb-3">
                <i class="{{ $isWaitingConfirmation ? 'ri-time-line text-info' : 'ri-exchange-funds-line text-primary' }} fs-4"></i>
                <div>
                    @if($isWaitingConfirmation)
                        <strong class="d-block text-dark fs-14">{{ __('Waiting for payment confirmation') }}</strong>
                        <p class="text-muted fs-13 mb-0">{{ __('We received your upload. Please wait until an admin confirms the payment.') }}</p>
                    @else
                        <strong class="d-block text-dark fs-14">{{ __('Offline card-to-card payment') }}</strong>
                        <p class="text-muted fs-13 mb-0">{{ __('This order is not paid online. Transfer the amount, then upload the receipt.') }}</p>
                    @endif
                </div>
            </div>

            @if($canUploadReceipts)
                @if($offlineDeadline && ! $offlineIsExpired)
                    <div class="liana-payment-deadline alert alert-warning py-2 px-3 rounded-3 fs-13 mb-3">
                        <i class="ri-time-line me-1"></i>
                        {{ __('Complete the transfer and upload the receipt before the deadline.') }}
                        <b class="font-fanum">{{ $offlineDeadline->jdate('Y/m/d H:i') }}</b>
                    </div>
                @endif
                <ol class="liana-payment-steps mb-3">
                    <li class="{{ $isWaitingConfirmation ? 'is-done' : '' }}">
                        <span>1</span>
                        <div>
                            <strong>{{ __('Transfer the amount') }}</strong>
                            <small>{{ __('Use the bank account details below') }}</small>
                        </div>
                    </li>
                    <li class="{{ $isWaitingConfirmation ? 'is-done' : 'is-current' }}">
                        <span>2</span>
                        <div>
                            <strong>{{ __('Upload the receipt') }}</strong>
                            <small>{{ __('Photo or PDF of your transfer') }}</small>
                        </div>
                    </li>
                    <li class="{{ $isWaitingConfirmation ? 'is-current' : '' }}">
                        <span>3</span>
                        <div>
                            <strong>{{ __('Wait for confirmation') }}</strong>
                            <small>{{ __('We will review and confirm your payment') }}</small>
                        </div>
                    </li>
                </ol>
            @endif

            {{-- Bank Account Details Box --}}
            <div class="liana-bank-box p-3 rounded-3 bg-light border border-light-subtle mb-3">
                <div class="liana-bank-box__head d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <i class="ri-bank-line text-primary fs-5"></i>
                    <strong class="text-dark fs-13">{{ __('Deposit to this account') }}</strong>
                </div>
                <div class="liana-bank-box__amount d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12">{{ __('Amount to pay') }}:</span>
                    <b class="text-primary fs-14 font-fanum">{{ number_format($invoice->total_price) }} {{ config('app.currency.symbol') }}</b>
                </div>
                <dl class="liana-bank-box__rows mb-0 fs-12">
                    @if($bank['bank_name'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('Bank name') }}:</dt>
                            <dd class="text-dark mb-0 fw-medium">{{ $bank['bank_name'] }}</dd>
                        </div>
                    @endif
                    @if($bank['account_holder_name'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('Account holder name') }}:</dt>
                            <dd class="text-dark mb-0 fw-medium">{{ $bank['account_holder_name'] }}</dd>
                        </div>
                    @endif
                    @if($bank['card_number'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('Card number') }}:</dt>
                            <dd class="text-dark mb-0 fw-bold font-fanum" dir="ltr">{{ $bank['card_number'] }}</dd>
                        </div>
                    @endif
                    @if($bank['account_number'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('Account number') }}:</dt>
                            <dd class="text-dark mb-0 font-fanum" dir="ltr">{{ $bank['account_number'] }}</dd>
                        </div>
                    @endif
                    @if($bank['iban'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('IBAN') }}:</dt>
                            <dd class="text-dark mb-0 font-fanum" dir="ltr">{{ $bank['iban'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Uploaded Receipts List --}}
            @if($receipts->count())
                <div class="liana-receipts mb-3">
                    <strong class="d-block text-dark fs-13 mb-2">{{ __('Uploaded receipts') }}</strong>
                    <ul class="list-unstyled mb-2 d-flex flex-column gap-1.5">
                        @foreach($receipts as $receipt)
                            <li class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border border-light-subtle fs-12">
                                <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="text-primary text-decoration-none d-flex align-items-center gap-1.5">
                                    <i class="{{ $receipt->isImage() ? 'ri-image-line' : 'ri-file-pdf-2-line' }}"></i>
                                    <span class="text-truncate">{{ $receipt->original_name }}</span>
                                </a>
                                <small class="text-muted font-fanum">{{ $receipt->created_at?->diffForHumans() }}</small>
                            </li>
                        @endforeach
                    </ul>
                    @if($canUploadReceipts)
                        <p class="liana-receipts__note text-muted fs-12 mb-2">
                            @if($isWaitingConfirmation)
                                {{ __('We received your upload. Please wait until an admin confirms the payment.') }}
                            @else
                                {{ __('Receipt received. You can upload more files if needed while we review your payment.') }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif

            {{-- Receipt Upload Form Component --}}
            @if($canUploadReceipts && ! $isWaitingConfirmation)
                @include('components.payment-receipt-uploader', ['invoice' => $invoice])
            @elseif($canUploadReceipts && $isWaitingConfirmation)
                <div class="liana-payment-waiting alert alert-info rounded-3 d-flex align-items-center gap-2 mb-2 fs-13">
                    <i class="ri-time-line fs-5"></i>
                    <div>
                        <strong>{{ __('Receipt uploaded successfully') }}</strong>
                        <p class="mb-0 fs-12">{{ __('Your file is under review. You can still add another receipt if needed.') }}</p>
                    </div>
                </div>
                <details class="liana-receipt-more mt-2">
                    <summary class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Upload another receipt') }}</summary>
                    <div class="pt-3">
                        @include('components.payment-receipt-uploader', ['invoice' => $invoice])
                    </div>
                </details>
            @elseif($invoice->status === \App\Models\Invoice::PAID)
                <div class="liana-payment-done alert alert-success rounded-3 d-flex align-items-center gap-2 fs-13 mb-0">
                    <i class="ri-checkbox-circle-line fs-5"></i>
                    <span>{{ __('Payment confirmed') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Description Note if any --}}
    @if($invoice->desc)
        <div class="card avisa-card-ref p-3 mb-3">
            <span class="text-muted fs-12 d-block mb-1">{{ __('Description') }}:</span>
            <p class="text-dark fs-13 mb-0">{{ $invoice->desc }}</p>
        </div>
    @endif

    {{-- Print Invoice Button --}}
    @if($invoice->status === \App\Models\Invoice::COMPLETED)
        <div class="no-print liana-print-btn btn btn-outline-secondary rounded-pill w-100 py-2.5 mb-3 d-flex align-items-center justify-content-center gap-2" onclick="window.print()">
            <i class="ri-printer-line fs-5"></i>
            <span>{{__("Print invoice")}}</span>
        </div>
    @endif
</div>

@once
    <script>
        (function () {
            var els = document.querySelectorAll('[data-deadline-countdown]');
            if (!els.length) return;
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            var render = function (el, s) {
                var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
                el.textContent = h + ':' + pad(m) + ':' + pad(sec);
            };
            els.forEach(function (el) {
                var total = parseInt(el.getAttribute('data-deadline'), 10) || 0;
                render(el, total);
                var timer = setInterval(function () {
                    total = Math.max(0, total - 1);
                    render(el, total);
                    if (total <= 0) {
                        clearInterval(timer);
                        el.textContent = el.getAttribute('data-expired-text') || '';
                    }
                }, 1000);
            });
        })();
    </script>
@endonce
@endsection
