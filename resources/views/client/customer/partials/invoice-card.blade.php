@php
    $orderCount = $inv->orders->count();
    $firstOrder = $inv->orders->first();
@endphp
<div class="card avisa-card-ref avisa-order-card mb-3 p-3">
    {{-- Card Header: Order Code, Persian Date, Status Pill --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2.5">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark fs-14">{{ __('Order') }} <span class="font-fanum">#{{ $inv->id }}</span></span>
            <span class="text-muted fs-12 font-fanum d-flex align-items-center gap-1">
                <i class="ri-calendar-line fs-13 text-secondary"></i>
                {{ $inv->created_at->jdate('j F Y') }}
            </span>
        </div>
        <span class="inv-badge inv-{{ $inv->displayStatusKey() }}">{{ $inv->statusLabel() }}</span>
    </div>

    {{-- Product Items Visual Preview (Natural & Human) --}}
    <div class="avisa-order-items-preview p-2.5 bg-light rounded-3 border border-light-subtle mb-3">
        @if($orderCount === 1 && $firstOrder)
            <div class="d-flex align-items-center gap-2.5">
                <img src="{{ $firstOrder->product?->thumbUrl() }}"
                     alt="{{ $firstOrder->product?->name ?? '' }}"
                     class="avisa-order-thumb rounded-2 border bg-white"
                     style="width: 50px; height: 50px; object-fit: cover;"
                     loading="lazy">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-dark fs-13 text-truncate mb-0.5">
                        {{ $firstOrder->product?->name ?? __('Product') }}
                    </div>
                    <div class="text-muted fs-12 font-fanum d-flex align-items-center gap-2">
                        <span>{{ number_format($firstOrder->count) }} {{ __('item') }}</span>
                        @if($firstOrder->quantity?->weight)
                            <span>· {{ number_format((float) $firstOrder->quantity->weight, 2) }} {{ __('g') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($orderCount > 1)
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1.5">
                    @foreach($inv->orders->take(4) as $orderItem)
                        <img src="{{ $orderItem->product?->thumbUrl() }}"
                             alt="{{ $orderItem->product?->name ?? '' }}"
                             class="avisa-order-thumb rounded-2 border bg-white"
                             style="width: 44px; height: 44px; object-fit: cover;"
                             title="{{ $orderItem->product?->name ?? '' }}"
                             loading="lazy">
                    @endforeach
                    @if($orderCount > 4)
                        <span class="badge bg-secondary-subtle text-secondary rounded-2 d-flex align-items-center justify-content-center font-fanum"
                              style="width: 44px; height: 44px; font-size: 12px;">+{{ $orderCount - 4 }}</span>
                    @endif
                </div>
                <span class="text-muted fs-12 font-fanum">{{ $orderCount }} {{ __('items') }}</span>
            </div>
        @else
            <div class="d-flex align-items-center gap-2 text-muted fs-13 py-1">
                <i class="ri-shopping-bag-3-line fs-5 text-primary"></i>
                <span>{{ __('Order registered') }}</span>
            </div>
        @endif
    </div>

    {{-- Helpful Status Context Hints --}}
    @if($inv->status === \App\Models\Invoice::OUT_FOR_DELIVERY)
        <div class="alert alert-warning py-1.5 px-2.5 rounded-3 fs-12 d-flex align-items-center gap-1.5 mb-2.5 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="ri-motorbike-line fs-14"></i>
            <span>{{ __('Courier is delivering your order. Delivery code was sent via SMS.') }}</span>
        </div>
    @elseif($inv->needsReceiptUpload())
        <div class="alert alert-warning py-1.5 px-2.5 rounded-3 fs-12 d-flex align-items-center gap-1.5 mb-2.5 border-0 bg-warning-subtle text-warning-emphasis">
            <i class="ri-time-line fs-14"></i>
            <span>{{ __('Please upload your payment receipt') }}</span>
        </div>
    @elseif($inv->displayStatusKey() === \App\Models\Invoice::WAITING_CONFIRMATION)
        <div class="alert alert-info py-1.5 px-2.5 rounded-3 fs-12 d-flex align-items-center gap-1.5 mb-2.5 border-0 bg-info-subtle text-info-emphasis">
            <i class="ri-time-line fs-14"></i>
            <span>{{ __('Payment receipt is under review') }}</span>
        </div>
    @endif

    {{-- Financial Info & Action Buttons --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
        <div class="d-flex align-items-baseline gap-1">
            <span class="text-muted fs-12">{{ __('Total price') }}:</span>
            <b class="text-dark fs-14 font-fanum">{{ number_format($inv->total_price) }}</b>
            <small class="text-muted fs-11">{{ config('app.currency.symbol') }}</small>
        </div>

        <div class="d-flex align-items-center gap-2 ms-auto">
            @if($inv->needsReceiptUpload())
                <button type="button"
                        class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3"
                        data-receipt-modal-open
                        data-upload-url="{{ route('client.invoice.receipts.store', $inv) }}"
                        data-invoice-label="#{{ $inv->id }} — {{ number_format($inv->total_price) }} {{ config('app.currency.symbol') }}">
                    <i class="ri-upload-2-line me-1"></i>
                    {{ __('Upload receipt') }}
                </button>
            @elseif(in_array($inv->status, ['PENDING', 'CANCELED', 'FAILED']) && $inv->created_at->timestamp > (time() - 3600))
                <a href="{{ route('client.pay', $inv->hash) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="ri-secure-payment-line me-1"></i>
                    {{ __('Pay now') }}
                </a>
            @endif
            <a href="{{ route('client.invoice', $inv->hash) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 d-inline-flex align-items-center gap-1">
                <span>{{ __('Order details') }}</span>
                <i class="ri-arrow-left-s-line"></i>
            </a>
        </div>
    </div>
</div>
