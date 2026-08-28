@extends('website.inc.website-layout')

@section('title')
    {{__("Invoice")}} #{{$invoice->hash}} - {{config('app.name')}}
@endsection

@section('content')
<section class='LianaInvoice py-4'>
    <div class="p-3">
        <div class="liana-card">

            @php
                $cardPayment = $invoice->cardPayment();
                $bankMeta = $cardPayment?->meta ?? [];
                $bank = [
                    'bank_name' => $bankMeta['bank_name'] ?? null,
                    'account_holder_name' => $bankMeta['account_holder_name'] ?? null,
                    'card_number' => $bankMeta['card_number'] ?? null,
                    'account_number' => $bankMeta['account_number'] ?? null,
                    'iban' => $bankMeta['iban'] ?? null,
                ];
                if (! array_filter($bank)) {
                    $bank = \App\Http\Controllers\CardController::activeBankDisplay();
                }
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
            @endphp

            @if($canUploadReceipts && ! $offlineIsExpired)
                <div class="liana-offline-alert no-print {{ $isWaitingConfirmation ? 'is-waiting' : '' }}" id="receipt-upload">
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

            <div class="liana-head">
                <div class="liana-brand">
                    <img src="{{asset('upload/images/logo.png')}}" class="liana-logo" alt="">
                    <div class="liana-brand-meta">
                        <h5>{{config('app.name')}}</h5>
                        <span class="inv-badge inv-{{$invoice->displayStatusKey()}}">{{ $invoice->statusLabel() }}</span>
                        @if($invoice->status === \App\Models\Invoice::OUT_FOR_DELIVERY)
                            <div class="liana-payment-done mt-2">
                                <i class="ri-motorbike-line"></i>
                                {{ __('A 4-digit code was sent to your mobile. Give it only to the courier.') }}
                            </div>
                        @endif
                        @if($showOfflinePaymentHint)
                            <span class="liana-pay-type">
                                <i class="ri-exchange-funds-line"></i>
                                {{ __('Card to card') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="liana-qr">
                    <img src="{{$qr->render(route('client.invoice',$invoice->hash))}}" alt="qr code"
                         class="qr-code">
                </div>
            </div>

            <div class="liana-meta">
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-calendar-line"></i> {{__("Date")}}</span>
                    <b>{{$invoice->created_at->ldate('Y-m-d')}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-file-list-3-line"></i> {{__("ID")}}</span>
                    <b>{{$invoice->hash}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-user-3-line"></i> {{__("Customer")}}</span>
                    <b>{{$invoice->customer->name ?? __('Customer')}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-phone-line"></i> {{__("Customer mobile")}}</span>
                    <b dir="ltr">{{$invoice->customer->mobile ?? '-'}}</b>
                </div>
            </div>

            <div class="liana-table-wrap">
                <table class="liana-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__("Product")}}</th>
                            <th>{{__("Count")}}</th>
                            <th>{{__("Specification")}}</th>
                            <th class="text-end">{{__("Price")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->orders as $k => $order)
                            <tr>
                                <td data-label="#">{{$k + 1}}</td>
                                <td data-label="{{__('Product')}}">{{$order->product->name ?? '-'}}</td>
                                <td data-label="{{__('Count')}}">{{number_format($order->count)}}</td>
                                <td data-label="{{__('Specification')}}">
                                    @if($order->quantity)
                                        @if($order->quantity->weight !== null)
                                            <span>{{ __('Weight') }}: {{ number_format((float) $order->quantity->weight, 3) }} {{ __('gram') }}</span>
                                        @endif
                                        @if($order->quantity->code)
                                            <span>{{ __('Code') }}: {{ $order->quantity->code }}</span>
                                        @endif
                                        @foreach(($order->quantity->meta ?? []) as $m)
                                            <span>{!! $m['human_value'] ?? '-' !!}</span>
                                        @endforeach
                                        @if($order->quantity->weight === null && ! $order->quantity->code && empty($order->quantity->meta))
                                            -
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-label="{{__('Price')}}" class="liana-price">
                                    {{number_format($order->price_total)}} {{config('app.currency.symbol')}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="liana-summary">
                <div class="liana-summary-item">
                    <span><i class="ri-truck-line"></i> {{__("Transport")}}</span>
                    <b>{{number_format($invoice->transport_price)}} {{config('app.currency.symbol')}}</b>
                </div>
                <div class="liana-summary-item">
                    <span><i class="ri-shopping-bag-4-line"></i> {{__("Orders count")}}</span>
                    <b>{{number_format($invoice->count)}}</b>
                </div>
                <div class="liana-summary-item liana-summary-total">
                    <span><i class="ri-bank-card-2-line"></i> {{__("Total price")}}</span>
                    <b>{{number_format($invoice->total_price)}} {{config('app.currency.symbol')}}</b>
                </div>
            </div>

            <div class="liana-footer">
                <p class="liana-desc">
                    {{$invoice->desc}}
                </p>
                <div class="liana-address">
                    <i class="ri-map-pin-2-line"></i>
                    {{__("Address")}}:
                    @php
                        $address = $invoice->address;
                        $addressParts = array_filter([
                            $address?->state?->name,
                            $address?->city?->name,
                            $address?->address,
                            $address?->zip,
                        ], fn ($part) => $part !== null && trim((string) $part) !== '');
                    @endphp
                    {{ $addressParts ? implode('، ', $addressParts) : __('No address registered.') }}
                </div>

                @if($showOfflinePaymentHint)
                    <div class="liana-payment-panel no-print">
                        @include('components.err')

                        <div class="liana-payment-panel__title">
                            <i class="{{ $isWaitingConfirmation ? 'ri-time-line' : 'ri-exchange-funds-line' }}"></i>
                            <div>
                                @if($isWaitingConfirmation)
                                    <strong>{{ __('Waiting for payment confirmation') }}</strong>
                                    <p>{{ __('We received your upload. Please wait until an admin confirms the payment.') }}</p>
                                @else
                                    <strong>{{ __('Offline card-to-card payment') }}</strong>
                                    <p>{{ __('This order is not paid online. Transfer the amount, then upload the receipt.') }}</p>
                                @endif
                            </div>
                        </div>

                        @if($canUploadReceipts)
                            @if($offlineDeadline && ! $offlineIsExpired)
                                <div class="liana-payment-deadline">
                                    <i class="ri-time-line"></i>
                                    {{ __('Complete the transfer and upload the receipt before the deadline.') }}
                                    <b>{{ $offlineDeadline->jdate('Y/m/d H:i') }}</b>
                                </div>
                            @endif
                            <ol class="liana-payment-steps">
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

                        <div class="liana-bank-box">
                            <div class="liana-bank-box__head">
                                <i class="ri-bank-line"></i>
                                <strong>{{ __('Deposit to this account') }}</strong>
                            </div>
                            <div class="liana-bank-box__amount">
                                <span>{{ __('Amount to pay') }}</span>
                                <b>{{ number_format($invoice->total_price) }} {{ config('app.currency.symbol') }}</b>
                            </div>
                            <dl class="liana-bank-box__rows">
                                @if($bank['bank_name'] ?? null)
                                    <div>
                                        <dt>{{ __('Bank name') }}</dt>
                                        <dd>{{ $bank['bank_name'] }}</dd>
                                    </div>
                                @endif
                                @if($bank['account_holder_name'] ?? null)
                                    <div>
                                        <dt>{{ __('Account holder name') }}</dt>
                                        <dd>{{ $bank['account_holder_name'] }}</dd>
                                    </div>
                                @endif
                                @if($bank['card_number'] ?? null)
                                    <div>
                                        <dt>{{ __('Card number') }}</dt>
                                        <dd dir="ltr">{{ $bank['card_number'] }}</dd>
                                    </div>
                                @endif
                                @if($bank['account_number'] ?? null)
                                    <div>
                                        <dt>{{ __('Account number') }}</dt>
                                        <dd dir="ltr">{{ $bank['account_number'] }}</dd>
                                    </div>
                                @endif
                                @if($bank['iban'] ?? null)
                                    <div>
                                        <dt>{{ __('IBAN') }}</dt>
                                        <dd dir="ltr">{{ $bank['iban'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        @if($receipts->count())
                            <div class="liana-receipts">
                                <strong>{{ __('Uploaded receipts') }}</strong>
                                <ul>
                                    @foreach($receipts as $receipt)
                                        <li>
                                            <a href="{{ $receipt->url() }}" target="_blank" rel="noopener">
                                                <i class="{{ $receipt->isImage() ? 'ri-image-line' : 'ri-file-pdf-2-line' }}"></i>
                                                {{ $receipt->original_name }}
                                            </a>
                                            <small>{{ $receipt->created_at?->diffForHumans() }}</small>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($canUploadReceipts)
                                    <p class="liana-receipts__note">
                                        @if($isWaitingConfirmation)
                                            {{ __('We received your upload. Please wait until an admin confirms the payment.') }}
                                        @else
                                            {{ __('Receipt received. You can upload more files if needed while we review your payment.') }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if($canUploadReceipts && ! $isWaitingConfirmation)
                            @include('components.payment-receipt-uploader', ['invoice' => $invoice])
                        @elseif($canUploadReceipts && $isWaitingConfirmation)
                            <div class="liana-payment-waiting">
                                <i class="ri-time-line"></i>
                                <div>
                                    <strong>{{ __('Receipt uploaded successfully') }}</strong>
                                    <p>{{ __('Your file is under review. You can still add another receipt if needed.') }}</p>
                                </div>
                            </div>
                            <details class="liana-receipt-more">
                                <summary>{{ __('Upload another receipt') }}</summary>
                                @include('components.payment-receipt-uploader', ['invoice' => $invoice])
                            </details>
                        @elseif($invoice->status === \App\Models\Invoice::PAID)
                            <div class="liana-payment-done">
                                <i class="ri-checkbox-circle-line"></i>
                                {{ __('Payment confirmed') }}
                            </div>
                        @endif
                    </div>
                @endif

                @if(trim(getSetting('invoice_desc') ?? '') != '')
                    <hr>
                    <div class="liana-dyn">
                        {!! getSetting('invoice_desc') !!}
                    </div>
                @endif
            </div>

            @if($invoice->status === \App\Models\Invoice::COMPLETED)
                <div class="no-print liana-print-btn" onclick="window.print()">
                    <i class="ri-printer-line"></i>
                    {{__("Print invoice")}}
                </div>
            @endif
        </div>
    </div>
</section>

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
