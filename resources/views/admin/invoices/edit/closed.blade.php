<div class="item-list mb-3">
    <div class="p-3">
        <div class="alert {{ $displayStatus === \App\Models\Invoice::FAILED ? 'alert-danger border border-danger-subtle' : 'alert-secondary border border-secondary-subtle' }} d-flex align-items-start gap-3 p-3 mb-0 rounded-3">
            <i class="ri-close-circle-fill fs-3"></i>
            <div>
                <strong class="d-block fs-5">{{ $displayStatus === \App\Models\Invoice::FAILED ? __('Failed') : __('Canceled') }}</strong>
                <span>{{ __('This invoice is closed and no further action is needed.') }}</span>

                @if($declinedReason)
                    <div class="mt-2"><strong>{{ __('Decline reason:') }}</strong> {{ $declinedReason }}</div>
                @endif
                @if(! empty($item->meta['cancel_reason']))
                    <div class="mt-2"><strong>{{ __('Cancel reason:') }}</strong> {{ $item->meta['cancel_reason'] }}</div>
                @endif
                @if(! empty($item->meta['refunded_amount']))
                    <div class="mt-2">
                        <i class="ri-refund-2-line"></i>
                        {{ __(':amount was returned to the customer credit.', ['amount' => number_format((int) $item->meta['refunded_amount'])]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
