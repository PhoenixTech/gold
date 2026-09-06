@extends('layouts.app')

@section('content')
    <section id="shop-summary-page" class="shop-summary container-fluid px-0">
        <header class="summary-hero mb-4">
            <div>
                <span class="summary-eyebrow"><i class="ri-bar-chart-grouped-line"></i>{{ __('Gold shop control room') }}</span>
                <h1 class="summary-title">{{ __('Shop summary') }}</h1>
                <p class="summary-description mb-0">
                    {{ __('A clear view of sales, inventory, and the rules that shape today’s prices.') }}
                </p>
            </div>
            <div class="summary-hero-actions">
                <span class="summary-date"><i class="ri-calendar-line"></i>{{ $today }}</span>
                @if($canEditPrices)
                    <a href="{{ route('admin.setting.index') }}" class="summary-action">
                        <i class="ri-settings-4-line"></i>{{ __('Manage settings') }}
                    </a>
                @endif
            </div>
        </header>

        @if(($lowStockCount ?? 0) > 0)
            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3" role="alert">
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
        @endif

        <section class="summary-sales mb-4" aria-labelledby="sales-overview-title">
            <div class="summary-section-heading">
                <div>
                    <span class="summary-section-kicker">{{ __('Business at a glance') }}</span>
                    <h2 id="sales-overview-title">{{ __('Overall sales') }}</h2>
                </div>
                <a href="{{ route('admin.invoice.index') }}" class="summary-inline-link">
                    {{ __('View all invoices') }} <i class="ri-arrow-left-line"></i>
                </a>
            </div>

            <div class="summary-sales-grid">
                <article class="summary-sales-total">
                    <span class="summary-card-label">{{ __('Total sales') }}</span>
                    <strong data-summary-metric="total-sales">
                        {{ number_format($salesSummary['total_price']) }}
                        <small>{{ config('app.currency.symbol') }}</small>
                    </strong>
                    <span class="summary-card-note"><i class="ri-checkbox-circle-line"></i>{{ __('Paid, processing, and completed invoices') }}</span>
                </article>
                <article class="summary-metric-card summary-metric-card--pieces">
                    <i class="ri-shopping-bag-3-line"></i>
                    <span class="summary-card-label">{{ __('Sold pieces') }}</span>
                    <strong data-summary-metric="sold-pieces">{{ number_format($salesSummary['item_count']) }}</strong>
                    <span class="summary-card-note">{{ __('All successful sales') }}</span>
                </article>
                <article class="summary-metric-card summary-metric-card--invoices">
                    <i class="ri-file-list-3-line"></i>
                    <span class="summary-card-label">{{ __('Successful invoices') }}</span>
                    <strong data-summary-metric="successful-invoices">{{ number_format($salesSummary['invoice_count']) }}</strong>
                    <span class="summary-card-note">{{ __('Paid or fulfilled') }}</span>
                </article>
                <article class="summary-metric-card summary-metric-card--weight">
                    <i class="ri-scales-3-line"></i>
                    <span class="summary-card-label">{{ __('Sold weight') }}</span>
                    <strong data-summary-metric="sold-weight">{{ \App\Services\AdminDashboardStats::formatWeight($salesSummary['total_weight']) }} <small>{{ __('g') }}</small></strong>
                    <span class="summary-card-note">{{ __('Based on sold product pieces') }}</span>
                </article>
            </div>
        </section>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <section class="summary-panel h-100" aria-labelledby="settings-title">
                    <div class="summary-panel-header">
                        <div>
                            <span class="summary-section-kicker">{{ __('Pricing and checkout') }}</span>
                            <h2 id="settings-title">{{ __('Shop settings') }}</h2>
                        </div>
                        <i class="ri-sliders-3-line summary-panel-mark"></i>
                    </div>
                    <div class="summary-settings-grid">
                        @foreach($marketSettings as $setting)
                            <div class="summary-setting summary-setting--{{ $setting['key'] }}">
                                <span class="summary-setting-icon"><i class="{{ $setting['icon'] }}"></i></span>
                                <div class="summary-setting-copy">
                                    <span>{{ $setting['label'] }}</span>
                                    <strong>
                                        {{ number_format((float) $setting['value']) }}
                                        <small>{{ $setting['suffix'] }}</small>
                                    </strong>
                                    @if($setting['key'] === 'min')
                                        <em>{{ __('Selling price must be at least :percent% of purchase price.', ['percent' => number_format((float) $setting['value'])]) }}</em>
                                    @elseif($setting['updated_at'])
                                        <em>{{ __('Updated: :time', ['time' => $setting['updated_at']]) }}</em>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="summary-panel h-100" aria-labelledby="inventory-title">
                    <div class="summary-panel-header">
                        <div>
                            <span class="summary-section-kicker">{{ __('Stock position') }}</span>
                            <h2 id="inventory-title">{{ __('Inventory overview') }}</h2>
                        </div>
                        <a href="{{ route('admin.product.index') }}" class="summary-panel-link">{{ __('Products') }} <i class="ri-arrow-left-line"></i></a>
                    </div>
                    <div class="summary-inventory-total">
                        <strong>{{ number_format($stockStats['total_count']) }}</strong>
                        <span>{{ __('available pieces') }}</span>
                        <b>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight']) }} {{ __('g') }}</b>
                    </div>
                    <div class="summary-inventory-breakdown">
                        <div>
                            <span><i class="ri-coins-line"></i>{{ __('Gold') }}</span>
                            <strong>{{ number_format($stockStats['gold_count']) }} <small>{{ __('pieces') }}</small></strong>
                            <em>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['gold_weight']) }} {{ __('g') }}</em>
                        </div>
                        <div>
                            <span><i class="ri-vip-diamond-line"></i>{{ __('Silver') }}</span>
                            <strong>{{ number_format($stockStats['silver_count']) }} <small>{{ __('pieces') }}</small></strong>
                            <em>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['silver_weight']) }} {{ __('g') }}</em>
                        </div>
                    </div>
                    @if(($lowStockCount ?? 0) > 0)
                        <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                            <span class="text-danger fw-semibold fs-13 d-flex align-items-center gap-1">
                                <i class="ri-alarm-warning-line"></i>
                                {{ __('Low stock alert') }}: {{ number_format($lowStockCount) }} {{ __('products') }}
                            </span>
                            <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none">
                                {{ __('Filter') }} <i class="ri-arrow-left-line"></i>
                            </a>
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <section class="summary-panel mb-4" aria-labelledby="pulse-title">
            <div class="summary-panel-header">
                <div>
                    <span class="summary-section-kicker">{{ __('Store health') }}</span>
                    <h2 id="pulse-title">{{ __('Store pulse') }}</h2>
                </div>
            </div>
            <div class="summary-pulse-grid">
                <a href="{{ route('admin.product.index') }}" class="summary-pulse-item">
                    <i class="ri-vip-diamond-line"></i>
                    <span>{{ __('Products') }}</span>
                    <strong>{{ number_format($products) }}</strong>
                    <small>{{ __('Gold') }} {{ number_format($goldProducts) }} · {{ __('Silver') }} {{ number_format($silverProducts) }}</small>
                </a>
                <a href="{{ route('admin.customer.index') }}" class="summary-pulse-item">
                    <i class="ri-team-line"></i>
                    <span>{{ __('Customers') }}</span>
                    <strong>{{ number_format($customers) }}</strong>
                    <small>{{ __('Registered customers') }}</small>
                </a>
                <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::PAID]]) }}" class="summary-pulse-item">
                    <i class="ri-shopping-bag-4-line"></i>
                    <span>{{ __('Need process orders') }}</span>
                    <strong>{{ number_format($needProcess) }}</strong>
                    <small>{{ __('Paid and awaiting processing') }}</small>
                </a>
                <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_RECEIPT]]) }}" class="summary-pulse-item">
                    <i class="ri-upload-2-line"></i>
                    <span>{{ __('WAITING_RECEIPT') }}</span>
                    <strong>{{ number_format($waitingReceipt) }}</strong>
                    <small>{{ __('Waiting for customer payment receipt.') }}</small>
                </a>
                <a href="{{ route('admin.invoice.index', ['filter' => ['status' => \App\Models\Invoice::WAITING_CONFIRMATION]]) }}" class="summary-pulse-item">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>{{ __('WAITING_CONFIRMATION') }}</span>
                    <strong>{{ number_format($waitingConfirmation) }}</strong>
                    <small>{{ __('Payment receipt is awaiting your confirmation.') }}</small>
                </a>
                <a href="{{ route('admin.ticket.index', ['filter' => ['status' => 'PENDING']]) }}" class="summary-pulse-item">
                    <i class="ri-customer-service-2-line"></i>
                    <span>{{ __('Pending tickets') }}</span>
                    <strong>{{ number_format($pendingTickets) }}</strong>
                    <small>{{ __('Need an answer') }}</small>
                </a>
                <a href="{{ route('admin.bank-account.index') }}" class="summary-pulse-item">
                    <i class="ri-bank-card-line"></i>
                    <span>{{ __('Active bank account') }}</span>
                    <strong>{{ $bankAccount?->bank_name ?? '—' }}</strong>
                    <small>{{ $bankAccount?->account_holder_name ?? __('No active bank account') }}</small>
                </a>
                <a href="{{ route('admin.product.index', ['filter' => ['low_stock' => '1']]) }}" class="summary-pulse-item @if(($lowStockCount ?? 0) > 0) border-danger-subtle @endif">
                    <i class="ri-alarm-warning-line @if(($lowStockCount ?? 0) > 0) text-danger @endif"></i>
                    <span>{{ __('Low stock') }}</span>
                    <strong class="@if(($lowStockCount ?? 0) > 0) text-danger @endif">{{ number_format($lowStockCount ?? 0) }}</strong>
                    <small class="@if(($lowStockCount ?? 0) > 0) text-danger @endif">{{ __('Below minimum stock') }}</small>
                </a>
            </div>
        </section>

        <section class="summary-panel" aria-labelledby="recent-sales-title">
            <div class="summary-panel-header">
                <div>
                    <span class="summary-section-kicker">{{ __('Latest activity') }}</span>
                    <h2 id="recent-sales-title">{{ __('Latest successful sales') }}</h2>
                </div>
                <a href="{{ route('admin.invoice.index') }}" class="summary-panel-link">{{ __('View all invoices') }} <i class="ri-arrow-left-line"></i></a>
            </div>
            @if($recentSales->isEmpty())
                <p class="summary-empty mb-0"><i class="ri-inbox-line"></i>{{ __('No successful sales yet') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table summary-table mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Total price') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recentSales as $invoice)
                            <tr>
                                <td><a href="{{ route('admin.invoice.edit', $invoice) }}">#{{ $invoice->hash }}</a></td>
                                <td>{{ $invoice->customer?->name ?? '—' }}</td>
                                <td>{{ \App\Models\Invoice::formatPersianDateTime($invoice->created_at) }}</td>
                                <td>{{ number_format((int) $invoice->total_price) }} {{ config('app.currency.symbol') }}</td>
                                <td><span class="{{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </section>
@endsection
