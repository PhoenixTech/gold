<div class="item-list shadow-sm mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold fs-15 text-dark d-flex align-items-center gap-2">
            <i class="ri-file-list-3-line text-primary fs-18"></i>
            <span>{{ __('Invoice summary') }}</span>
        </h5>
        @if($item->orders->count() > 0)
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum">
                {{ number_format($item->orders->count()) }} {{ __('Pieces') }}
            </span>
        @endif
    </div>

    <div class="p-3 border-bottom">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fs-12">{{ __('Customer') }}</span>
            @if($customer)
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.customer.show', $customer->id) }}" class="fw-semibold text-dark text-decoration-none fs-13">
                        {{ $customer->name }}
                    </a>
                    <a href="{{ route('admin.customer.show', $customer->id) }}" class="btn btn-sm btn-outline-primary py-0 px-1.5 fs-11">
                        {{ __('Profile') }}
                    </a>
                </div>
            @else
                <span class="text-muted fs-13">—</span>
            @endif
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted fs-12">{{ __('Mobile') }}</span>
            @if($customer?->mobile)
                <a href="tel:{{ $customer->mobile }}" dir="ltr" class="font-fanum fw-semibold text-primary text-decoration-none d-inline-flex align-items-center gap-1 fs-13">
                    <i class="ri-phone-line fs-14"></i>
                    <span>{{ $customer->mobile }}</span>
                </a>
            @else
                <span class="text-muted fs-13">—</span>
            @endif
        </div>
        @if($item->is_third_party)
            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                <span class="text-muted fs-12">{{ __('Recipient (gift order)') }}</span>
                <span class="fw-semibold text-dark fs-13">{{ $item->recipient_name ?: '—' }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
                <span class="text-muted fs-12">{{ __('Recipient mobile') }}</span>
                @if($item->recipient_mobile)
                    <a href="tel:{{ $item->recipient_mobile }}" dir="ltr" class="font-fanum fw-semibold text-primary text-decoration-none fs-13">
                        {{ $item->recipient_mobile }}
                    </a>
                @else
                    <span class="text-muted fs-13">—</span>
                @endif
            </div>
        @endif
    </div>

    @unless($isWaitingReceipt || $isWaitingConfirmation)
        <div class="p-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="text-muted fs-12 d-flex align-items-center gap-1">
                    <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-map-pin-line' }} text-primary"></i>
                    <span>{{ $isPickup ? __('Pickup location') : __('Delivery address') }}</span>
                </span>
                <span class="badge bg-light text-dark border fs-11" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
                    {{ $isPickup ? __('Store pickup') : __('Courier delivery') }}
                </span>
            </div>
            <div class="fs-13 text-dark mt-1">
                @if($isPickup)
                    {{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}
                @else
                    {{ $item->address?->address ?: ($item->address_alt ?: '—') }}
                @endif
            </div>
        </div>
    @endunless

    <div class="p-3 {{ trim((string) $item->desc) !== '' ? 'border-bottom' : '' }}">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fs-13">{{ __('Pieces') }}</span>
            <span class="font-fanum fw-semibold text-dark fs-13">{{ number_format($item->orders->count()) }}</span>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted fs-13">{{ __('Total price') }}</span>
            <span class="font-fanum fw-bold text-dark fs-14">
                {{ number_format($item->total_price) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
            </span>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted fs-13">{{ __('Paid amount') }}</span>
            <span class="font-fanum fw-bold text-success fs-14">
                {{ number_format($item->receivedAmount()) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
            </span>
        </div>

        @if($item->remainingReceiptBalance() > 0)
            <div class="p-2.5 rounded-3 bg-danger-subtle border border-danger-subtle d-flex align-items-center justify-content-between">
                <span class="text-danger fw-semibold fs-13">{{ __('Remaining balance') }}</span>
                <span class="font-fanum fw-bold text-danger fs-15">
                    {{ number_format($item->remainingReceiptBalance()) }} <small class="fw-normal fs-11">{{ __('Toman') }}</small>
                </span>
            </div>
        @elseif($item->total_price > 0)
            <div class="p-2.5 rounded-3 bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between">
                <span class="text-success fw-semibold fs-13">{{ __('Remaining balance') }}</span>
                <span class="badge bg-success text-white font-fanum">{{ __('Settled') }}</span>
            </div>
        @endif
    </div>

    @if(trim((string) $item->desc) !== '')
        <div class="p-3">
            <div class="text-muted fs-12 mb-1 d-flex align-items-center gap-1">
                <i class="ri-message-line text-primary"></i>
                <span>{{ __('Description') }}</span>
            </div>
            <div class="fs-13 text-secondary">
                {{ $item->desc }}
            </div>
        </div>
    @endif
</div>
