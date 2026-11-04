<div class="item-list shadow-sm mb-4 p-3 p-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <a href="{{ route($isShopSale ? 'admin.shop-invoice.index' : 'admin.invoice.index') }}"
               class="btn btn-sm btn-outline-secondary px-2 py-1 mt-1"
               title="{{ $isShopSale ? __('In-person sales') : __('Website sales') }}"
               aria-label="{{ $isShopSale ? __('In-person sales') : __('Website sales') }}">
                <i class="ri-arrow-right-line"></i>
            </a>
            <div>
                <h4 class="fw-bold text-dark fs-18 mb-2">
                    {{ __('Invoice') }} <span class="font-monospace" dir="ltr">#{{ $item->hash }}</span>
                </h4>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="{{ $item->statusBadgeClass() }} fs-13 px-3 py-1.5">{{ $item->statusLabel() }}</span>
                    <span class="badge bg-light text-dark border fs-12 px-2.5 py-1.5 d-inline-flex align-items-center gap-1" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
                        <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-motorbike-line' }} text-primary"></i>
                        <span>{{ $isPickup ? __('Store pickup') : __('Courier delivery') }}</span>
                    </span>
                    @if($isShopSale)
                        <span class="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle fs-12 px-2.5 py-1.5">{{ __('Shop sale') }}</span>
                        @if($item->createdBy)
                            <span class="text-muted fs-12">{{ __('Created by') }}: <strong class="text-dark">{{ $item->createdBy->name }}</strong></span>
                        @endif
                    @endif
                </div>
                @if($persianDeadline && in_array($displayStatus, [\App\Models\Invoice::WAITING_RECEIPT, \App\Models\Invoice::WAITING_CONFIRMATION], true))
                    <div class="fs-12 mt-2 d-flex align-items-center gap-1 {{ $offlineIsExpired ? 'text-danger' : 'text-muted' }}">
                        <i class="ri-timer-line"></i>
                        <span>{{ __('Deadline:') }} <strong>{{ $persianDeadline }}</strong></span>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="text-end">
                <div class="text-muted fs-12 fw-medium mb-0.5">{{ __('Total price') }}</div>
                <strong class="fs-4 font-fanum text-dark">{{ number_format($item->total_price) }}</strong>
                <small class="text-muted fs-12">{{ __('Toman') }}</small>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-1.5">
                @if($item->canPrint())
                    <a href="{{ route('admin.invoice.print', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="ri-printer-line"></i>
                        <span>{{ __('Print invoice') }}</span>
                    </a>
                    @unless($isPickup)
                        <a href="{{ route('admin.invoice.shipping-label', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="ri-price-tag-3-line"></i>
                            <span>{{ __('Shipping label') }}</span>
                        </a>
                    @endunless
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancel-invoice-modal">
                        <i class="ri-close-circle-line"></i>
                        <span>{{ __('Cancel order') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
