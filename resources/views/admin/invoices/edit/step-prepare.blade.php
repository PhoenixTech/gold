<div class="general-form item-list shadow-sm mb-4" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
    <div class="d-flex align-items-center gap-2 p-3 p-md-3.5 border-bottom">
        <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-motorbike-line' }} text-primary fs-18"></i>
        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ $isPickup ? __('Store pickup') : __('Courier delivery') }}</h5>
    </div>

    <div class="p-3 p-md-3.5">
        @if($isPickup)
            <div class="alert alert-info border border-info-subtle d-flex align-items-start gap-2 p-3 mb-3 rounded-3">
                <i class="ri-map-pin-line text-primary fs-5"></i>
                <div>
                    <strong class="d-block text-dark">{{ __('Pickup location') }}</strong>
                    <span class="fs-13 text-secondary">{{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                    @csrf
                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::READY_FOR_PICKUP }}">
                    <button type="submit" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="ri-store-2-line"></i>
                        <span>{{ __('Mark ready for pickup') }}</span>
                    </button>
                </form>
                @if($item->status === \App\Models\Invoice::PAID)
                    <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                        <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="ri-package-line"></i>
                            <span>{{ __('Start preparing') }}</span>
                        </button>
                    </form>
                @endif
            </div>
        @else
            <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                @csrf
                <div class="form-group mb-3">
                    <label for="courier_id" class="form-label fs-13 fw-semibold text-dark">{{ __('Courier') }}</label>
                    <select name="courier_id" id="courier_id" class="form-select @error('courier_id') is-invalid @enderror">
                        <option value="">{{ __('Select a courier') }}</option>
                        @foreach(($couriers ?? collect()) as $courier)
                            <option value="{{ $courier->id }}" @selected((string) old('courier_id', $item->activeDelivery?->courier_id) === (string) $courier->id)>
                                {{ $courier->name }}@if($courier->mobile) — {{ $courier->mobile }}@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text fs-12 text-muted">{{ __('A 4-digit code is sent to the customer by SMS. The courier needs it to finish the delivery.') }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="status" value="{{ \App\Models\Invoice::OUT_FOR_DELIVERY }}" class="btn btn-warning fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="ri-motorbike-line"></i>
                        <span>{{ __('Send for delivery') }}</span>
                    </button>
                    @if($item->status === \App\Models\Invoice::PAID)
                        <button type="submit" name="status" value="{{ \App\Models\Invoice::PROCESSING }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="ri-package-line"></i>
                            <span>{{ __('Start preparing') }}</span>
                        </button>
                    @endif
                    @unless($item->requiresDeliveryCode())
                        <button type="submit" name="status" value="{{ \App\Models\Invoice::COMPLETED }}" class="btn btn-outline-success d-inline-flex align-items-center gap-1"
                                data-confirm="{{ __('This old invoice has no courier code. Mark it as delivered?') }}">
                            <i class="ri-check-double-line"></i>
                            <span>{{ __('Mark as completed') }}</span>
                        </button>
                    @endunless
                </div>
            </form>
        @endif
    </div>
</div>
