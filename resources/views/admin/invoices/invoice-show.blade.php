@extends('layouts.app')

@section('title')
    {{ $title ?? __('Invoice') . ' #' . $invoice->hash }} -
@endsection

@section('content')
    @php
        $cardPayment = $invoice->cardPayment();
        $canConfirmPayment = $invoice->status === \App\Models\Invoice::AWAITING_PAYMENT
            && $cardPayment
            && $cardPayment->status === \App\Models\Payment::PENDING
            && $invoice->hasUploadedReceipt();
        $offlineHours = \App\Models\Invoice::offlinePaymentHours();
        $offlineDeadline = $invoice->offlinePaymentDeadline();
        $offlineIsExpired = $invoice->isOfflinePaymentExpired();
        $persianDeadline = $invoice->formattedDeadline();
        $displayStatus = $invoice->displayStatusKey();
        $address = $invoice->address;
        $addressParts = array_filter([
            $address?->state?->name,
            $address?->city?->name,
            $address?->address,
            $address?->zip ? __('Postal code') . ': ' . $address->zip : null,
        ], fn ($part) => $part !== null && trim((string) $part) !== '');
        $fullAddress = $addressParts ? implode('، ', $addressParts) : ($invoice->address_alt ?: __('No address registered.'));
        $receipts = $invoice->paymentReceipts ?? collect();
        $logoUrl = getSetting('logo_png') ? asset(getSetting('logo_png')) : (getSetting('logo_svg') ? asset(getSetting('logo_svg')) : asset('upload/images/logo.png'));
    @endphp

    <div class="admin-invoice-view pb-5">
        {{-- Screen-Only Action & Navigation Bar --}}
        <div class="no-print admin-invoice-actionbar mb-3 p-3 bg-white border rounded-3 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.invoice.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="ri-arrow-right-line"></i>
                    {{ __("Invoices list") }}
                </a>
                <span class="text-muted">|</span>
                <span class="fw-bold fs-6 text-dark">{{ __("Invoice") }} #{{ $invoice->hash }}</span>
                <span class="{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3" onclick="window.print()">
                    <i class="ri-printer-line"></i>
                    {{ __("Print invoice") }}
                </button>

                <a href="{{ route('admin.invoice.edit', $invoice) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="ri-edit-2-line"></i>
                    {{ __("Edit invoice") }}
                </a>

                <a href="{{ route('client.invoice', $invoice->hash) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="{{ __('Open Customer View') }}">
                    <i class="ri-external-link-line"></i>
                    {{ __("Customer view") }}
                </a>

                @if($canConfirmPayment)
                    <button type="submit" form="confirm-payment-form" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                        <i class="ri-check-double-line"></i>
                        {{ __("Confirm payment") }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Screen-Only Alerts / Status Explanations --}}
        <div class="no-print mb-3">
            @include('components.err')

            @if($displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION)
                <div class="alert alert-primary d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0 rounded-3 border">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-time-line fs-4"></i>
                        <div>
                            <strong>{{ __("Payment receipt is awaiting your confirmation.") }}</strong>
                            <div class="text-muted fs-xs">{{ __("A receipt is waiting. Confirm the payment if the transfer matches this invoice.") }}</div>
                        </div>
                    </div>
                    @if($canConfirmPayment)
                        <button type="submit" form="confirm-payment-form" class="btn btn-sm btn-success">
                            <i class="ri-check-double-line me-1"></i>{{ __("Confirm payment") }}
                        </button>
                    @endif
                </div>
            @elseif($displayStatus === \App\Models\Invoice::WAITING_RECEIPT)
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-0 rounded-3 border">
                    <i class="ri-alarm-warning-line fs-4"></i>
                    <div>
                        <strong>{{ __("Waiting for customer payment receipt.") }}</strong>
                        @if($persianDeadline)
                            <div class="fs-xs">
                                @if($offlineIsExpired)
                                    <span class="text-danger fw-bold">{{ __("Offline payment deadline passed") }} ({{ $persianDeadline }})</span>
                                @else
                                    <span>{{ __("Deadline:") }} <b>{{ $persianDeadline }}</b></span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Printable Official Invoice Paper --}}
        <div class="admin-invoice-paper bg-white p-3 p-md-4 rounded-3 border shadow-sm">
            {{-- 1. Invoice Header --}}
            <div class="invoice-header pb-2 mb-2 border-bottom">
                <div class="row align-items-center gy-2">
                    {{-- Company Brand & Info --}}
                    <div class="col-4 text-start">
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" class="invoice-brand-logo" onerror="this.style.display='none'">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ config('app.name') }}</h5>
                                <div class="text-muted fs-xxs">{{ getSetting('subtitle') ?: __('Official Online Store') }}</div>
                                @if(getSetting('tel'))
                                    <div class="text-muted fs-xxs" dir="ltr"><i class="ri-phone-line me-1"></i>{{ getSetting('tel') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Title (Center) --}}
                    <div class="col-4 text-center">
                        <h4 class="fw-bolder text-dark mb-1 invoice-main-title">{{ __("Official Sales Invoice") }}</h4>
                        <span class="badge {{ $invoice->statusBadgeClass() }} fs-xs px-2.5 py-1">{{ $invoice->statusLabel() }}</span>
                    </div>

                    {{-- Invoice Meta Details & QR (End) --}}
                    <div class="col-4">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <div class="invoice-meta-list text-end fs-xs lh-sm">
                                <div>
                                    <span class="text-muted">{{ __("Invoice number") }}:</span>
                                    <b class="text-dark" dir="ltr">#{{ $invoice->hash }}</b>
                                </div>
                                <div>
                                    <span class="text-muted">{{ __("Order number") }}:</span>
                                    <b class="text-dark">{{ $invoice->id }}</b>
                                </div>
                                <div>
                                    <span class="text-muted">{{ __("Issue date") }}:</span>
                                    <b class="text-dark">{{ $invoice->created_at->jdate('Y/m/d H:i') }}</b>
                                </div>
                            </div>
                            @if(isset($qr))
                                <div class="invoice-qr-box flex-shrink-0">
                                    <img src="{{ $qr->render(route('client.invoice', $invoice->hash)) }}" alt="{{ __('QR Code') }}" class="invoice-qr-img">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Seller & Buyer Information Cards --}}
            <div class="invoice-parties mb-2">
                <div class="row g-2">
                    {{-- Seller Information --}}
                    <div class="col-6">
                        <div class="party-box h-100 p-2 rounded-2 border bg-light bg-opacity-50 fs-xs lh-sm">
                            <div class="party-box-title d-flex align-items-center gap-1 mb-1 pb-1 border-bottom fw-bold text-dark">
                                <i class="ri-store-2-line text-warning"></i>
                                <span>{{ __("Seller information") }}</span>
                            </div>
                            <div class="party-box-content d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Name") }}:</span>
                                    <b class="text-dark">{{ config('app.name') }}</b>
                                </div>
                                @if(getSetting('tel'))
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __("Tel") }}:</span>
                                        <span dir="ltr" class="text-dark">{{ getSetting('tel') }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Website") }}:</span>
                                    <span dir="ltr" class="text-dark">{{ url('/') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Buyer Information --}}
                    <div class="col-6">
                        <div class="party-box h-100 p-2 rounded-2 border bg-light bg-opacity-50 fs-xs lh-sm">
                            <div class="party-box-title d-flex align-items-center gap-1 mb-1 pb-1 border-bottom fw-bold text-dark">
                                <i class="ri-user-3-line text-primary"></i>
                                <span>{{ __("Buyer information") }}</span>
                            </div>
                            <div class="party-box-content d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Name") }}:</span>
                                    <b class="text-dark">
                                        @if($invoice->customer)
                                            <a href="{{ route('admin.customer.edit', $invoice->customer->id) }}" class="text-dark text-decoration-none">
                                                {{ $invoice->customer->name }}
                                            </a>
                                        @else
                                            {{ __("Guest") }}
                                        @endif
                                    </b>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Mobile") }}:</span>
                                    <span dir="ltr" class="text-dark fw-bold">{{ $invoice->customer?->mobile ?? '---' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Address") }}:</span>
                                    <span class="text-dark text-end fw-semibold text-truncate" style="max-width: 75%;" title="{{ $fullAddress }}">{{ $fullAddress }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Shipping & Transport Meta Strip --}}
            <div class="invoice-shipping-strip mb-2 p-1.5 px-2 rounded-2 border bg-light d-flex flex-wrap align-items-center justify-content-between gap-2 fs-xs">
                <div class="d-flex align-items-center gap-1.5">
                    <i class="ri-truck-line text-primary"></i>
                    <span class="text-muted">{{ __("Shipping method") }}:</span>
                    <b class="text-dark">{{ $invoice->transport?->title ?? __('Standard Transport') }}</b>
                    <span class="text-muted">({{ number_format($invoice->transport_price) }} {{ config('app.currency.symbol') }})</span>
                </div>

                <div class="d-flex align-items-center gap-1.5">
                    <i class="ri-barcode-line text-dark"></i>
                    <span class="text-muted">{{ __("Tracking code") }}:</span>
                    @if($invoice->tracking_code)
                        <code class="fw-bold text-primary px-1.5 py-0.5 bg-white border rounded" dir="ltr">{{ $invoice->tracking_code }}</code>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">{{ __("Pending shipment") }}</span>
                    @endif
                </div>
            </div>

            {{-- 4. Order Items Table --}}
            <div class="invoice-items-table mb-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th style="min-width: 180px;" class="text-start">{{ __("Product") }}</th>
                                <th style="min-width: 160px;">{{ __("Specifications") }}</th>
                                <th style="width: 65px;">{{ __("Count") }}</th>
                                <th style="min-width: 110px;">{{ __("Unit price") }}</th>
                                <th style="min-width: 120px;">{{ __("Total price") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->orders as $k => $order)
                                @php
                                    $unitPrice = $order->count > 0 ? (int) ($order->price_total / $order->count) : $order->price_total;
                                    $product = $order->product;
                                    $quantity = $order->quantity;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $k + 1 }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center gap-1.5">
                                            @if($product && method_exists($product, 'imgUrl'))
                                                <img src="{{ $product->imgUrl() }}" alt="{{ $product->name }}" class="invoice-item-img rounded" onerror="this.style.display='none'">
                                            @endif
                                            <div>
                                                @if($product)
                                                    <a href="{{ route('admin.product.edit', $product->slug) }}" class="fw-bold text-dark text-decoration-none fs-xs">
                                                        {{ $product->name }}
                                                    </a>
                                                    @if($product->sku)
                                                        <div class="text-muted fs-xxs" dir="ltr"><small>SKU: {{ $product->sku }}</small></div>
                                                    @endif
                                                @else
                                                    <span class="text-muted fs-xs">{{ __("Product removed") }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fs-xs text-start py-1">
                                        @if($quantity)
                                            <div class="d-flex flex-wrap gap-x-2 gap-y-0.5 align-items-center">
                                                @if($quantity->weight !== null)
                                                    <span class="me-2">
                                                        <span class="text-muted">{{ __("Weight") }}:</span>
                                                        <b class="text-dark">{{ number_format((float) $quantity->weight, 3) }} {{ __('g') }}</b>
                                                    </span>
                                                @endif
                                                @if($quantity->code)
                                                    <span class="me-2">
                                                        <span class="text-muted">{{ __("Code") }}:</span>
                                                        <code class="text-dark" dir="ltr">{{ $quantity->code }}</code>
                                                    </span>
                                                @endif
                                                @foreach(($quantity->meta ?? []) as $m)
                                                    <span class="me-2">
                                                        <span class="text-muted">{{ $m['label'] ?? '' }}:</span>
                                                        <span class="text-dark">{!! $m['human_value'] ?? '-' !!}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif($order->data)
                                            <div class="text-muted fs-xxs">{{ is_array($order->data) ? json_encode($order->data) : $order->data }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold fs-xs">{{ number_format($order->count) }}</td>
                                    <td class="fs-xs">
                                        <span class="fw-semibold">{{ number_format($unitPrice) }}</span>
                                        <small class="text-muted fs-xxs">{{ config('app.currency.symbol') }}</small>
                                    </td>
                                    <td class="fw-bold text-dark fs-xs">
                                        <span>{{ number_format($order->price_total) }}</span>
                                        <small class="text-muted fs-xxs">{{ config('app.currency.symbol') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted py-3 fs-xs">{{ __("There is nothing to show!") }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5. Summary Breakdown & Financial Totals --}}
            <div class="invoice-summary-section mb-2">
                <div class="row g-2 justify-content-between">
                    {{-- Payment Details & Transaction Info (Left) --}}
                    <div class="col-6">
                        <div class="p-2 rounded-2 border bg-light bg-opacity-50 h-100 fs-xs lh-sm">
                            <div class="d-flex align-items-center gap-1 mb-1 pb-1 border-bottom fw-bold text-dark">
                                <i class="ri-bank-card-line text-success"></i>
                                <span>{{ __("Payment details") }}</span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Payment method") }}:</span>
                                    <b class="text-dark">
                                        @if($invoice->isOfflineCardPayment())
                                            <i class="ri-exchange-funds-line text-warning me-1"></i>{{ __("Card to card") }}
                                        @else
                                            <i class="ri-bank-card-2-line text-success me-1"></i>{{ __("Online Gateway") }}
                                        @endif
                                    </b>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __("Status") }}:</span>
                                    <span class="{{ $invoice->statusBadgeClass() }} fs-xxs">{{ $invoice->statusLabel() }}</span>
                                </div>
                                @php
                                    $refId = $invoice->payments->firstWhere('status', 'SUCCESS')?->reference_id
                                        ?? $invoice->payments->firstWhere('status', 'COMPLETED')?->reference_id
                                        ?? $invoice->successPayments->first()?->reference_id
                                        ?? ($cardPayment?->reference_id ?? $invoice->payments->first()?->reference_id);
                                @endphp
                                @if($refId)
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __("Transaction reference") }}:</span>
                                        <code class="text-dark fw-bold" dir="ltr">{{ $refId }}</code>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Totals Calculation Box (Right) --}}
                    <div class="col-5">
                        <div class="p-2 rounded-2 border bg-light bg-opacity-50 fs-xs lh-sm">
                            @php
                                $subtotal = $invoice->orders->sum('price_total');
                            @endphp
                            <div class="d-flex justify-content-between py-0.5 border-bottom">
                                <span class="text-muted">{{ __("Subtotal") }}:</span>
                                <span class="fw-semibold text-dark">{{ number_format($subtotal) }} {{ config('app.currency.symbol') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-0.5 border-bottom">
                                <span class="text-muted">{{ __("Shipping cost") }}:</span>
                                <span class="fw-semibold text-dark">{{ number_format($invoice->transport_price) }} {{ config('app.currency.symbol') }}</span>
                            </div>
                            @if($invoice->credit_price > 0)
                                <div class="d-flex justify-content-between py-0.5 border-bottom text-danger">
                                    <span>{{ __("Discount amount") }}:</span>
                                    <span>-{{ number_format($invoice->credit_price) }} {{ config('app.currency.symbol') }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <span class="fw-bolder text-dark">{{ __("Final payable amount") }}:</span>
                                <span class="fw-bolder fs-6 text-primary">
                                    {{ number_format($invoice->total_price) }}
                                    <small class="fs-xxs fw-normal text-muted">{{ config('app.currency.symbol') }}</small>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. Uploaded Receipts Section (Screen View Only) --}}
            @if($receipts->count())
                <div class="no-print invoice-receipts-gallery mb-2 p-2 rounded-2 border bg-light bg-opacity-25">
                    <div class="d-flex align-items-center gap-1 mb-1.5 pb-1 border-bottom fw-bold text-dark fs-xs">
                        <i class="ri-attachment-line text-primary"></i>
                        <span>{{ __("Payment receipts") }} ({{ $receipts->count() }})</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($receipts as $receipt)
                            <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="invoice-receipt-card d-flex align-items-center gap-2 p-1.5 bg-white border rounded-2 text-dark text-decoration-none shadow-sm">
                                @if($receipt->isImage())
                                    <img src="{{ $receipt->url() }}" alt="{{ $receipt->original_name }}" class="invoice-receipt-thumb rounded">
                                @else
                                    <i class="ri-file-pdf-2-line fs-2 text-danger p-1"></i>
                                @endif
                                <div class="fs-xs lh-sm">
                                    <b class="d-block text-truncate" style="max-width: 160px;">{{ $receipt->original_name }}</b>
                                    <small class="text-muted">{{ \App\Models\Invoice::formatPersianDateTime($receipt->created_at) }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 7. Description & Terms (Compact) --}}
            @if($invoice->desc || getSetting('guarantee'))
                <div class="invoice-notes mb-2 p-2 rounded-2 border bg-light bg-opacity-25 fs-xs lh-sm">
                    @if($invoice->desc)
                        <div class="mb-1">
                            <b class="text-dark"><i class="ri-message-2-line me-1 text-warning"></i>{{ __("Description") }}:</b>
                            <span class="text-muted">{{ $invoice->desc }}</span>
                        </div>
                    @endif
                    @if(getSetting('guarantee'))
                        <div>
                            <b class="text-dark"><i class="ri-shield-check-line me-1 text-success"></i>{{ __("Guarantee and return policy") }}:</b>
                            <span class="text-muted">{{ getSetting('guarantee') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- 8. Official Signatures & Stamp Box (Compact) --}}
            <div class="invoice-signatures mt-2 pt-2 border-top">
                <div class="row text-center g-2">
                    <div class="col-6">
                        <div class="signature-box p-2 border rounded-2 bg-light bg-opacity-25 h-100 d-flex flex-column justify-content-between" style="min-height: 55px;">
                            <span class="fw-bold text-dark fs-xs">{{ __("Seller Signature & Stamp") }}</span>
                            <div class="text-muted fs-xxs mt-auto">{{ config('app.name') }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="signature-box p-2 border rounded-2 bg-light bg-opacity-25 h-100 d-flex flex-column justify-content-between" style="min-height: 55px;">
                            <span class="fw-bold text-dark fs-xs">{{ __("Buyer Signature") }}</span>
                            <div class="text-muted fs-xxs mt-auto">{{ $invoice->customer?->name ?? __('Buyer') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden form for confirm payment action --}}
    @if($canConfirmPayment)
        <form id="confirm-payment-form"
              action="{{ route('admin.invoice.confirm-payment', $invoice) }}"
              method="post"
              class="d-none"
              onsubmit="return confirm('{{ __("Confirm this card-to-card payment?") }}');">
            @csrf
        </form>
    @endif

    {{-- Auto-print trigger when ?print=1 or opened via print action --}}
    @if(!empty($autoPrint))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () {
                    window.print();
                }, 400);
            });
        </script>
    @endif
@endsection
