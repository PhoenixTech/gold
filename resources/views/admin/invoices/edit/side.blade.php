@php
    $successfulCount = $customer?->invoices()->whereIn('status', \App\Models\Invoice::successfulStatuses())->count() ?? 0;
    $failedCount = $customer?->invoices()->whereIn('status', [\App\Models\Invoice::CANCELED, \App\Models\Invoice::FAILED])->count() ?? 0;
@endphp

<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between p-3 p-md-3.5 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-user-line text-primary fs-18"></i>
            <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Customer') }}</h5>
        </div>
        @if($customer)
            <a href="{{ route('admin.customer.show', $customer->id) }}" class="btn btn-sm btn-outline-primary px-2 py-0.5 fs-12">
                {{ __('Profile') }}
            </a>
        @endif
    </div>
    <ul class="list-group list-group-flush fs-13">
        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5 px-3">
            <span class="text-muted fs-12">{{ __('Name') }}</span>
            @if($customer)
                <a href="{{ route('admin.customer.show', $customer->id) }}" class="fw-semibold text-dark text-decoration-none">
                    {{ $customer->name }}
                </a>
            @else
                <span class="text-muted">—</span>
            @endif
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5 px-3">
            <span class="text-muted fs-12">{{ __('Mobile') }}</span>
            @if($customer?->mobile)
                <a href="tel:{{ $customer->mobile }}" dir="ltr" class="font-fanum fw-semibold text-primary text-decoration-none d-inline-flex align-items-center gap-1">
                    <i class="ri-phone-line fs-14"></i>
                    <span>{{ $customer->mobile }}</span>
                </a>
            @else
                <span class="text-muted">—</span>
            @endif
        </li>
        @if($item->is_third_party)
            <li class="list-group-item d-flex justify-content-between align-items-center py-2.5 px-3">
                <span class="text-muted fs-12">{{ __('Recipient (gift order)') }}</span>
                <span class="fw-semibold text-dark">{{ $item->recipient_name ?: '—' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2.5 px-3">
                <span class="text-muted fs-12">{{ __('Recipient mobile') }}</span>
                @if($item->recipient_mobile)
                    <a href="tel:{{ $item->recipient_mobile }}" dir="ltr" class="font-fanum fw-semibold text-primary text-decoration-none">
                        {{ $item->recipient_mobile }}
                    </a>
                @else
                    <span class="text-muted">—</span>
                @endif
            </li>
        @endif
        <li class="list-group-item d-flex justify-content-between align-items-center py-2.5 px-3">
            <span class="text-muted fs-12">{{ __('Paid invoices') }} / {{ __('Failed invoices') }}</span>
            <div class="d-flex align-items-center gap-1 font-fanum">
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-2 py-0.5">
                    {{ number_format($successfulCount) }}
                </span>
                <span class="text-muted">/</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-12 px-2 py-0.5">
                    {{ number_format($failedCount) }}
                </span>
            </div>
        </li>
    </ul>
</div>

@unless($isWaitingReceipt || $isWaitingConfirmation)
<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 p-3 p-md-3.5 border-bottom">
        <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-map-pin-line' }} text-primary fs-18"></i>
        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ $isPickup ? __('Pickup location') : __('Delivery address') }}</h5>
    </div>
    <div class="p-3 p-md-3.5 fs-13 text-dark">
        @if($isPickup)
            {{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}
        @else
            {{ $item->address?->address ?: ($item->address_alt ?: '—') }}
        @endif
    </div>
</div>
@endunless

@if(trim((string) $item->desc) !== '')
    <div class="item-list shadow-sm mb-4">
        <div class="d-flex align-items-center gap-2 p-3 p-md-3.5 border-bottom">
            <i class="ri-message-line text-primary fs-18"></i>
            <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Description') }}</h5>
        </div>
        <div class="p-3 p-md-3.5 fs-13 text-secondary">
            {{ $item->desc }}
        </div>
    </div>
@endif
