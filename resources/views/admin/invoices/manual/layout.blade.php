@extends('layouts.app')

@section('title')
    {{ __('New shop invoice') }} -
@endsection

@section('content')
    @php
        $stepLabels = [
            'customer' => __('Customer'),
            'items' => __('Items'),
            'payment' => __('Payment'),
            'review' => __('Review'),
        ];
        $stepKeys = array_keys($stepLabels);
        $isComplete = $step === 'complete';
        $currentIndex = $isComplete ? count($stepKeys) : (int) array_search($step, $stepKeys, true);
        $hasDraft = ! $isComplete && ($draft['customer'] !== null || $draft['quantity_ids'] !== []);
        $recordedPayments = $draft['payments'] ?? [];
        $totalPaidSum = isset($invoice)
            ? $invoice->receivedAmount()
            : array_sum(array_map(fn ($p) => (int) ($p['amount'] ?? 0), $recordedPayments));
        $remainingBalance = isset($invoice)
            ? $invoice->remainingReceiptBalance()
            : max(0, $total - $totalPaidSum);
        $displayCustomerName = isset($invoice)
            ? ($invoice->customer?->name ?: '—')
            : (($draft['customer']['name'] ?? '') ?: '—');
        $displayCustomerMobile = isset($invoice)
            ? ($invoice->customer?->mobile ?: '—')
            : ($draft['customer']['mobile'] ?? '—');
        $displayPiecesCount = isset($invoice)
            ? $invoice->orders->count()
            : $lines->count();
    @endphp

    <div class="manual-invoice pb-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark fs-18">
                <i class="ri-store-2-line text-primary"></i>
                {{ __('New shop invoice') }}
            </h4>
            <a href="{{ route('admin.shop-invoice.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="ri-arrow-right-line"></i>
                <span>{{ __('In-person sales') }}</span>
            </a>
        </div>

        <div class="item-list p-3 mb-4 shadow-sm">
            <ol class="list-unstyled d-flex align-items-center justify-content-between mb-0 p-0" aria-label="{{ __('Progress') }}">
                @foreach($stepLabels as $key => $label)
                    @php
                        $index = array_search($key, $stepKeys, true);
                        $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'todo');
                    @endphp
                    <li class="d-flex align-items-center gap-2 {{ $index < count($stepKeys) - 1 ? 'flex-grow-1' : '' }}" @if($state === 'current') aria-current="step" @endif>
                        @if($state === 'done')
                            @if(! $isComplete)
                                <a href="{{ route('admin.shop-invoice.create', ['step' => $key]) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                            @else
                                <div class="d-flex align-items-center gap-2">
                            @endif
                                <span class="badge rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                                    <i class="ri-check-line fs-14"></i>
                                </span>
                                <span class="fw-semibold text-dark fs-13 d-none d-sm-inline">{{ $label }}</span>
                            @if(! $isComplete)
                                </a>
                            @else
                                </div>
                            @endif
                        @elseif($state === 'current')
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle bg-primary text-white shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                                    {{ $index + 1 }}
                                </span>
                                <span class="fw-bold text-dark fs-13">{{ $label }}</span>
                            </div>
                        @else
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle bg-light text-muted border d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-muted fs-13 d-none d-sm-inline">{{ $label }}</span>
                            </div>
                        @endif

                        @if($index < count($stepKeys) - 1)
                            <div class="flex-grow-1 mx-2 text-center text-muted opacity-50 d-none d-md-block">
                                <i class="ri-arrow-left-s-line fs-18"></i>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        @include('components.err')

        <div class="row g-3 g-xl-4">
            <div class="col-lg-8">
                @yield('step')
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 1rem; z-index: 10;">
                    <div class="item-list shadow-sm">
                        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 fw-bold fs-15 text-dark d-flex align-items-center gap-2">
                                <i class="ri-file-list-3-line text-primary fs-18"></i>
                                <span>{{ __('Sale summary') }}</span>
                            </h5>
                            @if($displayPiecesCount > 0)
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum">
                                    {{ number_format($displayPiecesCount) }} {{ __('Pieces') }}
                                </span>
                            @endif
                        </div>

                        <div class="p-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted fs-12">{{ __('Customer') }}</span>
                                <b class="text-dark fs-13">{{ $displayCustomerName }}</b>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted fs-12">{{ __('Mobile') }}</span>
                                <b class="font-fanum text-dark fs-13" dir="ltr">{{ $displayCustomerMobile }}</b>
                            </div>
                        </div>

                        <div class="p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted fs-13">{{ __('Pieces') }}</span>
                                <span class="font-fanum fw-semibold text-dark fs-13">{{ number_format($displayPiecesCount) }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted fs-13">{{ __('Total price') }}</span>
                                <span class="font-fanum fw-bold text-dark fs-14">
                                    {{ number_format($total) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="text-muted fs-13">{{ __('Paid amount') }}</span>
                                <span class="font-fanum fw-bold text-success fs-14">
                                    {{ number_format($totalPaidSum) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </span>
                            </div>

                            @if($remainingBalance > 0)
                                <div class="p-2.5 rounded-3 bg-danger-subtle border border-danger-subtle d-flex align-items-center justify-content-between">
                                    <span class="text-danger fw-semibold fs-13">{{ __('Remaining balance') }}</span>
                                    <span class="font-fanum fw-bold text-danger fs-15">
                                        {{ number_format($remainingBalance) }} <small class="fw-normal fs-11">{{ __('Toman') }}</small>
                                    </span>
                                </div>
                            @elseif($total > 0)
                                <div class="p-2.5 rounded-3 bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between">
                                    <span class="text-success fw-semibold fs-13">{{ __('Remaining balance') }}</span>
                                    <span class="badge bg-success text-white font-fanum">{{ __('Settled') }}</span>
                                </div>
                            @endif

                            <div class="mt-3 pt-2 text-muted fs-12 d-flex align-items-start gap-1 border-top">
                                <i class="ri-information-line text-primary mt-0.5"></i>
                                <span>{{ __('Prices use the current gold price and are calculated again when the invoice is saved.') }}</span>
                            </div>

                            @if($hasDraft)
                                <form method="POST" action="{{ route('admin.shop-invoice.store') }}" class="mt-3" data-confirm="{{ __('Discard this sale? Nothing has been saved yet.') }}">
                                    @csrf
                                    <input type="hidden" name="step" value="cancel">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                        <i class="ri-delete-bin-line"></i>
                                        <span>{{ __('Discard this sale') }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
