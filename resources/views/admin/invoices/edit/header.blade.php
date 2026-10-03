<div class="item-list mb-3 p-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 rounded border border-primary-subtle fs-12 d-inline-block mb-2">
                {{ __('Invoice') }} / #{{ $item->hash }}
            </code>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="{{ $item->statusBadgeClass() }} fs-6">{{ $item->statusLabel() }}</span>
                <span class="text-muted" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
                    <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-motorbike-line' }} text-primary"></i>
                    {{ $isPickup ? __('Store pickup') : __('Courier delivery') }}
                </span>
            </div>
            @if($persianDeadline && in_array($displayStatus, [\App\Models\Invoice::WAITING_RECEIPT, \App\Models\Invoice::WAITING_CONFIRMATION], true))
                <div class="text-muted fs-13 mt-2">
                    <i class="ri-time-line"></i>
                    @if($offlineIsExpired)
                        {{ __('Customer had :hours hours to upload a receipt. Deadline was :date.', ['hours' => \App\Models\Invoice::offlinePaymentHours(), 'date' => $persianDeadline]) }}
                    @else
                        {{ __('Deadline:') }} <b>{{ $persianDeadline }}</b>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="text-end">
                <div class="text-muted fs-12">{{ __('Total price') }}</div>
                <strong class="fs-4 font-fanum">{{ number_format($item->total_price) }}</strong>
                <span class="text-muted">{{ config('app.currency.symbol') }}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Open the order board') }}">
                    <i class="ri-dashboard-2-line"></i>
                </a>
                @unless($isPickup)
                    <a href="{{ route('admin.invoice.shipping-label', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" title="{{ __('Shipping label') }}">
                        <i class="ri-printer-line"></i>
                    </a>
                @endunless
                @if($item->canPrint())
                    <a href="{{ route('admin.invoice.print', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" title="{{ __('Print invoice') }}">
                        <i class="ri-file-text-line"></i>
                    </a>
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancel-invoice-modal">
                        <i class="ri-close-circle-line"></i> {{ __('Cancel order') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
