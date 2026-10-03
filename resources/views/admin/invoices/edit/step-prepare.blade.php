<div class="general-form item-list mb-3" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
    <div class="p-3">
        @if($isPickup)
            <h4 class="mb-3"><i class="ri-store-2-line me-1"></i> {{ __('Store pickup') }}</h4>

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
                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::READY_FOR_PICKUP }}">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="ri-store-2-line me-1"></i>{{ __('Mark ready for pickup') }}
                    </button>
                </form>
                @if($item->status === \App\Models\Invoice::PAID)
                    <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="ri-package-line me-1"></i>{{ __('Start preparing') }}
                        </button>
                    </form>
                @endif
            </div>
        @else
            <h4 class="mb-3"><i class="ri-motorbike-line me-1"></i> {{ __('Courier delivery') }}</h4>

            <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                @csrf
                <div class="form-group mb-3">
                    <label for="courier_id" class="form-label">{{ __('Courier') }}</label>
                    <select name="courier_id" id="courier_id" class="form-select @error('courier_id') is-invalid @enderror">
                        <option value="">{{ __('Select a courier') }}</option>
                        @foreach(($couriers ?? collect()) as $courier)
                            <option value="{{ $courier->id }}" @selected((string) old('courier_id', $item->activeDelivery?->courier_id) === (string) $courier->id)>
                                {{ $courier->name }}@if($courier->mobile) — {{ $courier->mobile }}@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('A 4-digit code is sent to the customer by SMS. The courier needs it to finish the delivery.') }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="status" value="{{ \App\Models\Invoice::OUT_FOR_DELIVERY }}" class="btn btn-warning fw-bold">
                        <i class="ri-motorbike-line"></i> {{ __('Send for delivery') }}
                    </button>
                    @if($item->status === \App\Models\Invoice::PAID)
                        <button type="submit" name="status" value="{{ \App\Models\Invoice::PROCESSING }}" class="btn btn-outline-secondary">
                            <i class="ri-package-line"></i> {{ __('Start preparing') }}
                        </button>
                    @endif
                    @unless($item->requiresDeliveryCode())
                        <button type="submit" name="status" value="{{ \App\Models\Invoice::COMPLETED }}" class="btn btn-outline-success"
                                data-confirm="{{ __('This old invoice has no courier code. Mark it as delivered?') }}">
                            <i class="ri-check-double-line"></i> {{ __('Mark as completed') }}
                        </button>
                    @endunless
                </div>
            </form>
        @endif
    </div>
</div>
