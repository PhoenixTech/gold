@extends('layouts.app')

@section('content')
    <section id="AvisaCustomer" class="shop-dashboard container-fluid px-0">
        @if((($lowStockCount ?? 0) > 0) || (($belowBuyPriceCount ?? 0) > 0))
            <div class="row g-3 mb-4">
                @if(($lowStockCount ?? 0) > 0)
                    <div class="col-12 col-md-6">
                        <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-0 h-100 rounded-3" role="alert">
                            <div class="d-flex align-items-center gap-2.5">
                                <i class="ri-alarm-warning-fill text-warning fs-3"></i>
                                <div>
                                    <strong class="d-block text-dark">{{ __('Low stock notice') }}</strong>
                                    <span class="text-muted fs-13">
                                        {{ __(':count products have fallen below minimum stock level and need attention.', ['count' => number_format($lowStockCount)]) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="btn btn-sm btn-warning text-dark fw-bold px-3">
                                <i class="ri-eye-line me-1"></i>{{ __('View low stock products') }}
                            </a>
                        </div>
                    </div>
                @endif

                @if(($belowBuyPriceCount ?? 0) > 0)
                    <div class="col-12 col-md-6">
                        <div class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-0 h-100 rounded-3" role="alert">
                            <div class="d-flex align-items-center gap-2.5">
                                <i class="ri-error-warning-fill text-danger fs-3"></i>
                                <div>
                                    <strong class="d-block text-dark">{{ __('Price below purchase price notice') }}</strong>
                                    <span class="text-muted fs-13">
                                        {{ __(':count products have calculated price below purchase price and sales are paused.', ['count' => number_format($belowBuyPriceCount)]) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('admin.product.index', ['filter' => ['below_buy_price' => '1']]) }}" class="btn btn-sm btn-danger text-white fw-bold px-3">
                                <i class="ri-eye-line me-1"></i>{{ __('View products') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="dash-rates mb-4">
            @foreach($rates as $rate)
                @if($canEditPrices)
                    <a class="dash-rate dash-rate--{{ $rate['key'] }}" href="{{ route('admin.setting.index') }}">
                @else
                    <div class="dash-rate dash-rate--{{ $rate['key'] }}">
                @endif
                    <span class="dash-rate__metal">
                        <i class="{{ $rate['icon'] }}"></i>
                        {{ $rate['label'] }}
                    </span>
                    <strong class="dash-rate__value">
                        {{ number_format($rate['value']) }}
                        <small>{{ config('app.currency.symbol') }}</small>
                    </strong>
                    <span class="dash-rate__updated">
                        @if($rate['updated_at'])
                            {{ __('Updated: :time', ['time' => $rate['updated_at']]) }}
                        @else
                            {{ __('Not updated yet') }}
                        @endif
                    </span>
                @if($canEditPrices)
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a class="avisa-summary-stat-card card-receipt" href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_RECEIPT]]) }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-upload-2-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{ __('WAITING_RECEIPT') }}</span>
                        <h5 class="stat-value">{{ number_format($waitingReceipt) }}</h5>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="avisa-summary-stat-card card-confirm" href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_CONFIRMATION]]) }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{ __('WAITING_CONFIRMATION') }}</span>
                        <h5 class="stat-value">{{ number_format($waitingConfirmation) }}</h5>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="avisa-summary-stat-card card-invoice" href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::PAID]]) }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-shopping-bag-4-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{ __('Need process orders') }}</span>
                        <h5 class="stat-value">{{ number_format($needProcess) }}</h5>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6 col-md-6">
                <a class="avisa-summary-stat-card card-stock" href="{{ route('admin.product.index') }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-archive-line"></i>
                    </div>
                    <div class="stat-details">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="stat-label mb-0">{{ __('In-stock inventory') }}</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ number_format($stockStats['total_count']) }} {{ __('items') }} · {{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight']) }} {{ __('g') }}
                            </span>
                        </div>
                        <h5 class="stat-value">
                            {{ number_format($stockStats['total_count']) }}
                            <small class="text-muted">{{ __('items') }}</small>
                            <span class="mx-1 text-muted fw-normal">·</span>
                            {{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight']) }}
                            <small class="text-muted">{{ __('g') }}</small>
                        </h5>
                        <small class="stat-sub">
                            <span class="me-2"><i class="ri-coins-line text-warning me-1"></i>{{ __('Gold') }}: {{ number_format($stockStats['gold_count']) }} {{ __('items') }} ({{ \App\Services\AdminDashboardStats::formatWeight($stockStats['gold_weight']) }} {{ __('g') }})</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-1"></i>{{ __('Silver') }}: {{ number_format($stockStats['silver_count']) }} {{ __('items') }} ({{ \App\Services\AdminDashboardStats::formatWeight($stockStats['silver_weight']) }} {{ __('g') }})</span>
                        </small>
                        @if(($lowStockCount ?? 0) > 0)
                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between text-danger fs-12">
                                <span><i class="ri-alarm-warning-line me-1"></i>{{ __('Low stock alert') }}: <b>{{ number_format($lowStockCount) }}</b> {{ __('products') }}</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Filter') }} &larr;</span>
                            </div>
                        @endif
                        @if(($belowBuyPriceCount ?? 0) > 0)
                            <div class="mt-1 pt-1 border-top d-flex align-items-center justify-content-between text-warning-emphasis fs-12">
                                <span><i class="ri-error-warning-line me-1"></i>{{ __('Below purchase price') }}: <b>{{ number_format($belowBuyPriceCount) }}</b> {{ __('products') }}</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('Filter') }} &larr;</span>
                            </div>
                        @endif
                    </div>
                </a>
            </div>
            <div class="col-lg-6 col-md-6">
                <a class="avisa-summary-stat-card card-sold" href="{{ route('admin.invoice.index') }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-shopping-cart-check-line"></i>
                    </div>
                    <div class="stat-details">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="stat-label mb-0">{{ __('Sold items') }}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ number_format($soldStats['total_count']) }} {{ __('items') }} · {{ \App\Services\AdminDashboardStats::formatWeight($soldStats['total_weight']) }} {{ __('g') }}
                            </span>
                        </div>
                        <h5 class="stat-value">
                            {{ number_format($soldStats['total_count']) }}
                            <small class="text-muted">{{ __('items') }}</small>
                            <span class="mx-1 text-muted fw-normal">·</span>
                            {{ \App\Services\AdminDashboardStats::formatWeight($soldStats['total_weight']) }}
                            <small class="text-muted">{{ __('g') }}</small>
                        </h5>
                        <small class="stat-sub">
                            <span class="me-2"><i class="ri-coins-line text-warning me-1"></i>{{ __('Gold') }}: {{ number_format($soldStats['gold_count']) }} {{ __('items') }} ({{ \App\Services\AdminDashboardStats::formatWeight($soldStats['gold_weight']) }} {{ __('g') }})</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-1"></i>{{ __('Silver') }}: {{ number_format($soldStats['silver_count']) }} {{ __('items') }} ({{ \App\Services\AdminDashboardStats::formatWeight($soldStats['silver_weight']) }} {{ __('g') }})</span>
                        </small>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <a class="avisa-summary-stat-card card-sales" href="{{ route('admin.invoice.index') }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-line-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{ __('This month sales') }}</span>
                        <h5 class="stat-value">{{ number_format($monthlySales) }}</h5>
                        <small class="stat-sub">{{ config('app.currency.symbol') }}</small>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a class="avisa-summary-stat-card card-bank" href="{{ route('admin.bank-account.index') }}">
                    <div class="stat-icon-wrapper">
                        <i class="ri-bank-card-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{ __('Active bank account') }}</span>
                        @if($bankAccount)
                            <h5 class="stat-value">{{ $bankAccount->bank_name }}</h5>
                            <small class="stat-sub">{{ $bankAccount->account_holder_name }}</small>
                        @else
                            <h5 class="stat-value">—</h5>
                            <small class="stat-sub">{{ __('No active bank account') }}</small>
                        @endif
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="avisa-summary-widget">
                    <div class="widget-header">
                        <h4><i class="ri-file-list-3-line me-2"></i>{{ __('Recent invoices') }}</h4>
                        <a class="widget-link" href="{{ route('admin.invoice.index') }}">
                            {{ __('Invoices') }}
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    </div>
                    <div class="widget-body p-0">
                        @if($recentInvoices->isEmpty())
                            <p class="text-muted px-3 py-4 mb-0">{{ __('No invoices yet') }}</p>
                        @else
                            <div class="table-responsive">
                                <table class="table avisa-summary-table mb-0">
                                    <thead>
                                    <tr>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('created_at') }}</th>
                                        <th>{{ __('Total price') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($recentInvoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.invoice.edit', $invoice) }}">
                                                    {{ $invoice->customer?->name ?? '—' }}
                                                </a>
                                            </td>
                                            <td>{{ \App\Models\Invoice::formatPersianDateTime($invoice->created_at) }}</td>
                                            <td>{{ number_format((int) $invoice->total_price) }} {{ config('app.currency.symbol') }}</td>
                                            <td><span class="{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="avisa-summary-widget">
                    <div class="widget-header">
                        <h4><i class="ri-store-2-line me-2"></i>{{ __('Quick access') }}</h4>
                    </div>
                    <div class="widget-body">
                        <ul class="dash-quick-links">
                            <li><a href="{{ route('admin.product.index') }}"><i class="ri-vip-diamond-fill"></i>{{ __('Products') }}</a></li>
                            <li><a href="{{ route('admin.category.index') }}"><i class="ri-box-3-fill"></i>{{ __('Categories') }}</a></li>
                            <li><a href="{{ route('admin.invoice.index') }}"><i class="ri-file-list-3-fill"></i>{{ __('Invoices') }}</a></li>
                            <li><a href="{{ route('admin.bank-account.index') }}"><i class="ri-bank-card-line"></i>{{ __('Bank accounts') }}</a></li>
                            <li><a href="{{ route('admin.transport.index') }}"><i class="ri-truck-fill"></i>{{ __('Transports') }}</a></li>
                            <li><a href="{{ route('admin.customer.index') }}"><i class="ri-team-fill"></i>{{ __('Customers') }}</a></li>
                        </ul>
                        <p class="dash-visitors mb-0">
                            <i class="ri-eye-line"></i>
                            {{ __('Monthly Visitors') }}:
                            <strong>{{ number_format($monthlyVisitors) }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
