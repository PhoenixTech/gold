@extends('layouts.app')

@section('title')
    {{ __('Payments list') }} -
@endsection

@section('content')
<div class="mb-5 pb-5">
    @include('components.err')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1 fw-bold d-flex align-items-center gap-2 text-dark fs-18">
                <i class="ri-money-dollar-circle-line text-primary"></i>
                {{ __('Payments list') }}
            </h4>
            <p class="text-muted fs-13 mb-0">
                {{ __('All successful payments from in-person shop sales and online website orders.') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                <i class="ri-check-double-line text-success me-1"></i>
                {{ __('Total payments') }}:
                <b class="text-primary font-fanum">{{ number_format($kpis['total_count']) }}</b>
            </span>
        </div>
    </div>

    @php
        $kpiCards = [
            [
                'title' => __('Total payments'),
                'amount' => $kpis['total_amount'],
                'count' => $kpis['total_count'],
                'count_label' => __('Payment'),
                'icon' => 'ri-hand-coin-line',
                'color' => 'primary',
            ],
            [
                'title' => __('Total physical payments'),
                'amount' => $kpis['physical_amount'],
                'count' => $kpis['physical_count'],
                'count_label' => __('In-person sale'),
                'icon' => 'ri-store-2-line',
                'color' => 'success',
            ],
            [
                'title' => __('Total online payments'),
                'amount' => $kpis['online_amount'],
                'count' => $kpis['online_count'],
                'count_label' => __('Website sale'),
                'icon' => 'ri-global-line',
                'color' => 'info',
            ],
            [
                'title' => __('Total paid to suppliers'),
                'amount' => $kpis['supplier_amount'],
                'count' => $kpis['supplier_count'],
                'count_label' => __('Payment'),
                'icon' => 'ri-contacts-line',
                'color' => 'warning',
            ],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach($kpiCards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-12 d-block mb-1">{{ $card['title'] }}</span>
                            <h5 class="fw-bold text-dark font-fanum mb-0">{{ number_format($card['amount']) }} <small class="fs-12 text-muted">{{ __('Toman') }}</small></h5>
                            <small class="text-muted fs-11 font-fanum">{{ number_format($card['count']) }} {{ $card['count_label'] }}</small>
                        </div>
                        <div class="p-2.5 rounded-3 bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} fs-4">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3 px-1">
        @php
            $baseParams = array_filter([
                'q' => request('q'),
                'sort' => request('sort'),
                'sortType' => request('sortType'),
            ]);
            $quickUrl = fn (array $filterParam = []) => route('admin.payment.index', array_merge($baseParams, $filterParam));

            $activePill = match (true) {
                $currentType === 'physical' && empty($currentSupplier) && ($currentTimeFrame === 'all' || empty($currentTimeFrame)) => 'physical',
                $currentType === 'online' && empty($currentSupplier) && ($currentTimeFrame === 'all' || empty($currentTimeFrame)) => 'online',
                $currentSupplier === 'any' && empty($currentType) && ($currentTimeFrame === 'all' || empty($currentTimeFrame)) => 'supplier',
                $currentTimeFrame === 'today' && empty($currentType) && empty($currentSupplier) => 'today',
                $currentTimeFrame === 'yesterday' && empty($currentType) && empty($currentSupplier) => 'yesterday',
                empty($currentType) && empty($currentSupplier) && ($currentTimeFrame === 'all' || empty($currentTimeFrame)) => 'all',
                default => null,
            };
        @endphp
        <a href="{{ $quickUrl() }}"
           class="btn btn-sm {{ $activePill === 'all' ? 'btn-light fw-bold' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ __('All') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['all']) }}</span>
        </a>
        <a href="{{ $activePill === 'physical' ? $quickUrl() : $quickUrl(['type' => 'physical']) }}"
           class="btn btn-sm {{ $activePill === 'physical' ? 'btn-light fw-bold text-success' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="ri-store-2-line me-1"></i>{{ __('In-person sale') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['physical']) }}</span>
        </a>
        <a href="{{ $activePill === 'online' ? $quickUrl() : $quickUrl(['type' => 'online']) }}"
           class="btn btn-sm {{ $activePill === 'online' ? 'btn-light fw-bold text-info' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="ri-global-line me-1"></i>{{ __('Website sale') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['online']) }}</span>
        </a>
        <a href="{{ $activePill === 'supplier' ? $quickUrl() : $quickUrl(['supplier' => 'any']) }}"
           class="btn btn-sm {{ $activePill === 'supplier' ? 'btn-light fw-bold text-warning-emphasis' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="ri-contacts-line me-1"></i>{{ __('Paid to supplier') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['supplier']) }}</span>
        </a>
        <a href="{{ $activePill === 'today' ? $quickUrl() : $quickUrl(['time_frame' => 'today']) }}"
           class="btn btn-sm {{ $activePill === 'today' ? 'btn-light fw-bold text-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="ri-calendar-event-line me-1"></i>{{ __('Today') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['today']) }}</span>
        </a>
        <a href="{{ $activePill === 'yesterday' ? $quickUrl() : $quickUrl(['time_frame' => 'yesterday']) }}"
           class="btn btn-sm {{ $activePill === 'yesterday' ? 'btn-light fw-bold text-secondary' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="ri-history-line me-1"></i>{{ __('Yesterday') }}
            <span class="ms-1 opacity-75 font-fanum">{{ number_format($quickCounts['yesterday']) }}</span>
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.payment.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label text-muted fs-12 mb-1">{{ __('Payment channel') }}</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">{{ __('All types') }}</option>
                        <option value="physical" @selected($currentType === 'physical')>{{ __('In-person sale') }}</option>
                        <option value="online" @selected($currentType === 'online')>{{ __('Website sale') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-3">
                    <label class="form-label text-muted fs-12 mb-1">{{ __('Suppliers') }}</label>
                    <select name="supplier" class="form-select form-select-sm">
                        <option value="">{{ __('All suppliers') }}</option>
                        <option value="none" @selected($currentSupplier === 'none')>{{ __('Direct store account') }}</option>
                        <option value="any" @selected($currentSupplier === 'any')>{{ __('Paid to supplier') }} ({{ __('All') }})</option>
                        <optgroup label="{{ __('Suppliers') }}">
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup['id'] }}" @selected($currentSupplier === (string) $sup['id'])>
                                    {{ $sup['label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label text-muted fs-12 mb-1">{{ __('Time frame') }}</label>
                    <select name="time_frame" class="form-select form-select-sm" id="time_frame_select">
                        <option value="all" @selected($currentTimeFrame === 'all')>{{ __('All time') }}</option>
                        <option value="today" @selected($currentTimeFrame === 'today')>{{ __('Today') }}</option>
                        <option value="yesterday" @selected($currentTimeFrame === 'yesterday')>{{ __('Yesterday') }}</option>
                        <option value="last_7_days" @selected($currentTimeFrame === 'last_7_days')>{{ __('Last 7 days') }}</option>
                        <option value="last_30_days" @selected($currentTimeFrame === 'last_30_days')>{{ __('Last 30 days') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-3">
                    <label class="form-label text-muted fs-12 mb-1">{{ __('Search') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" class="form-control" placeholder="{{ __('Search payments...') }}" value="{{ $search }}">
                        <button type="submit" class="btn btn-primary" title="{{ __('Search') }}">
                            <i class="ri-search-line"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-lg-2 d-flex align-items-end gap-1 mt-auto">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i>{{ __('Filter') }}
                    </button>
                    @if(!empty($currentType) || !empty($currentSupplier) || ($currentTimeFrame !== 'all' && !empty($currentTimeFrame)) || !empty($search))
                        <a href="{{ route('admin.payment.index') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Clear filters') }}">
                            <i class="ri-refresh-line"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 70px;">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'sortType' => request('sort') === 'id' && request('sortType') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                #
                                @if(request('sort', 'id') === 'id')
                                    <i class="ri-arrow-{{ request('sortType') === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </a>
                        </th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Payment method') }}</th>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Supplier') }} / {{ __('Store account') }}</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'sortType' => request('sort') === 'amount' && request('sortType') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                {{ __('Amount') }}
                                @if(request('sort') === 'amount')
                                    <i class="ri-arrow-{{ request('sortType') === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </a>
                        </th>
                        <th>{{ __('Reference ID') }}</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'sortType' => request('sort') === 'created_at' && request('sortType') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                {{ __('Date') }}
                                @if(request('sort') === 'created_at')
                                    <i class="ri-arrow-{{ request('sortType') === 'asc' ? 'up' : 'down' }}-s-line"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-center" style="width: 80px;">{{ __('Receipt slip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $isPhysical = $item->isPhysical();
                            $invoice = $item->invoice;
                            $customer = $invoice?->customer;
                            $receipt = $item->receipts->first();
                            $supplierModel = $item->supplier;
                            $bankName = $item->meta['bank_account_name'] ?? null;
                            $cardNumber = $item->meta['card_number'] ?? null;
                        @endphp
                        <tr>
                            <td class="ps-3 font-monospace text-muted fs-12">
                                <span class="text-dark fw-bold">{{ $item->id }}</span>
                                @if($item->order_id)
                                    <small class="d-block text-muted fs-11 text-truncate" style="max-width: 150px;" dir="ltr" title="{{ $item->order_id }}">{{ $item->order_id }}</small>
                                @endif
                            </td>

                            <td>
                                @if($isPhysical)
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle fs-12">
                                        <i class="ri-store-2-line me-1"></i>{{ __('In-person sale') }}
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-12">
                                        <i class="ri-global-line me-1"></i>{{ __('Website sale') }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border fs-12">
                                    {{ $item->methodLabel() }}
                                </span>
                            </td>

                            <td>
                                @if($invoice)
                                    <a href="{{ route('admin.invoice.show', $invoice) }}" class="fw-bold font-monospace text-primary text-decoration-none" dir="ltr">
                                        #{{ $invoice->hash ?: $invoice->id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($customer)
                                    <a href="{{ route('admin.customer.edit', $customer->id) }}" class="text-dark fw-semibold text-decoration-none d-block">
                                        {{ $customer->name }}
                                    </a>
                                    @if($customer->mobile)
                                        <a href="tel:{{ $customer->mobile }}" dir="ltr" class="font-monospace text-muted fs-12 text-decoration-none">
                                            {{ $customer->mobile }}
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($supplierModel)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12 d-inline-flex align-items-center gap-1">
                                        <i class="ri-contacts-line"></i>
                                        {{ $supplierModel->name }}
                                    </span>
                                    @if($supplierModel->company_name)
                                        <small class="text-muted d-block fs-11">{{ $supplierModel->company_name }}</small>
                                    @endif
                                @elseif($bankName || $cardNumber)
                                    <div class="d-inline-flex align-items-center gap-1 text-dark fs-12">
                                        <i class="ri-bank-card-line text-muted"></i>
                                        <span>{{ $bankName ?: __('Store account') }}</span>
                                    </div>
                                    @if($cardNumber)
                                        <small class="font-monospace text-muted d-block fs-11" dir="ltr">{{ $cardNumber }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-light text-muted border fs-11">
                                        {{ __('Direct store account') }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="fw-bold text-dark font-fanum fs-14">
                                    {{ number_format($item->amount) }}
                                </span>
                                <small class="text-muted fs-11">{{ __('Toman') }}</small>
                            </td>

                            <td>
                                @if($item->reference_id)
                                    <span class="font-monospace text-dark fs-12" dir="ltr">{{ $item->reference_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                <span class="text-dark font-fanum fs-12 d-block" dir="ltr">
                                    {{ $item->formattedPaymentDate() }}
                                </span>
                            </td>

                            <td class="text-center">
                                @if($receipt && $receipt->path)
                                    <a href="{{ asset('storage/'.$receipt->path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 fs-11" title="{{ __('View receipt') }}">
                                        <i class="ri-attachment-line"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="ri-inbox-line fs-2 d-block mb-2 opacity-50"></i>
                                {{ __('No payments found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted fs-12">
                {{ __('Showing :rows of :total payments', [
                    'rows' => number_format($items->count()),
                    'total' => number_format($items->total()),
                ]) }}
            </span>

            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-1">
                    <label class="text-muted fs-12" for="per_page_select">{{ __('Rows per page') }}</label>
                    <select id="per_page_select" class="form-select form-select-sm w-auto" onchange="const u = new URL(location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); location.href = u.toString();">
                        @foreach($perPageOptions as $opt)
                            <option value="{{ $opt }}" @selected($items->perPage() === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
