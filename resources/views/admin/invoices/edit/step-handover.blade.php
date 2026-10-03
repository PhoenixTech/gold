<div class="general-form item-list mb-3" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
    <div class="p-3">
        @if($isPickup)
            <h4 class="mb-3"><i class="ri-store-2-line me-1"></i> {{ __('Customer pickup') }}</h4>

            <div class="alert alert-info border border-info-subtle d-flex align-items-start gap-2 p-3 mb-3 rounded-3">
                <i class="ri-map-pin-line text-primary fs-5"></i>
                <div>
                    <strong class="d-block">{{ __('Pickup location') }}</strong>
                    <span>{{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                    @csrf
                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::COMPLETED }}">
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="ri-checkbox-circle-line me-1"></i>{{ __('Mark as collected') }}
                    </button>
                </form>
                <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                    @csrf
                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="ri-arrow-go-back-line me-1"></i>{{ __('Return to preparation') }}
                    </button>
                </form>
            </div>
        @else
            <h4 class="mb-3"><i class="ri-motorbike-line me-1"></i> {{ __('Courier delivery') }}</h4>

            @if($item->activeDelivery)
                @php $delivery = $item->activeDelivery; @endphp
                <div class="p-3 border rounded bg-light mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                        <div>
                            <div class="fw-semibold">
                                {{ $delivery->courier?->name }}
                                <span class="ms-1 {{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                            </div>
                            <div class="text-muted fs-12">
                                @if($delivery->sms_sent_at)
                                    <span class="me-2">{{ __('SMS sent') }} {{ \App\Models\Invoice::formatPersianDateTime($delivery->sms_sent_at) }}</span>
                                @endif
                                @if($delivery->failed_attempts)
                                    <span>{{ __('Failed attempts') }}: {{ $delivery->failed_attempts }}</span>
                                @endif
                            </div>
                        </div>
                        <form action="{{ route('admin.invoice.resend-delivery-code', $item) }}" method="post" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="ri-send-plane-line"></i> {{ __('Resend delivery code') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-7">
                        <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                            @csrf
                            <input type="hidden" name="status" value="{{ \App\Models\Invoice::OUT_FOR_DELIVERY }}">
                            <label class="form-label">{{ __('Reassign to another courier') }}</label>
                            <div class="input-group">
                                <select name="courier_id" class="form-select" required>
                                    <option value="">{{ __('Select a courier') }}</option>
                                    @foreach(($couriers ?? collect()) as $courier)
                                        <option value="{{ $courier->id }}" @selected($delivery->courier_id === $courier->id)>{{ $courier->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">{{ __('Reassign') }}</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                            @csrf
                            <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                            <label class="form-label">{{ __('Cancel the delivery') }}</label>
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="ri-arrow-go-back-line"></i> {{ __('Return to preparation') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-0 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-alarm-warning-fill text-warning fs-3"></i>
                        <div>
                            <strong class="d-block text-dark">{{ __('No active courier delivery.') }}</strong>
                            <span class="text-muted fs-13">{{ __('The delivery may have been rejected or cancelled.') }}</span>
                        </div>
                    </div>
                    <form action="{{ route('admin.invoice.update', $item) }}" method="post" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                        <button type="submit" class="btn btn-sm btn-warning fw-bold px-3">
                            <i class="ri-arrow-go-back-line me-1"></i>{{ __('Return to preparation') }}
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>
</div>
