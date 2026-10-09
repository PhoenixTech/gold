<div class="item-list shadow-sm mb-4">
    <div class="p-4 text-center">
        <div class="mb-2">
            <i class="ri-checkbox-circle-fill text-success" style="font-size: 2.75rem;"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">{{ $isPickup ? __('Collected') : __('Completed') }}</h5>
        <p class="text-muted fs-13 mb-0">{{ $isPickup ? __('The customer collected this order from the store. No further action is needed.') : __('This invoice was delivered and confirmed. No further action is needed.') }}</p>
    </div>
</div>
