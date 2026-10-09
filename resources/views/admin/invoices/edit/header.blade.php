<div class="item-list shadow-sm mb-4 p-3 p-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="d-inline-flex align-items-center gap-1 badge bg-light text-dark border font-monospace fs-12 px-2.5 py-1 mb-2">
                <span class="text-muted">{{ __('Invoice') }}</span>
                <span class="fw-bold">#{{ $item->hash }}</span>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="{{ $item->statusBadgeClass() }} fs-13 px-3 py-1.5">{{ $item->statusLabel() }}</span>
                <span class="badge bg-light text-dark border fs-12 px-2.5 py-1.5 d-inline-flex align-items-center gap-1" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
                    <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-motorbike-line' }} text-primary"></i>
                    <span>{{ $isPickup ? __('Store pickup') : __('Courier delivery') }}</span>
                </span>
                @if($item->isManual())
                    <span class="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle fs-12 px-2.5 py-1.5 d-inline-flex align-items-center gap-1">
                        <i class="ri-store-2-line"></i>
                        <span>{{ __('Shop sale') }}</span>
                    </span>
                    @if($item->createdBy)
                        <span class="text-muted fs-12">{{ __('Created by') }}: <strong class="text-dark">{{ $item->createdBy->name }}</strong></span>
                    @endif
                @endif
            </div>
            @if($persianDeadline && in_array($displayStatus, [\App\Models\Invoice::WAITING_RECEIPT, \App\Models\Invoice::WAITING_CONFIRMATION], true))
                <div class="text-muted fs-12 mt-2 d-flex align-items-center gap-1">
                    <i class="ri-time-line text-warning"></i>
                    @if($offlineIsExpired)
                        <span>{{ __('Customer had :hours hours to upload a receipt. Deadline was :date.', ['hours' => \App\Models\Invoice::offlinePaymentHours(), 'date' => $persianDeadline]) }}</span>
                    @else
                        <span>{{ __('Deadline:') }} <strong class="text-dark">{{ $persianDeadline }}</strong></span>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="text-end">
                <div class="text-muted fs-12 fw-medium mb-0.5">{{ __('Total price') }}</div>
                <div class="d-flex align-items-baseline justify-content-end gap-1">
                    <strong class="fs-4 font-fanum text-dark">{{ number_format($item->total_price) }}</strong>
                    <small class="text-muted fs-12">{{ config('app.currency.symbol') }}</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1.5">
                <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-outline-secondary px-2.5 py-1.5" title="{{ __('Open the order board') }}">
                    <i class="ri-dashboard-2-line"></i>
                </a>
                @unless($isPickup)
                    <a href="{{ route('admin.invoice.shipping-label', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary px-2.5 py-1.5" title="{{ __('Shipping label') }}">
                        <i class="ri-printer-line"></i>
                    </a>
                @endunless
                @if($item->canPrint())
                    <a href="{{ route('admin.invoice.print', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary px-2.5 py-1.5" title="{{ __('Print invoice') }}">
                        <i class="ri-file-text-line"></i>
                    </a>
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-sm btn-outline-danger px-2.5 py-1.5 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancel-invoice-modal">
                        <i class="ri-close-circle-line"></i>
                        <span>{{ __('Cancel order') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
