@extends('layouts.app')

@section('title')
    {{ __('Order board') }} -
@endsection

@section('content')
<div class="container-fluid px-0 mb-5">
    {{-- Header --}}
    <header class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 text-primary fs-14 fw-semibold mb-1">
                <i class="ri-dashboard-2-line"></i>
                <span>{{ __('Operational order board') }}</span>
            </div>
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
                    <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Filter scope') }}">
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'active'])) }}" 
                           class="btn {{ $scope === 'active' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ri-fire-line me-1"></i>{{ __('Active orders') }}
                            <span class="badge {{ $scope === 'active' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ number_format($activeCount) }}</span>
                        </a>
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'completed'])) }}" 
                           class="btn {{ $scope === 'completed' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            <i class="ri-check-double-line me-1"></i>{{ __('Completed orders') }}
                            <span class="badge {{ $scope === 'completed' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ number_format($completedCount) }}</span>
                        </a>
                        <a href="{{ route('admin.order-board.index', array_merge(request()->except('scope'), ['scope' => 'all'])) }}" 
                           class="btn {{ $scope === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ __('All orders') }}
                            <span class="badge {{ $scope === 'all' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ number_format($allCount) }}</span>
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
                                        data-target="#subrow-{{ $order['id'] }}" title="{{ __('View phone and address') }}">
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

                            {{-- 6-10. Workflow Stages Loop --}}
                            @foreach(['payment', 'confirm', 'settle', 'courier', 'delivery'] as $key)
                                @php $stage = $order['stages'][$key]; @endphp
                                <td>
                                    <span class="badge {{ $stage['done'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} p-2 fs-12 w-100 d-inline-flex align-items-center justify-content-center gap-1" title="{{ $stage['title'] }}">
                                        <i class="{{ $stage['done'] ? ($key === 'delivery' ? 'ri-check-double-fill' : ($key === 'courier' ? 'ri-truck-fill' : 'ri-checkbox-circle-fill')) : 'ri-time-fill' }}"></i>
                                        <span>{{ $stage['text'] }}</span>
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Sub-row: Phone, Address & Contact Details --}}
                        <tr class="order-subrow d-none bg-white border-top border-bottom" id="subrow-{{ $order['id'] }}">
                            <td colspan="10" class="p-3 text-start bg-light bg-opacity-50">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Phone subrow toggle
    document.querySelectorAll('.phone-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const subrow = document.querySelector(this.getAttribute('data-target'));
            if (!subrow) return;
            const isHidden = subrow.classList.toggle('d-none');
            this.classList.toggle('btn-primary', !isHidden);
            this.classList.toggle('text-white', !isHidden);
            this.classList.toggle('btn-light', isHidden);
            this.classList.toggle('text-primary', isHidden);
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
