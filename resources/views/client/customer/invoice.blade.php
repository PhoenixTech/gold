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
    $offlineRemaining = ($offlineDeadline && ! $offlineIsExpired) ? max(0, $offlineDeadline->timestamp - now()->timestamp) : 0;
    $showOfflinePaymentHint = $isOfflinePayment
        && ! in_array($invoice->status, [\App\Models\Invoice::FAILED, \App\Models\Invoice::CANCELED])
        && ! $offlineIsExpired;
    $showPaymentPanel = $showOfflinePaymentHint
        && in_array($invoice->status, [\App\Models\Invoice::AWAITING_PAYMENT, \App\Models\Invoice::PENDING]);
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
                    @if($offlineDeadline)
                        <div class="liana-offline-deadline mt-1">
                            <i class="ri-timer-line"></i>
                            <span>
                                {{ __('Pay and upload your receipt within :hours hours.', ['hours' => $offlineHours]) }}
                                {{ __('Deadline:') }}
                                <b>{{ $offlineDeadline->jdate('Y/m/d H:i') }}</b>
                                (<span data-deadline-countdown
                                       data-deadline="{{ $offlineRemaining }}"
                                       data-expired-text="{{ __('Expired') }}"
                                       dir="ltr">…</span>)
                            </span>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <a href="{{ route('client.invoice.receipt', $invoice) }}" class="btn btn-sm btn-primary rounded-pill px-3 d-inline-flex align-items-center gap-1">
                                <i class="ri-file-upload-line"></i>
                                <span>{{ __('Register Payment Receipt') }}</span>
                            </a>
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
    @elseif($invoice->status === \App\Models\Invoice::CANCELED)
        <div class="liana-offline-alert no-print is-failed mb-3">
            <div class="liana-offline-alert__icon text-secondary">
                <i class="ri-close-circle-line"></i>
            </div>
            <div class="liana-offline-alert__body">
                <span class="liana-offline-alert__eyebrow text-secondary">{{ __('Canceled invoice') }}</span>
                <strong class="text-dark">{{ __('This invoice was canceled.') }}</strong>
                @if($invoice->declinedReceiptReason())
                    <p class="mb-0 text-muted fs-13">
                        {{ __('Decline reason:') }} {{ $invoice->declinedReceiptReason() }}
                    </p>
                @else
                    <p class="mb-0 text-muted fs-13">
                        {{ __('This order was canceled. If you still wish to purchase, please create a new order.') }}
                    </p>
                @endif
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
    @if($showPaymentPanel)
        <div class="liana-payment-panel card avisa-card-ref p-3 mb-3 no-print" id="payment-panel">
            @include('components.err')

            <div class="liana-payment-panel__title d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="{{ $isWaitingConfirmation ? 'ri-time-line text-info' : 'ri-bank-card-line text-primary' }} fs-5"></i>
                    <strong class="text-dark fs-14">
                        @if($isWaitingConfirmation)
                            {{ __('Waiting for payment confirmation') }}
                        @else
                            {{ __('Card to card') }}
                        @endif
                    </strong>
                </div>
                @if($canUploadReceipts && $offlineDeadline && ! $offlineIsExpired)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-fanum fs-11 d-inline-flex align-items-center gap-1">
                        <i class="ri-timer-line"></i>
                        <span data-deadline-countdown data-deadline="{{ $offlineRemaining }}" data-expired-text="{{ __('Expired') }}" dir="ltr">…</span>
                    </span>
                @endif
            </div>

            {{-- Bank Account Details Box --}}
            <div class="liana-bank-box p-3 rounded-3 bg-light border border-light-subtle mb-3">
                <div class="liana-bank-box__head d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-1.5">
                        <i class="ri-bank-line text-primary fs-5"></i>
                        <strong class="text-dark fs-13">{{ __('Deposit to this account') }}</strong>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted fs-11">{{ __('Amount to pay') }}:</span>
                        <b class="text-primary fs-13 font-fanum">{{ number_format($invoice->total_price) }} {{ config('app.currency.symbol') }}</b>
                    </div>
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
                            <dd class="text-dark mb-0 fw-bold font-fanum d-flex align-items-center gap-1.5" dir="ltr">
                                <span>{{ $bank['card_number'] }}</span>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 text-muted border-0 copy-btn"
                                        data-copy="{{ str_replace(' ', '', $bank['card_number']) }}"
                                        title="{{ __('Copy') }}">
                                    <i class="ri-file-copy-line fs-14"></i>
                                </button>
                            </dd>
                        </div>
                    @endif
                    @if($bank['account_number'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('Account number') }}:</dt>
                            <dd class="text-dark mb-0 font-fanum d-flex align-items-center gap-1.5" dir="ltr">
                                <span>{{ $bank['account_number'] }}</span>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 text-muted border-0 copy-btn"
                                        data-copy="{{ str_replace(' ', '', $bank['account_number']) }}"
                                        title="{{ __('Copy') }}">
                                    <i class="ri-file-copy-line fs-14"></i>
                                </button>
                            </dd>
                        </div>
                    @endif
                    @if($bank['iban'] ?? null)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <dt class="text-muted mb-0 fw-normal">{{ __('IBAN') }}:</dt>
                            <dd class="text-dark mb-0 font-fanum d-flex align-items-center gap-1.5" dir="ltr">
                                <span>{{ $bank['iban'] }}</span>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 text-muted border-0 copy-btn"
                                        data-copy="{{ str_replace(' ', '', $bank['iban']) }}"
                                        title="{{ __('Copy') }}">
                                    <i class="ri-file-copy-line fs-14"></i>
                                </button>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Uploaded Receipts List --}}
            @if($receipts->count())
                <div class="liana-receipts mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong class="text-dark fs-13">{{ __('Uploaded receipts') }}</strong>
                        <span class="badge bg-secondary-subtle text-secondary font-fanum fs-11">{{ $receipts->count() }}</span>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-1.5">
                        @foreach($receipts as $receipt)
                            <li class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border border-light-subtle fs-12">
                                <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="text-primary text-decoration-none d-flex align-items-center gap-1.5 min-w-0">
                                    <i class="{{ $receipt->isImage() ? 'ri-image-line' : 'ri-file-pdf-2-line' }} flex-shrink-0"></i>
                                    <span class="text-truncate">{{ $receipt->original_name }}</span>
                                </a>
                                <small class="text-muted font-fanum flex-shrink-0 ms-2">{{ $receipt->created_at?->diffForHumans() }}</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($canUploadReceipts)
                <div class="my-3 text-center">
                    <a href="{{ route('client.invoice.receipt', $invoice) }}" class="btn btn-primary btn-md rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="ri-file-upload-line fs-5"></i>
                        <span class="fw-bold">{{ __('Register Payment Receipt') }}</span>
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>
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
            if (els.length) {
                var pad = function (n) { return (n < 10 ? '0' : '') + n; };
                var render = function (el, s) {
                    var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
                    el.textContent = (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(sec);
                };
                els.forEach(function (el) {
                    var total = parseInt(el.getAttribute('data-deadline'), 10) || 0;
                    if (total <= 0) {
                        el.textContent = el.getAttribute('data-expired-text') || '00:00';
                        return;
                    }
                    render(el, total);
                    var timer = setInterval(function () {
                        total = Math.max(0, total - 1);
                        render(el, total);
                        if (total <= 0) {
                            clearInterval(timer);
                            el.textContent = el.getAttribute('data-expired-text') || '00:00';
                        }
                    }, 1000);
                });
            }

            document.querySelectorAll('[data-copy]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var val = btn.getAttribute('data-copy');
                    if (!val) return;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(val);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = val;
                        ta.style.position = 'fixed';
                        ta.style.top = '-9999px';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                    var icon = btn.querySelector('i');
                    if (icon) {
                        var orig = icon.className;
                        icon.className = 'ri-check-line text-success fs-14';
                        setTimeout(function () { icon.className = orig; }, 1800);
                    }
                });
            });
        })();
    </script>
@endonce
@endsection
