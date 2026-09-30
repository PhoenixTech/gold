@extends('layouts.customer')

@section('title')
    {{ __('Register Payment Receipt') }} - {{ config('app.name') }}
@endsection

@section('customer-content')
@php
    $receipts = $invoice->paymentReceipts ?? collect();
    $currency = config('app.currency.symbol') ?: __('Toman');
    $totalOrder = (int) $invoice->total_price;
    $totalUploaded = (int) $invoice->receiptsTotalAmount();
    $remaining = (int) $invoice->remainingReceiptBalance();
    $hasReceipts = $invoice->hasUploadedReceipt();
    $jalaliYear = now()->jdate('Y', 'en');
    $jalaliMonth = now()->jdate('m', 'en');
    $jalaliDay = (int) now()->jdate('d', 'en');
    $jalaliYearMonth = $jalaliYear . '/' . $jalaliMonth;
    $jalaliMonthNum = (int) $jalaliMonth;
    $daysInMonth = $jalaliMonthNum <= 6 ? 31 : ($jalaliMonthNum <= 11 ? 30 : 29);
@endphp

<div class="receipt-registration-container">
    @if(session('message'))
        <div class="alert alert-success border border-success-subtle shadow-sm p-3 mb-3 rounded-3 d-flex align-items-center gap-2">
            <i class="ri-checkbox-circle-line fs-4 text-success"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border border-danger-subtle shadow-sm p-3 mb-3 rounded-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="avisa-subnav-head mb-3">
        <a href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), '/profile') ? route('client.profile').'#invoices' : route('client.invoice', $invoice) }}" class="avisa-subnav-back" aria-label="{{ __('Back') }}">
            <i class="ri-arrow-right-line"></i>
        </a>
        <div class="d-flex align-items-center justify-content-between flex-grow-1">
            <h4 class="fw-bold mb-0 text-dark">{{ __('Register Payment Receipt') }} <span class="font-fanum text-muted fs-13">#{{ $invoice->id }}</span></h4>
            <span class="inv-badge inv-{{ $invoice->displayStatusKey() }}">{{ $invoice->statusLabel() }}</span>
        </div>
    </div>

    @if($hasReceipts)
        <div class="alert alert-info border border-info-subtle shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3">
            <i class="ri-time-line fs-2 text-info"></i>
            <div>
                <strong class="d-block text-dark">{{ __('Waiting for store confirmation') }}</strong>
                <span class="text-muted fs-13">{{ __('Awaiting store verification. Our team will contact you within a few hours.') }}</span>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fs-15 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ri-calculator-line text-primary fs-18"></i>
                        {{ __('Order Summary') }}
                    </h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-around">
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="text-muted fs-13">{{ __('Total Order Amount') }}</span>
                        <span class="fw-bold text-dark fs-15 font-fanum">
                            <span id="tally-order" data-amount="{{ $totalOrder }}">{{ number_format($totalOrder) }}</span> {{ $currency }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="text-muted fs-13">{{ __('Total Amount Uploaded') }}</span>
                        <span class="fw-bold text-success fs-15 font-fanum">
                            <span id="tally-uploaded" data-initial="{{ $totalUploaded }}">{{ number_format($totalUploaded) }}</span> {{ $currency }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span class="text-muted fs-13" id="tally-balance-label">{{ $totalUploaded > $totalOrder ? __('Overpayment Amount') : __('Remaining Balance') }}</span>
                        <span class="fw-bold fs-15 font-fanum {{ $totalUploaded > $totalOrder ? 'text-primary' : ($remaining === 0 ? 'text-success' : 'text-danger') }}" id="tally-balance-wrap">
                            <span id="tally-remaining">{{ ($totalUploaded > $totalOrder ? '+ ' : '') . number_format(abs($remaining)) }}</span> {{ $currency }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($receipts->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title fs-15 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-file-list-3-line text-primary fs-18"></i>
                    {{ __('Uploaded receipts') }}
                </h5>
                <span class="badge bg-secondary-subtle text-secondary font-fanum fs-12">{{ $receipts->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-13">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Tracking Number') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Payment Date') }}</th>
                                <th>{{ __('Payment Time') }}</th>
                                <th>{{ __('Receipt Image / Slip') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipts as $receipt)
                                <tr>
                                    <td class="font-monospace">{{ $receipt->tracking_number ?: '-' }}</td>
                                    <td class="font-fanum fw-bold">{{ $receipt->amount ? number_format($receipt->amount).' '.$currency : '-' }}</td>
                                    <td class="font-fanum">{{ $receipt->payment_date ?: '-' }}</td>
                                    <td class="font-fanum">{{ $receipt->payment_time ?: '-' }}</td>
                                    <td>
                                        <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0 px-2 fs-12">
                                            <i class="{{ $receipt->isImage() ? 'ri-image-line' : 'ri-file-pdf-2-line' }} me-1"></i>
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($invoice->status === \App\Models\Invoice::AWAITING_PAYMENT && ! $invoice->isOfflinePaymentExpired())
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title fs-15 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-upload-cloud-2-line text-primary fs-18"></i>
                    {{ __('Upload payment receipt(s)') }}
                </h5>
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-pill px-3" id="add-receipt-btn">
                    <i class="ri-add-line"></i>
                    <span>{{ __('Add Receipt') }}</span>
                </button>
            </div>
            <div class="card-body">
                <form action="{{ route('client.invoice.receipts.store', $invoice) }}" method="POST" enctype="multipart/form-data" id="receipts-form">
                    @csrf
                    <div id="receipt-rows-container">
                        <div class="receipt-row border rounded-3 p-3 mb-3 bg-light" data-row-index="0">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fw-bold text-dark fs-13 receipt-row-title">#1</span>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn d-none py-0 px-2 fs-12">
                                    <i class="ri-delete-bin-line me-1"></i>
                                    {{ __('Remove') }}
                                </button>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label fs-12 text-muted mb-1">{{ __('Amount') }} ({{ $currency }})</label>
                                    <input type="number" name="receipts[0][amount]" class="form-control form-control-sm receipt-amount-input" placeholder="{{ __('Amount') }}">
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label fs-12 text-muted mb-1">{{ __('Tracking Number') }}</label>
                                    <input type="text" name="receipts[0][tracking_number]" class="form-control form-control-sm" placeholder="{{ __('Tracking Number') }}">
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label fs-12 text-muted mb-1">{{ __('Payment Date') }}</label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm receipt-day-select font-fanum">
                                            @for($d = 1; $d <= $daysInMonth; $d++)
                                                <option value="{{ sprintf('%02d', $d) }}" {{ $d === $jalaliDay ? 'selected' : '' }}>
                                                    {{ sprintf('%02d', $d) }}
                                                </option>
                                            @endfor
                                        </select>
                                        <span class="input-group-text bg-light text-muted font-fanum fs-12 px-2">/{{ $jalaliYearMonth }}</span>
                                        <input type="hidden" name="receipts[0][payment_date]" class="receipt-payment-date" value="{{ $jalaliYearMonth . '/' . sprintf('%02d', $jalaliDay) }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label fs-12 text-muted mb-1">{{ __('Payment Time') }}</label>
                                    <input type="time" name="receipts[0][payment_time]" class="form-control form-control-sm" placeholder="12:00" value="{{ date('H:i') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-12 text-muted mb-1">{{ __('Receipt Image / Slip') }}</label>
                                    <input type="file" name="receipts[0][slip]" class="form-control form-control-sm" accept="image/*,.pdf">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ri-check-line me-1"></i> {{ __('Submit Receipts') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var totalOrder = parseInt(document.getElementById('tally-order')?.getAttribute('data-amount') || '0', 10);
    var initialUploaded = parseInt(document.getElementById('tally-uploaded')?.getAttribute('data-initial') || '0', 10);
    var container = document.getElementById('receipt-rows-container');
    var addBtn = document.getElementById('add-receipt-btn');
    var addBtnSecondary = document.getElementById('add-receipt-btn-secondary');
    var rowIndex = 1;
    var yearMonthPrefix = '{{ $jalaliYearMonth }}';
    var daysInMonth = {{ $daysInMonth }};
    var currentDay = {{ $jalaliDay }};

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function recalculateTally() {
        var inputs = document.querySelectorAll('.receipt-amount-input');
        var formSum = 0;
        inputs.forEach(function (inp) {
            var val = parseInt(inp.value, 10);
            if (!isNaN(val) && val > 0) {
                formSum += val;
            }
        });

        var uploadedTotal = initialUploaded + formSum;
        var diff = totalOrder - uploadedTotal;

        var upEl = document.getElementById('tally-uploaded');
        if (upEl) {
            upEl.textContent = formatNumber(uploadedTotal);
        }

        var remEl = document.getElementById('tally-remaining');
        var labelEl = document.getElementById('tally-balance-label');
        var wrapEl = document.getElementById('tally-balance-wrap');

        if (diff < 0) {
            var overpayment = Math.abs(diff);
            if (labelEl) {
                labelEl.textContent = '{{ __("Overpayment Amount") }}';
            }
            if (remEl) {
                remEl.textContent = '+ ' + formatNumber(overpayment);
            }
            if (wrapEl) {
                wrapEl.classList.remove('text-danger', 'text-success');
                wrapEl.classList.add('text-primary');
            }
        } else {
            if (labelEl) {
                labelEl.textContent = '{{ __("Remaining Balance") }}';
            }
            if (remEl) {
                remEl.textContent = formatNumber(diff);
            }
            if (wrapEl) {
                wrapEl.classList.remove('text-primary');
                if (diff === 0) {
                    wrapEl.classList.remove('text-danger');
                    wrapEl.classList.add('text-success');
                } else {
                    wrapEl.classList.remove('text-success');
                    wrapEl.classList.add('text-danger');
                }
            }
        }
    }

    function updateRemoveButtons() {
        var rows = container ? container.querySelectorAll('.receipt-row') : [];
        rows.forEach(function (row, idx) {
            var removeBtn = row.querySelector('.remove-row-btn');
            var title = row.querySelector('.receipt-row-title');
            if (title) {
                title.textContent = '#' + (idx + 1);
            }
            if (removeBtn) {
                if (rows.length > 1) {
                    removeBtn.classList.remove('d-none');
                } else {
                    removeBtn.classList.add('d-none');
                }
            }
        });
    }

    function addRow() {
        if (!container) return;
        var idx = rowIndex++;
        var daysOptions = '';
        for (var d = 1; d <= daysInMonth; d++) {
            var val = (d < 10 ? '0' : '') + d;
            daysOptions += '<option value="' + val + '"' + (d === currentDay ? ' selected' : '') + '>' + val + '</option>';
        }
        var currentDayFormatted = (currentDay < 10 ? '0' : '') + currentDay;
        var row = document.createElement('div');
        row.className = 'receipt-row border rounded-3 p-3 mb-3 bg-light';
        row.setAttribute('data-row-index', idx);
        row.innerHTML = '<div class="d-flex align-items-center justify-content-between mb-3">' +
            '<span class="fw-bold text-dark fs-13 receipt-row-title">#' + (container.children.length + 1) + '</span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn py-0 px-2 fs-12">' +
            '<i class="ri-delete-bin-line me-1"></i> {{ __("Remove") }}' +
            '</button>' +
            '</div>' +
            '<div class="row g-2">' +
            '<div class="col-12 col-md-6 col-lg-3">' +
            '<label class="form-label fs-12 text-muted mb-1">{{ __("Amount") }} ({{ $currency }})</label>' +
            '<input type="number" name="receipts[' + idx + '][amount]" class="form-control form-control-sm receipt-amount-input" placeholder="{{ __("Amount") }}">' +
            '</div>' +
            '<div class="col-12 col-md-6 col-lg-3">' +
            '<label class="form-label fs-12 text-muted mb-1">{{ __("Tracking Number") }}</label>' +
            '<input type="text" name="receipts[' + idx + '][tracking_number]" class="form-control form-control-sm" placeholder="{{ __("Tracking Number") }}">' +
            '</div>' +
            '<div class="col-12 col-md-6 col-lg-3">' +
            '<label class="form-label fs-12 text-muted mb-1">{{ __("Payment Date") }}</label>' +
            '<div class="input-group input-group-sm">' +
            '<select class="form-select form-select-sm receipt-day-select font-fanum">' + daysOptions + '</select>' +
            '<span class="input-group-text bg-light text-muted font-fanum fs-12 px-2" dir="ltr">/' + yearMonthPrefix + '</span>' +
            '<input type="hidden" name="receipts[' + idx + '][payment_date]" class="receipt-payment-date" value="' + yearMonthPrefix + '/' + currentDayFormatted + '">' +
            '</div>' +
            '</div>' +
            '<div class="col-12 col-md-6 col-lg-3">' +
            '<label class="form-label fs-12 text-muted mb-1">{{ __("Payment Time") }}</label>' +
            '<input type="text" name="receipts[' + idx + '][payment_time]" class="form-control form-control-sm" placeholder="12:00" value="{{ date("H:i") }}">' +
            '</div>' +
            '<div class="col-12">' +
            '<label class="form-label fs-12 text-muted mb-1">{{ __("Receipt Image / Slip") }}</label>' +
            '<input type="file" name="receipts[' + idx + '][slip]" class="form-control form-control-sm" accept="image/*,.pdf">' +
            '</div>' +
            '</div>';

        container.appendChild(row);
        updateRemoveButtons();
        bindEvents(row);
    }

    function bindEvents(scope) {
        var inputs = scope.querySelectorAll('.receipt-amount-input');
        inputs.forEach(function (inp) {
            inp.addEventListener('input', recalculateTally);
        });

        var daySelects = scope.querySelectorAll('.receipt-day-select');
        daySelects.forEach(function (sel) {
            sel.addEventListener('change', function () {
                var group = sel.closest('.input-group');
                var hidden = group ? group.querySelector('.receipt-payment-date') : null;
                if (hidden) {
                    hidden.value = yearMonthPrefix + '/' + sel.value;
                }
            });
        });

        var removeBtns = scope.querySelectorAll('.remove-row-btn');
        removeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('.receipt-row');
                if (row && container.children.length > 1) {
                    row.remove();
                    updateRemoveButtons();
                    recalculateTally();
                }
            });
        });
    }

    if (container) {
        bindEvents(container);
    }

    if (addBtn) {
        addBtn.addEventListener('click', addRow);
    }
    if (addBtnSecondary) {
        addBtnSecondary.addEventListener('click', addRow);
    }

    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-copy');
            if (text && navigator.clipboard) {
                navigator.clipboard.writeText(text);
                var icon = btn.querySelector('i');
                if (icon) {
                    var prev = icon.className;
                    icon.className = 'ri-check-line text-success fs-16';
                    setTimeout(function () {
                        icon.className = prev;
                    }, 1500);
                }
            }
        });
    });
});
</script>
@endsection
