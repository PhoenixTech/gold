@extends('admin.invoices.manual.layout')

@section('step')
    @php
        $customer = $draft['customer'];
        $recordedPayments = $draft['payments'] ?? [];
        $paidSum = array_sum(array_map(fn ($p) => (int) ($p['amount'] ?? 0), $recordedPayments));
        $remainingBalance = max(0, $total - $paidSum);
        $isFullyPaid = $paidSum >= $total && $total > 0;
        $willBeCollected = $isFullyPaid && ($draft['handover'] ?? false);
        $hasUnavailable = $lines->contains(fn (array $line) => ! $line['available']);
    @endphp

    <div class="item-list shadow-sm mb-4">
        <div class="p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="ri-checkbox-circle-line text-primary fs-18"></i>
                <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Check before saving') }}</h5>
            </div>

            <ul class="list-group list-group-flush border rounded-3 mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 p-3">
                    <span class="text-muted fs-13">{{ __('Customer') }}</span>
                    <b class="text-dark fs-13">{{ ($customer['name'] ?? '') ?: '—' }}</b>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 p-3">
                    <span class="text-muted fs-13">{{ __('Mobile') }}</span>
                    <b class="font-fanum text-dark fs-13" dir="ltr">{{ $customer['mobile'] }}</b>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 p-3">
                    <span class="text-muted fs-13">{{ __('Customer type') }}</span>
                    <span class="badge bg-light text-dark border">{{ empty($customer['id']) ? __('New customer') : __('Existing customer') }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 p-3">
                    <span class="text-muted fs-13">{{ __('Status after saving') }}</span>
                    <span>
                        @if($willBeCollected)
                            <span class="badge bg-success text-white">{{ __('Collected') }}</span>
                        @elseif($isFullyPaid)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Paid, waiting for pickup') }}</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                {{ __('Waiting for payment') }} ({{ number_format($remainingBalance) }} {{ __('Toman') }} {{ __('remaining') }})
                            </span>
                        @endif
                    </span>
                </li>
                @if(! empty($draft['note']))
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2 p-3">
                        <span class="text-muted fs-13">{{ __('Note') }}</span>
                        <span class="text-dark fs-13">{{ $draft['note'] }}</span>
                    </li>
                @endif
            </ul>

            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ri-shopping-bag-3-line text-primary fs-18"></i>
                <h6 class="fw-bold text-dark fs-14 mb-0">{{ __('Ordered pieces') }}</h6>
            </div>
            <div class="table-responsive border rounded-3 mb-4">
                <table class="table align-middle mb-0">
                    <thead class="table-light text-muted fs-12 fw-semibold">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Piece code') }}</th>
                            <th>{{ __('Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                            <tr class="{{ $line['available'] ? '' : 'table-danger' }}">
                                <td class="fw-semibold text-dark fs-13">{{ $line['piece']?->product?->name ?? '#'.$line['id'] }}</td>
                                <td class="font-monospace text-dark fs-12">{{ $line['piece']?->code ?: '—' }}</td>
                                <td class="font-fanum fw-bold text-dark fs-13">
                                    {{ number_format($line['price']) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-dark">{{ __('Total price') }}</th>
                            <th class="font-fanum fw-bold text-dark fs-14">
                                {{ number_format($total) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ri-bank-card-line text-primary fs-18"></i>
                <h6 class="fw-bold text-dark fs-14 mb-0">{{ __('Recorded payments') }}</h6>
            </div>
            @if(empty($recordedPayments))
                <div class="alert alert-light border p-3 mb-4 fs-13 text-muted d-flex align-items-center gap-2">
                    <i class="ri-time-line text-muted fs-16"></i>
                    <span>{{ __('No payments entered now. The invoice will be registered as awaiting payment.') }}</span>
                </div>
            @else
                <div class="table-responsive border rounded-3 mb-4">
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
                            @foreach($recordedPayments as $idx => $payment)
                                @php
                                    $methodEnum = \App\Enums\ShopPaymentMethod::tryFrom($payment['method'] ?? '');
                                    $supplierObj = ! empty($payment['supplier_id']) ? ($suppliers->get($payment['supplier_id']) ?? null) : null;
                                    $bankObj = ! empty($payment['bank_account_id']) ? ($bankAccounts->get($payment['bank_account_id']) ?? null) : null;
                                @endphp
                                <tr>
                                    <td class="font-fanum text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            {{ $methodEnum?->label() ?? ($payment['method'] ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="font-fanum fw-bold text-success">
                                        {{ number_format($payment['amount'] ?? 0) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                    </td>
                                    <td>
                                        @if($supplierObj)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="ri-building-line me-0.5"></i>{{ $supplierObj->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($bankObj)
                                            <span class="font-fanum fw-semibold text-dark">{{ $bankObj->bank_name }}</span>
                                            <small class="text-muted font-monospace d-block fs-11" dir="ltr">{{ $bankObj->card_number }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace text-dark">{{ $payment['tracking_number'] ?? '—' }}</td>
                                    <td class="font-fanum text-dark">
                                        {{ $payment['payment_date'] ?? '—' }} <small class="text-muted">{{ $payment['payment_time'] ?? '' }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-dark">{{ __('Total paid') }}</th>
                                <th class="font-fanum fw-bold text-success fs-14">
                                    {{ number_format($paidSum) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </th>
                                <th colspan="4"></th>
                            </tr>
                            @if($remainingBalance > 0)
                                <tr>
                                    <th colspan="2" class="text-dark">{{ __('Remaining balance') }}</th>
                                    <th class="font-fanum fw-bold text-danger fs-14">
                                        {{ number_format($remainingBalance) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                    </th>
                                    <th colspan="4" class="text-muted fs-12 fw-normal">{{ __('Will be collected later.') }}</th>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            @endif

            @if($hasUnavailable)
                <div class="alert alert-danger border border-danger-subtle shadow-sm p-3 mb-0 rounded-3 d-flex align-items-center gap-2">
                    <i class="ri-error-warning-line fs-5 text-danger"></i>
                    <span>{{ __('Some pieces are no longer available. Remove them in the items step before saving.') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="{{ route('admin.invoice.create', ['step' => 'payment']) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="ri-arrow-right-line"></i>
            <span>{{ __('Back') }}</span>
        </a>

        <form method="POST" action="{{ route('admin.invoice.store') }}">
            @csrf
            <input type="hidden" name="step" value="review">
            <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-1.5" @disabled($hasUnavailable)>
                <i class="ri-check-double-line"></i>
                <span>{{ __('Create invoice') }}</span>
            </button>
        </form>
    </div>
@endsection
