@extends('admin.invoices.manual.layout')

@section('step')
    @if($lines->isNotEmpty())
        <div class="alert alert-info border border-info-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
            <span class="d-flex align-items-center gap-2">
                <i class="ri-information-line fs-5 text-info"></i>
                <span>{{ __('You have an unfinished sale with :count pieces.', ['count' => $lines->count()]) }}</span>
            </span>
            <a href="{{ route('admin.invoice.create', ['step' => 'items']) }}" class="btn btn-sm btn-primary">
                {{ __('Continue the sale') }}
            </a>
        </div>
    @endif

    <div class="item-list shadow-sm mb-4">
        <div class="p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="ri-user-3-line text-primary fs-18"></i>
                <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Who is buying?') }}</h5>
            </div>
            <p class="text-muted fs-13 mb-4">{{ __('Enter the customer mobile. If the number is already registered, that customer is used.') }}</p>

            <form method="POST" action="{{ route('admin.invoice.store') }}">
                @csrf
                <input type="hidden" name="step" value="customer">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="manual-mobile" class="form-label fs-13 fw-semibold text-dark">
                            {{ __('Customer mobile') }} <span class="text-danger">*</span>
                        </label>
                        <input id="manual-mobile" name="mobile" type="tel" inputmode="numeric" dir="ltr" maxlength="15" autocomplete="off"
                               class="form-control font-fanum @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $draft['customer']['mobile'] ?? '') }}" placeholder="09xxxxxxxxx" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label for="manual-name" class="form-label fs-13 fw-semibold text-dark">
                            {{ __('Customer name') }}
                        </label>
                        <input id="manual-name" name="name" type="text" maxlength="255"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $draft['customer']['name'] ?? '') }}">
                        <div class="form-text fs-12 text-muted">{{ __('Only needed for a new customer.') }}</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-2">
                    <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5">
                        <span>{{ __('Next') }}</span>
                        <i class="ri-arrow-left-line"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
