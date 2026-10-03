<div class="item-list mb-3">
    <div class="p-3">
        <h4 class="mb-3"><i class="ri-bank-card-line me-1"></i> {{ __('Payment') }}</h4>

        @if($reuploadReason)
            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-3 rounded-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-refresh-line text-warning fs-3"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('A clearer receipt was requested from the customer.') }}</strong>
                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $reuploadReason }}</span>
                        @if($item->reuploadRequestedAt())
                            <small class="text-muted fs-12 d-block">
                                <i class="ri-time-line"></i>{{ $item->reuploadRequestedAt()->jdate('Y/m/d H:i') }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($declinedReason)
            <div class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-3 rounded-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-close-circle-line text-danger fs-3"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('A receipt was declined and needs to be uploaded again.') }}</strong>
                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $declinedReason }}</span>
                    </div>
                </div>
            </div>
        @endif

        <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Amount to pay') }}</span><b class="font-fanum">{{ number_format($item->total_price) }} {{ __('Toman') }}</b></li>
            <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Receipts uploaded') }}</span><b class="font-fanum">{{ number_format($item->paymentReceipts->count()) }}</b></li>
        </ul>
        <small class="text-muted d-block mt-3">
            <i class="ri-information-line text-primary"></i>
            {{ __('If no receipt arrives before the deadline, the order fails automatically and the stock is released.') }}
        </small>
    </div>
</div>
