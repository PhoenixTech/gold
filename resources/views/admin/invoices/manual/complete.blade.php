@extends('admin.invoices.manual.layout')

@section('step')
    <div class="item-list shadow-sm mb-4">
        <div class="p-4 p-md-5 text-center">
            <div class="mb-3">
                <i class="ri-checkbox-circle-fill text-success" style="font-size: 3.5rem;"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">{{ __('Shop invoice created successfully') }}</h4>
            <p class="text-muted fs-14 mb-3">{{ __('Invoice #:hash has been recorded.', ['hash' => $invoice->hash]) }}</p>

            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-4">
                <span class="{{ $invoice->statusBadgeClass() }} fs-13 px-3 py-1.5">{{ $invoice->statusLabel() }}</span>
                <span class="badge bg-light text-dark border fs-13 px-3 py-1.5 font-fanum">
                    {{ __('Total price') }}: {{ number_format($invoice->total_price) }} {{ __('Toman') }}
                </span>
                @if($invoice->remainingReceiptBalance() > 0)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-13 px-3 py-1.5 font-fanum">
                        {{ __('Remaining balance') }}: {{ number_format($invoice->remainingReceiptBalance()) }} {{ __('Toman') }}
                    </span>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-13 px-3 py-1.5">
                        {{ __('Settled') }}
                    </span>
                @endif
            </div>

            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 pt-2">
                <a href="{{ route('admin.invoice.print', $invoice->hash) }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="ri-printer-line"></i>
                    <span>{{ __('Print invoice') }}</span>
                </a>
                <a href="{{ route('admin.invoice.create') }}" class="btn btn-outline-primary px-3 d-inline-flex align-items-center gap-1.5">
                    <i class="ri-add-line"></i>
                    <span>{{ __('New invoice') }}</span>
                </a>
                @if($invoice->status === \App\Models\Invoice::COMPLETED)
                    <a href="{{ route('admin.invoice.show', $invoice) }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1.5">
                        <i class="ri-eye-line"></i>
                        <span>{{ __('View invoice') }}</span>
                    </a>
                @else
                    <a href="{{ route('admin.invoice.edit', $invoice) }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1.5">
                        <i class="ri-edit-line"></i>
                        <span>{{ __('Manage invoice') }}</span>
                    </a>
                @endif
                <a href="{{ route('admin.invoice.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1.5">
                    <i class="ri-arrow-right-line"></i>
                    <span>{{ __('Back to invoices') }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="item-list shadow-sm mb-4">
        <div class="p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-shopping-bag-3-line text-primary fs-18"></i>
                    <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Ordered pieces') }}</h5>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum">
                    {{ $invoice->orders->count() }}
                </span>
            </div>

            <div class="table-responsive border rounded-3">
                <table class="table align-middle mb-0">
                    <thead class="table-light text-muted fs-12 fw-semibold">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Piece code') }}</th>
                            <th>{{ __('Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->orders as $order)
                            <tr>
                                <td class="fw-semibold text-dark fs-13">{{ $order->product?->name }}</td>
                                <td class="font-monospace text-dark fs-12">{{ $order->quantity?->code ?: '—' }}</td>
                                <td class="font-fanum fw-bold text-dark fs-13">
                                    {{ number_format($order->price_total) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($invoice->payments->isNotEmpty())
        <div class="item-list shadow-sm mb-4">
            <div class="p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-bank-card-line text-primary fs-18"></i>
                        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Recorded payments') }}</h5>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-fanum">
                        {{ number_format($invoice->receivedAmount()) }} {{ __('Toman') }}
                    </span>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0 fs-13">
                        <thead class="table-light text-muted fs-12 fw-semibold">
                            <tr>
                                <th style="width: 2.5rem">#</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Related supplier') }}</th>
                                <th>{{ __('Destination account') }}</th>
                                <th>{{ __('Tracking Number') }}</th>
                                <th>{{ __('Date & Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $idx => $payment)
                                @php
                                    $methodEnum = ! empty($payment->meta['method']) ? \App\Enums\ShopPaymentMethod::tryFrom($payment->meta['method']) : null;
                                @endphp
                                <tr>
                                    <td class="font-fanum text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            {{ $methodEnum?->label() ?? ($payment->meta['method'] ?? $payment->type) }}
                                        </span>
                                    </td>
                                    <td class="font-fanum fw-bold text-success">
                                        {{ number_format($payment->amount) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                    </td>
                                    <td>
                                        @if($payment->supplier)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="ri-building-line me-0.5"></i>{{ $payment->supplier->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($payment->meta['bank_account_name']) || !empty($payment->meta['card_number']))
                                            <span class="font-fanum fw-semibold text-dark">{{ $payment->meta['bank_account_name'] ?? '' }}</span>
                                            @if(!empty($payment->meta['card_number']))
                                                <small class="text-muted font-monospace d-block fs-11" dir="ltr">{{ $payment->meta['card_number'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace text-dark">{{ $payment->reference_id ?: '—' }}</td>
                                    <td class="font-fanum text-dark">
                                        {{ $payment->meta['payment_date'] ?? ($payment->created_at ? $payment->created_at->jdate('Y/m/d') : '—') }}
                                        @if(!empty($payment->meta['payment_time']))
                                            <small class="text-muted">{{ $payment->meta['payment_time'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
