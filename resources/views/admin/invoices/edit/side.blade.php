@php
    $successfulCount = $customer?->invoices()->whereIn('status', \App\Models\Invoice::successfulStatuses())->count() ?? 0;
    $failedCount = $customer?->invoices()->whereIn('status', [\App\Models\Invoice::CANCELED, \App\Models\Invoice::FAILED])->count() ?? 0;
@endphp

<div class="item-list mb-3">
    <h5 class="p-3 mb-0"><i class="ri-user-line"></i> {{ __('Customer') }}</h5>
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">{{ __('Name') }}</span>
            <a href="{{ route('admin.customer.show', $customer->id) }}"><b>{{ $customer->name }}</b></a>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">{{ __('Mobile') }}</span>
            <a href="tel:{{ $customer->mobile }}" dir="ltr" title="{{ __('Call customer') }}"><b>{{ $customer->mobile }}</b></a>
        </li>
        @if($item->is_third_party)
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">{{ __('Recipient (gift order)') }}</span>
                <b>{{ $item->recipient_name ?: '—' }}</b>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">{{ __('Recipient mobile') }}</span>
                <a href="tel:{{ $item->recipient_mobile }}" dir="ltr" title="{{ __('Call recipient') }}"><b>{{ $item->recipient_mobile ?: '—' }}</b></a>
            </li>
        @endif
        <li class="list-group-item d-flex justify-content-between">
            <span class="text-muted">{{ __('Paid invoices') }} / {{ __('Failed invoices') }}</span>
            <b class="font-fanum">{{ number_format($successfulCount) }} / {{ number_format($failedCount) }}</b>
        </li>
    </ul>
</div>

@unless($isWaitingReceipt || $isWaitingConfirmation)
<div class="item-list mb-3">
    <h5 class="p-3 mb-0">
        <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-map-pin-line' }}"></i>
        {{ $isPickup ? __('Pickup location') : __('Delivery address') }}
    </h5>
    <p class="px-3 pb-3 mb-0">
        @if($isPickup)
            {{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}
        @else
            {{ $item->address?->address ?: ($item->address_alt ?: '—') }}
        @endif
    </p>
</div>
@endunless

@if(trim((string) $item->desc) !== '')
    <div class="item-list mb-3">
        <h5 class="p-3 mb-0"><i class="ri-message-line"></i> {{ __('Description') }}</h5>
        <p class="px-3 pb-3 mb-0">{{ $item->desc }}</p>
    </div>
@endif
