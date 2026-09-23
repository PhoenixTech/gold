@extends('layouts.app')

@section('title')
    {{ __('Order board') }} -
@endsection

@section('content')
<div class="container-fluid px-0 mb-5">
    {{-- Header --}}
    <header class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-12 mb-2 d-inline-block">
                {{ __('Operational order board') }}
            </code>
            <h1 class="h3 fw-bold text-dark mb-1">{{ __('Manager dashboard') }}</h1>
            <p class="text-muted fs-13 mb-0">
                {{ __('Clinic-style daily workflow table for tracking and processing active orders.') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.invoice.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="ri-file-list-3-line"></i>
                {{ __('All invoices') }}
            </a>
            <a href="{{ route('admin.summary.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="ri-pie-chart-2-line"></i>
                {{ __('Shop summary') }}
            </a>
        </div>
    </header>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.order-board.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-3 fs-14">
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'active'])) }}" 
                           class="text-decoration-none fw-semibold {{ $scope === 'active' ? 'text-primary' : 'text-muted' }}">
                            <i class="ri-fire-line me-1"></i>{{ __('Active orders') }}
                            <span class="badge {{ $scope === 'active' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-1 rounded-pill" title="{{ __('Active orders count') }}">{{ number_format($activeCount) }}</span>
                        </a>
                        <span class="text-muted opacity-25">|</span>
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'completed'])) }}" 
                           class="text-decoration-none fw-semibold {{ $scope === 'completed' ? 'text-primary' : 'text-muted' }}">
                            <i class="ri-check-double-line me-1"></i>{{ __('Completed orders') }}
                            <span class="badge {{ $scope === 'completed' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-1 rounded-pill" title="{{ __('Completed orders count') }}">{{ number_format($completedCount) }}</span>
                        </a>
                        <span class="text-muted opacity-25">|</span>
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'all'])) }}" 
                           class="text-decoration-none fw-semibold {{ $scope === 'all' ? 'text-primary' : 'text-muted' }}">
                            {{ __('All orders') }}
                            <span class="badge {{ $scope === 'all' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} ms-1 rounded-pill" title="{{ __('All orders count') }}">{{ number_format($allCount) }}</span>
                        </a>
                    </div>
                    <input type="hidden" name="scope" value="{{ $scope }}">
                </div>

                <div class="col-12 col-sm">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="ri-search-line"></i></span>
                        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ __('Search order, customer code, phone...') }}">
                    </div>
                </div>

                <div class="col-auto d-flex align-items-center gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ri-filter-3-line me-1"></i>{{ __('Filter') }}
                    </button>
                    @if($search)
                        <a href="{{ route('admin.order-board.index', ['scope' => $scope]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Clear filters') }}">
                            <i class="ri-refresh-line"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Order Board Table --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" id="orderBoardTable">
                <thead class="table-light border-bottom">
                    <tr class="fs-13 text-dark align-middle">
                        <th style="width: 60px;" class="py-3 cursor-pointer sort-header" data-sort="row">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('#') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 110px;" class="py-3 cursor-pointer sort-header" data-sort="customer_code">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Customer code') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="width: 60px;" class="py-3 text-center" title="{{ __('Click phone icon in any row to expand details') }}">
                            <i class="ri-phone-line text-muted fs-15"></i>
                        </th>
                        <th style="min-width: 100px;" class="py-3">{{ __('Province') }}</th>
                        <th style="min-width: 150px;" class="py-3 cursor-pointer sort-header" data-sort="date">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Order & Date') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 120px;" class="py-3 cursor-pointer sort-header" data-sort="payment" title="{{ __('Click to prioritize unpaid orders') }}">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Payment') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 130px;" class="py-3 cursor-pointer sort-header" data-sort="confirm" title="{{ __('Click to prioritize pending confirmation') }}">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Payment confirmation') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 120px;" class="py-3 cursor-pointer sort-header" data-sort="settle" title="{{ __('Click to prioritize unsettled orders') }}">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Settlement') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 120px;" class="py-3 cursor-pointer sort-header" data-sort="courier" title="{{ __('Click to prioritize pending courier') }}">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Courier') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                        <th style="min-width: 130px;" class="py-3 cursor-pointer sort-header" data-sort="delivery" title="{{ __('Click to prioritize undelivered orders') }}">
                            <span class="d-inline-flex align-items-center gap-1">{{ __('Delivered to customer') }}<i class="ri-arrow-up-down-line text-muted fs-11"></i></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="order-main-row" 
                            id="order-row-{{ $order['id'] }}"
                            data-row="{{ $order['index'] }}"
                            data-customer_code="{{ $order['customer_code'] }}"
                            data-date="{{ $order['id'] }}"
                            data-payment="{{ $order['stages']['payment']['done'] ? 1 : 0 }}"
                            data-confirm="{{ $order['stages']['confirm']['done'] ? 1 : 0 }}"
                            data-settle="{{ $order['stages']['settle']['done'] ? 1 : 0 }}"
                            data-courier="{{ $order['stages']['courier']['done'] ? 1 : 0 }}"
                            data-delivery="{{ $order['stages']['delivery']['done'] ? 1 : 0 }}"
                        >
                            {{-- 1. Row # --}}
                            <td class="fw-bold font-monospace text-muted fs-13">{{ $order['index'] }}</td>

                            {{-- 2. Customer Code --}}
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace fs-12 px-2 py-1">
                                    {{ $order['customer_code'] }}
                                </span>
                            </td>

                            {{-- 3. Phone Toggle Button --}}
                            <td>
                                <button type="button" class="btn btn-sm btn-light border border-secondary-subtle text-primary p-1 rounded phone-toggle-btn"
                                        data-target="#subrow-{{ $order['id'] }}" data-stage="contact" title="{{ __('View phone and address') }}">
                                    <i class="ri-phone-line fs-14"></i>
                                </button>
                            </td>

                            {{-- 4. Province --}}
                            <td class="fs-13 fw-semibold text-dark">{{ $order['province'] }}</td>

                            {{-- 5. Order Code & Date --}}
                            <td class="text-start px-3">
                                <div class="d-flex flex-column">
                                    <a href="{{ route('admin.invoice.show', $order['hash']) }}" class="fw-bold font-monospace text-primary text-decoration-none fs-13" title="{{ __('View invoice') }}">
                                        #{{ $order['hash'] }}
                                    </a>
                                    <span class="text-muted font-monospace fs-11 mt-0.5">
                                        <i class="ri-calendar-line me-0.5"></i>{{ $order['date_persian'] }}
                                        @if($order['time_persian'])<span class="ms-1 text-black-50">{{ $order['time_persian'] }}</span>@endif
                                    </span>
                                </div>
                            </td>

                            {{-- 6-10. Workflow Stages Loop (click to expand stage details) --}}
                            @foreach(['payment', 'confirm', 'settle', 'courier', 'delivery'] as $key)
                                @php $stage = $order['stages'][$key]; @endphp
                                <td>
                                    <button type="button"
                                            class="stage-toggle-btn w-100 border-0 p-0 bg-transparent"
                                            data-target="#subrow-{{ $order['id'] }}"
                                            data-stage="{{ $key }}"
                                            title="{{ $stage['title'] }}">
                                        <span class="badge {{ $stage['done'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} p-2 fs-12 w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                            <i class="{{ $stage['done'] ? ($key === 'delivery' ? 'ri-check-double-fill' : ($key === 'courier' ? ($order['invoice']->isPickup() ? 'ri-store-2-fill' : 'ri-truck-fill') : 'ri-checkbox-circle-fill')) : ($order['invoice']->isPickup() && $key === 'courier' ? 'ri-store-2-line' : 'ri-time-fill') }}"></i>
                                            <span>{{ $stage['text'] }}</span>
                                        </span>
                                    </button>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Sub-row: expandable detail cards (contact / payment / confirm / settle / courier / delivery) --}}
                        <tr class="order-subrow d-none bg-white border-top border-bottom" id="subrow-{{ $order['id'] }}">
                            <td colspan="10" class="p-3 text-start bg-light bg-opacity-50">

                                {{-- Contact card (phone icon) --}}
                                <div class="subcard d-none" data-subcard="contact">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center gap-4 flex-wrap">
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Customer name') }}</span>
                                                <strong class="text-dark fs-14">{{ $order['customer_name'] }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Phone number') }}</span>
                                                <a href="tel:{{ $order['customer_mobile'] }}" class="fw-bold font-monospace text-primary text-decoration-none fs-14 d-inline-flex align-items-center gap-1">
                                                    <i class="ri-phone-fill"></i>{{ $order['customer_mobile'] }}
                                                </a>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('City / Province') }}</span>
                                                <span class="text-dark fs-13 fw-semibold">{{ $order['province'] }} @if($order['city']) - {{ $order['city'] }} @endif</span>
                                            </div>
                                            <div style="max-width: 420px;">
                                                <span class="text-muted fs-12 d-block">{{ __('Shipping address') }}</span>
                                                <span class="text-dark fs-13">{{ $order['address'] }} @if($order['postal_code'])<small class="text-muted font-monospace ms-1">({{ __('Postal code') }}: {{ $order['postal_code'] }})</small>@endif</span>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Total price') }}</span>
                                                <strong class="text-dark font-monospace fs-14">{{ number_format($order['total_price']) }} <small class="text-muted">{{ config('app.currency.symbol') }}</small></strong>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ route('admin.invoice.show', $order['hash']) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                                <i class="ri-eye-line"></i>{{ __('View invoice') }}
                                            </a>
                                            <a href="{{ route('admin.invoice.edit', $order['hash']) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                                <i class="ri-edit-2-line"></i>{{ __('Edit') }}
                                            </a>
                                            @if($order['invoice']->canPrint())
                                                <a href="{{ route('admin.invoice.print', $order['hash']) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" target="_blank">
                                                    <i class="ri-printer-line"></i>{{ __('Print') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Payment card: customer receipts --}}
                                @php $d = $order['details']['payment']; @endphp
                                <div class="subcard d-none" data-subcard="payment">
                                    <div class="bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                                            <strong class="text-dark fs-14 d-inline-flex align-items-center gap-2">
                                                <i class="ri-receipt-line text-primary fs-5"></i>{{ __('Customer receipts') }}
                                            </strong>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12 px-2 py-1">
                                                {{ $d['receipts']->count() }} {{ __('receipt(s)') }}
                                            </span>
                                        </div>
                                        @if($d['receipts']->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-3">
                                                @foreach($d['receipts'] as $r)
                                                    <a href="{{ $r['url'] }}" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none p-2 rounded-3 border border-secondary-subtle bg-light" title="{{ __('Open receipt') }}">
                                                        @if($r['is_image'])
                                                            <img src="{{ $r['url'] }}" alt="{{ $r['name'] }}" class="rounded border bg-white" style="width: 56px; height: 56px; object-fit: cover;">
                                                        @else
                                                            <span class="d-flex align-items-center justify-content-center bg-white border rounded" style="width: 56px; height: 56px;">
                                                                <i class="ri-file-3-line fs-4 text-secondary"></i>
                                                            </span>
                                                        @endif
                                                        <span class="d-flex flex-column">
                                                            <span class="fw-semibold text-dark fs-13 text-truncate" style="max-width: 180px;">{{ $r['name'] }}</span>
                                                            <span class="text-muted fs-11">{{ $r['size'] }}</span>
                                                            <span class="text-muted fs-11 d-inline-flex align-items-center gap-1"><i class="ri-calendar-line"></i>{{ $r['date'] }}</span>
                                                            <span class="text-muted fs-11 d-inline-flex align-items-center gap-1"><i class="ri-user-line"></i>{{ $r['uploader'] }}</span>
                                                        </span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted fs-13 d-flex align-items-center gap-2">
                                                <i class="ri-inbox-line"></i>{{ __('No receipt uploaded yet.') }}
                                            </div>
                                        @endif
                                        @if($d['declined_at'])
                                            <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between text-warning-emphasis fs-12">
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <i class="ri-error-warning-fill fs-5"></i>
                                                    <span class="fw-semibold">{{ __('Receipt declined at') }} <span class="font-monospace">{{ $d['declined_at'] }}</span></span>
                                                    @if($d['decline_reason'])
                                                        <span class="text-muted ms-1">— {{ $d['decline_reason'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('Declined') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Confirm card --}}
                                @php $d = $order['details']['confirm']; @endphp
                                <div class="subcard d-none" data-subcard="confirm">
                                    <div class="bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                            <i class="ri-shield-check-line text-primary fs-5"></i>
                                            <strong class="text-dark fs-14">{{ __('Payment confirmation') }}</strong>
                                        </div>
                                        <div class="d-flex align-items-start gap-4 flex-wrap">
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Confirmed at') }}</span>
                                                <strong class="text-dark fs-13 font-monospace">{{ $d['confirmed_at'] ?? '—' }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Confirmed by') }}</span>
                                                <strong class="text-dark fs-13">{{ $d['confirmed_by'] ?? '—' }}</strong>
                                            </div>
                                            @if($d['reference'])
                                                <div>
                                                    <span class="text-muted fs-12 d-block">{{ __('Payment reference') }}</span>
                                                    <span class="text-dark fs-13 font-monospace">{{ $d['reference'] }}</span>
                                                </div>
                                            @endif
                                            @if($d['receipt'])
                                                <a href="{{ $d['receipt']['url'] }}" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none p-2 rounded-3 border border-secondary-subtle bg-light" title="{{ __('Open receipt') }}">
                                                    @if($d['receipt']['is_image'])
                                                        <img src="{{ $d['receipt']['url'] }}" alt="{{ $d['receipt']['name'] }}" class="rounded border bg-white" style="width: 48px; height: 48px; object-fit: cover;">
                                                    @else
                                                        <span class="d-flex align-items-center justify-content-center bg-white border rounded" style="width: 48px; height: 48px;">
                                                            <i class="ri-file-3-line fs-4 text-secondary"></i>
                                                        </span>
                                                    @endif
                                                    <span class="d-flex flex-column">
                                                        <span class="text-muted fs-12">{{ __('Accepted receipt') }}</span>
                                                        <span class="fw-semibold text-dark fs-13 text-truncate" style="max-width: 180px;">{{ $d['receipt']['name'] }}</span>
                                                    </span>
                                                </a>
                                            @endif
                                            @if(!$d['done'])
                                                <div class="text-muted fs-13 d-flex align-items-center gap-2">
                                                    <i class="ri-time-line"></i>{{ __('Awaiting admin verification') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Settle card --}}
                                @php $d = $order['details']['settle']; @endphp
                                <div class="subcard d-none" data-subcard="settle">
                                    <div class="bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                            <i class="ri-scales-3-line text-primary fs-5"></i>
                                            <strong class="text-dark fs-14">{{ __('Settlement') }}</strong>
                                        </div>
                                        <div class="d-flex align-items-start gap-4 flex-wrap">
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Settled at') }}</span>
                                                <strong class="text-dark fs-13 font-monospace">{{ $d['settled_at'] ?? '—' }}</strong>
                                            </div>
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Confirmed by') }}</span>
                                                <strong class="text-dark fs-13">{{ $d['confirmed_by'] ?? '—' }}</strong>
                                            </div>
                                            @if(!$d['done'])
                                                <div class="text-muted fs-13 d-flex align-items-center gap-2">
                                                    <i class="ri-time-line"></i>{{ __('Unsettled balance') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Courier card --}}
                                @php $d = $order['details']['courier']; $isPickup = $order['invoice']->isPickup(); @endphp
                                <div class="subcard d-none" data-subcard="courier">
                                    <div class="bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                            <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-truck-line' }} text-primary fs-5"></i>
                                            <strong class="text-dark fs-14">{{ $isPickup ? __('In-person gallery pickup') : __('Courier') }}</strong>
                                        </div>
                                        @if($isPickup)
                                            <div class="alert alert-info border border-info-subtle rounded-3 p-2.5 mb-0 d-flex align-items-center gap-2 fs-13">
                                                <i class="ri-information-line fs-5 text-primary"></i>
                                                <span>{{ __('This is an in-person gallery pickup order. No courier assignment required.') }}</span>
                                            </div>
                                        @elseif($d)
                                            <div class="d-flex align-items-start gap-4 flex-wrap">
                                                <div>
                                                    <span class="text-muted fs-12 d-block">{{ __('Courier name') }}</span>
                                                    <strong class="text-dark fs-13">{{ $d['name'] }}</strong>
                                                </div>
                                                @if($d['email'])
                                                    <div>
                                                        <span class="text-muted fs-12 d-block">{{ __('Email') }}</span>
                                                        <span class="text-dark fs-13 font-monospace">{{ $d['email'] }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <span class="text-muted fs-12 d-block">{{ __('Courier status') }}</span>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-12 px-2 py-1">{{ __(ucfirst($d['status'])) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-muted fs-12 d-block">{{ __('Accepted at') }}</span>
                                                    <strong class="text-dark fs-13 font-monospace">{{ $d['accepted_at'] ?? '—' }}</strong>
                                                </div>
                                                <div>
                                                    <span class="text-muted fs-12 d-block">{{ __('Failed attempts') }}</span>
                                                    <strong class="text-dark fs-13 font-monospace">{{ $d['failed_attempts'] }}</strong>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-muted fs-13 d-flex align-items-center gap-2">
                                                <i class="ri-time-line"></i>{{ __('No courier assigned yet.') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Delivery card --}}
                                @php $d = $order['details']['delivery']; @endphp
                                <div class="subcard d-none" data-subcard="delivery">
                                    <div class="bg-white p-3 rounded-3 border border-secondary-subtle shadow-sm">
                                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                            <i class="ri-home-smile-line text-primary fs-5"></i>
                                            <strong class="text-dark fs-14">{{ __('Delivered to customer') }}</strong>
                                        </div>
                                        <div class="d-flex align-items-start gap-4 flex-wrap">
                                            <div>
                                                <span class="text-muted fs-12 d-block">{{ __('Delivered at') }}</span>
                                                <strong class="text-dark fs-13 font-monospace">{{ $d['delivered_at'] ?? '—' }}</strong>
                                            </div>
                                            @if(!$d['done'])
                                                <div class="text-muted fs-13 d-flex align-items-center gap-2">
                                                    <i class="ri-time-line"></i>{{ $isPickup ? __('Customer will pick up the order directly at the gallery.') : __('Not delivered yet.') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-5 text-center text-muted">
                                <i class="ri-inbox-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                                <span class="fs-14 fw-semibold">{{ __('No orders found matching the criteria.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .stage-toggle-btn { cursor: pointer; }
    .stage-toggle-btn .badge { transition: box-shadow .15s ease; }
    .stage-toggle-btn.stage-active .badge { box-shadow: 0 0 0 2px var(--bs-primary-bg-subtle); }
    .phone-toggle-btn { cursor: pointer; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Subrow toggle (phone button shows contact card, stage badges show stage detail cards)
    document.querySelectorAll('.phone-toggle-btn, .stage-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const subrow = document.querySelector(this.getAttribute('data-target'));
            if (!subrow) return;
            const stage = this.getAttribute('data-stage') || 'contact';
            const activeCard = subrow.querySelector('.subcard[data-subcard="' + stage + '"]');
            const activeIsVisible = activeCard && !activeCard.classList.contains('d-none') && !subrow.classList.contains('d-none');

            // hide row + all cards first
            subrow.classList.add('d-none');
            subrow.querySelectorAll('.subcard').forEach(function (card) {
                card.classList.add('d-none');
            });

            // reset active styling on all triggers of this row
            document.querySelectorAll('[data-target="#' + subrow.id + '"]').forEach(function (b) {
                b.classList.remove('stage-active');
                if (b.classList.contains('phone-toggle-btn')) {
                    b.classList.add('btn-light', 'text-primary');
                    b.classList.remove('btn-primary', 'text-white');
                }
            });

            if (activeIsVisible) return; // second click on same trigger collapses

            // show requested card
            subrow.classList.remove('d-none');
            if (activeCard) activeCard.classList.remove('d-none');

            // highlight active trigger
            this.classList.add('stage-active');
            if (this.classList.contains('phone-toggle-btn')) {
                this.classList.remove('btn-light', 'text-primary');
                this.classList.add('btn-primary', 'text-white');
            }
        });
    });

    // 2. Urgent-first client sort (orange/0 first)
    let currentKey = '';
    let currentDir = 'asc';

    document.querySelectorAll('.sort-header').forEach(function (th) {
        th.addEventListener('click', function () {
            const key = this.getAttribute('data-sort');
            if (!key) return;
            currentDir = (currentKey === key && currentDir === 'asc') ? 'desc' : 'asc';
            currentKey = key;

            const tbody = document.querySelector('#orderBoardTable tbody');
            const rows = Array.from(tbody.querySelectorAll('tr.order-main-row'));

            rows.sort(function (a, b) {
                let vA = a.getAttribute('data-' + key) ?? '';
                let vB = b.getAttribute('data-' + key) ?? '';
                return !isNaN(vA) && !isNaN(vB) 
                    ? (currentDir === 'asc' ? vA - vB : vB - vA)
                    : (currentDir === 'asc' ? vA.localeCompare(vB) : vB.localeCompare(vA));
            });

            rows.forEach(function (r, idx) {
                const num = r.querySelector('td:first-child');
                if (num) num.textContent = idx + 1;
                tbody.appendChild(r);
                const sub = document.getElementById(r.id.replace('order-row-', 'subrow-'));
                if (sub) tbody.appendChild(sub);
            });
        });
    });
});
</script>
@endsection
