<div class="modal fade" id="cancel-invoice-modal" tabindex="-1" aria-hidden="true" aria-labelledby="cancel-invoice-title">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ route('admin.invoice.cancel', $item) }}" method="post">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="cancel-invoice-title">{{ __('Cancel order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                @if($refundAmount > 0)
                    <div class="alert alert-warning border border-warning-subtle rounded-3">
                        <i class="ri-refund-2-line"></i>
                        {{ __('The customer already paid. :amount will be returned to the customer credit.', ['amount' => number_format($refundAmount).' '.__('Toman')]) }}
                    </div>
                @else
                    <div class="alert alert-secondary border border-secondary-subtle rounded-3">
                        {{ __('No money was received yet, so nothing will be refunded.') }}
                    </div>
                @endif
                <p class="text-muted fs-13">{{ __('The reserved stock is released and the customer is notified by SMS.') }}</p>
                <label for="cancel_reason" class="form-label">{{ __('Cancel reason') }} <span class="text-danger">*</span></label>
                <textarea id="cancel_reason" name="reason" rows="3" maxlength="500" class="form-control" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-danger fw-bold">
                    <i class="ri-close-circle-line"></i>
                    {{ $refundAmount > 0 ? __('Cancel and refund') : __('Cancel order') }}
                </button>
            </div>
        </form>
    </div>
</div>
