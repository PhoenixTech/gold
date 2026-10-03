<div class="item-list mb-3">
    <div class="p-3">
        <h4 class="mb-2 text-success"><i class="ri-checkbox-circle-line me-1"></i> {{ $isPickup ? __('Collected') : __('Completed') }}</h4>
        <p class="text-muted mb-0">{{ $isPickup ? __('The customer collected this order from the store. No further action is needed.') : __('This invoice was delivered and confirmed. No further action is needed.') }}</p>
    </div>
</div>
