@extends('layouts.app')

@section('content')
    <section id="shop-summary-page" class="container-fluid px-0">
        <header class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h4 mb-0 fw-bold text-dark">{{ __('Shop summary') }}</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border border-secondary-subtle px-3 py-2 fw-medium">
                    <i class="ri-calendar-line me-1" aria-hidden="true"></i>{{ $today }}
                </span>
                @if($canEditPrices)
                    <a href="{{ route('admin.setting.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-settings-4-line me-1" aria-hidden="true"></i>{{ __('Manage settings') }}
                    </a>
                @endif
            </div>
        </header>

        @if(($lowStockCount ?? 0) > 0)
            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3" role="alert">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="ri-alarm-warning-fill text-warning fs-3" aria-hidden="true"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('Low stock notice') }}</strong>
                        <span class="text-muted fs-13">
                            {{ __(':count products have fallen below minimum stock level and need attention.', ['count' => number_format($lowStockCount)]) }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="btn btn-sm btn-warning text-dark fw-bold px-3">
                    <i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('View low stock products') }}
                </a>
            </div>
        @endif

        @if(($belowBuyPriceCount ?? 0) > 0)
            <div class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3" role="alert">
                <div class="d-flex align-items-center gap-2.5">
                    <i class="ri-error-warning-fill text-danger fs-3" aria-hidden="true"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('Price below purchase price notice') }}</strong>
                        <span class="text-muted fs-13">
                            {{ __(':count products have calculated price below purchase price and sales are paused.', ['count' => number_format($belowBuyPriceCount)]) }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.product.index', ['filter' => ['below_buy_price' => '1']]) }}" class="btn btn-sm btn-danger text-white fw-bold px-3">
                    <i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('View products') }}
                </a>
            </div>
        @endif

        <section class="mb-4" aria-labelledby="sales-overview-title">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 id="sales-overview-title" class="h6 mb-0 fw-bold text-dark">{{ __('Overall sales') }}</h2>
                <a href="{{ route('admin.invoice.index') }}" class="btn btn-sm btn-link text-decoration-none p-0">
                    {{ __('View all invoices') }}
                </a>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="card border shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <span class="text-secondary small fw-medium d-block mb-1">{{ __('Total sales') }}</span>
                            <strong class="h4 mb-0 fw-bold text-dark d-block" data-summary-metric="total-sales">
                                {{ number_format($salesSummary['total_price']) }}
                                <small class="fs-6 text-muted">{{ config('app.currency.symbol') }}</small>
                            </strong>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="card border shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <span class="text-secondary small fw-medium d-block mb-1">{{ __('Sold pieces') }}</span>
                            <strong class="h4 mb-0 fw-bold text-dark d-block" data-summary-metric="sold-pieces">
                                {{ number_format($salesSummary['item_count']) }}
                            </strong>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="card border shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <span class="text-secondary small fw-medium d-block mb-1">{{ __('Successful invoices') }}</span>
                            <strong class="h4 mb-0 fw-bold text-dark d-block" data-summary-metric="successful-invoices">
                                {{ number_format($salesSummary['invoice_count']) }}
                            </strong>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <article class="card border shadow-sm rounded-3 h-100">
                        <div class="card-body p-3">
                            <span class="text-secondary small fw-medium d-block mb-1">{{ __('Sold weight') }}</span>
                            <strong class="h4 mb-0 fw-bold text-dark d-block" data-summary-metric="sold-weight">
                                {{ \App\Services\AdminDashboardStats::formatWeight($salesSummary['total_weight']) }}
                                <small class="fs-6 text-muted">{{ __('g') }}</small>
                            </strong>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <section class="card border shadow-sm rounded-3 h-100" aria-labelledby="settings-title">
                    <div class="card-header bg-transparent border-bottom p-3">
                        <h2 id="settings-title" class="h6 mb-0 fw-bold text-dark">{{ __('Shop settings') }}</h2>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @foreach($marketSettings as $setting)
                                <div class="{{ $setting['key'] === 'min' ? 'col-12' : 'col-12 col-sm-6' }}">
                                    <div class="p-2.5 rounded-2 border bg-light d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <i class="{{ $setting['icon'] }} text-secondary" aria-hidden="true"></i>
                                            <span class="small text-secondary text-truncate">{{ $setting['label'] }}</span>
                                        </div>
                                        <div class="text-end text-nowrap">
                                            <strong class="text-dark">{{ number_format((float) $setting['value']) }}</strong>
                                            <small class="text-muted">{{ $setting['suffix'] }}</small>
                                        </div>
                                    </div>
                                    @if($setting['key'] === 'min')
                                        <span class="small text-secondary d-block mt-1 px-1">
                                            {{ __('Selling price must be at least :percent% of purchase price.', ['percent' => number_format((float) $setting['value'])]) }}
                                        </span>
                                    @elseif($setting['updated_at'])
                                        <span class="small text-muted d-block mt-1 px-1">
                                            {{ __('Updated: :time', ['time' => $setting['updated_at']]) }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="card border shadow-sm rounded-3 h-100" aria-labelledby="inventory-title">
                    <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
                        <h2 id="inventory-title" class="h6 mb-0 fw-bold text-dark">{{ __('Inventory overview') }}</h2>
                        <a href="{{ route('admin.product.index') }}" class="btn btn-sm btn-link text-decoration-none p-0">{{ __('Products') }}</a>
                    </div>
                    <div class="card-body p-3">
                        <div class="p-3 mb-3 rounded-2 border bg-light d-flex align-items-baseline justify-content-between flex-wrap gap-2">
                            <div>
                                <strong class="h4 mb-0 fw-bold text-dark">{{ number_format($stockStats['total_count']) }}</strong>
                                <span class="text-secondary ms-1">{{ __('available pieces') }}</span>
                            </div>
                            <span class="fw-bold text-dark">
                                {{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight']) }} {{ __('g') }}
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2.5 rounded-2 border bg-light">
                                    <span class="small text-secondary d-flex align-items-center gap-1 mb-1">
                                        <i class="ri-coins-line text-secondary" aria-hidden="true"></i>{{ __('Gold') }}
                                    </span>
                                    <div class="fw-bold text-dark">{{ number_format($stockStats['gold_count']) }} <small class="text-muted fw-normal">{{ __('pieces') }}</small></div>
                                    <div class="small text-secondary">{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['gold_weight']) }} {{ __('g') }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-2 border bg-light">
                                    <span class="small text-secondary d-flex align-items-center gap-1 mb-1">
                                        <i class="ri-vip-diamond-line text-secondary" aria-hidden="true"></i>{{ __('Silver') }}
                                    </span>
                                    <div class="fw-bold text-dark">{{ number_format($stockStats['silver_count']) }} <small class="text-muted fw-normal">{{ __('pieces') }}</small></div>
                                    <div class="small text-secondary">{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['silver_weight']) }} {{ __('g') }}</div>
                                </div>
                            </div>
                        </div>
                        @if(($lowStockCount ?? 0) > 0)
                            <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                                <span class="text-danger fw-semibold fs-13 d-flex align-items-center gap-1">
                                    <i class="ri-alarm-warning-line" aria-hidden="true"></i>
                                    {{ __('Low stock alert') }}: {{ number_format($lowStockCount) }} {{ __('products') }}
                                </span>
                                <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none">
                                    {{ __('Filter') }}
                                </a>
                            </div>
                        @endif
                        @if(($belowBuyPriceCount ?? 0) > 0)
                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                <span class="text-warning-emphasis fw-semibold fs-13 d-flex align-items-center gap-1">
                                    <i class="ri-error-warning-line" aria-hidden="true"></i>
                                    {{ __('Below purchase price') }}: {{ number_format($belowBuyPriceCount) }} {{ __('products') }}
                                </span>
                                <a href="{{ route('admin.product.index', ['filter' => ['below_buy_price' => '1']]) }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-decoration-none">
                                    {{ __('Filter') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <section class="mb-4" aria-labelledby="quick-access-title">
            <div class="mb-3">
                <h2 id="quick-access-title" class="h6 mb-0 fw-bold text-dark">{{ __('Quick access') }}</h2>
            </div>
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.product.index') }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Products') }}</span>
                                <i class="ri-vip-diamond-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($products) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.customer.index') }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Customers') }}</span>
                                <i class="ri-team-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($customers) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::PAID]]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Need process orders') }}</span>
                                <i class="ri-shopping-bag-4-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($needProcess) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_RECEIPT]]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Waiting receipt') }}</span>
                                <i class="ri-upload-2-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($waitingReceipt) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_CONFIRMATION]]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Waiting confirmation') }}</span>
                                <i class="ri-checkbox-circle-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($waitingConfirmation) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.ticket.index', ['filter' => ['status' => 'PENDING']]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Pending tickets') }}</span>
                                <i class="ri-customer-service-2-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark">{{ number_format($pendingTickets) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.bank-account.index') }}" class="card border shadow-sm rounded-3 text-decoration-none h-100">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-secondary fw-medium">{{ __('Active bank account') }}</span>
                                <i class="ri-bank-card-line text-secondary" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold text-dark text-truncate">{{ $bankAccount?->bank_name ?? '-' }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100 @if(($lowStockCount ?? 0) > 0) border-danger-subtle @endif">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small @if(($lowStockCount ?? 0) > 0) text-danger @else text-secondary @endif fw-medium">{{ __('Low stock') }}</span>
                                <i class="ri-alarm-warning-line @if(($lowStockCount ?? 0) > 0) text-danger @else text-secondary @endif" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold @if(($lowStockCount ?? 0) > 0) text-danger @else text-dark @endif">{{ number_format($lowStockCount ?? 0) }}</strong>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.product.index', ['filter' => ['below_buy_price' => '1']]) }}" class="card border shadow-sm rounded-3 text-decoration-none h-100 @if(($belowBuyPriceCount ?? 0) > 0) border-danger-subtle @endif">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small @if(($belowBuyPriceCount ?? 0) > 0) text-danger @else text-secondary @endif fw-medium">{{ __('Below purchase price') }}</span>
                                <i class="ri-error-warning-line @if(($belowBuyPriceCount ?? 0) > 0) text-danger @else text-secondary @endif" aria-hidden="true"></i>
                            </div>
                            <strong class="h5 mb-0 fw-bold @if(($belowBuyPriceCount ?? 0) > 0) text-danger @else text-dark @endif">{{ number_format($belowBuyPriceCount ?? 0) }}</strong>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section class="card border shadow-sm rounded-3" aria-labelledby="recent-sales-title">
            <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
                <h2 id="recent-sales-title" class="h6 mb-0 fw-bold text-dark">{{ __('Latest successful sales') }}</h2>
                <a href="{{ route('admin.invoice.index') }}" class="btn btn-sm btn-link text-decoration-none p-0">{{ __('View all invoices') }}</a>
            </div>
            <div class="card-body p-0">
                @if($recentSales->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="ri-inbox-line fs-2 d-block mb-1" aria-hidden="true"></i>
                        <span>{{ __('No successful sales yet') }}</span>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">{{ __('Invoice') }}</th>
                                <th scope="col">{{ __('Customer') }}</th>
                                <th scope="col">{{ __('Date') }}</th>
                                <th scope="col">{{ __('Total price') }}</th>
                                <th scope="col" class="pe-3">{{ __('Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentSales as $invoice)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route($invoice->status === \App\Models\Invoice::COMPLETED ? 'admin.invoice.show' : 'admin.invoice.edit', $invoice) }}" class="fw-bold text-decoration-none">
                                            #{{ $invoice->hash }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                                    <td>{{ \App\Models\Invoice::formatPersianDateTime($invoice->created_at) }}</td>
                                    <td class="fw-bold">{{ number_format((int) $invoice->total_price) }} {{ config('app.currency.symbol') }}</td>
                                    <td class="pe-3">
                                        <span class="{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </section>
@endsection
