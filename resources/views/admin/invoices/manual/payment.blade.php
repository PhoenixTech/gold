@extends('admin.invoices.manual.layout')

@section('step')
    @php
        $payments = old('payments', $draft['payments'] ?? []);
        if (empty($payments)) {
            $payments = [[
                'method' => \App\Enums\ShopPaymentMethod::Pos->value,
                'amount' => $total > 0 ? $total : '',
                'supplier_id' => '',
                'bank_account_id' => $bankAccounts->first()?->id ?? '',
                'tracking_number' => '',
                'payment_date' => now()->jdate('Y/m/d', 'en'),
                'payment_time' => now()->format('H:i'),
                'note' => '',
            ]];
        }
        $jalaliToday = now()->jdate('Y/m/d', 'en');
        $currentTime = now()->format('H:i');
    @endphp

    <form method="POST" action="{{ route('admin.invoice.store') }}" enctype="multipart/form-data" id="manual-payment-form">
        @csrf
        <input type="hidden" name="step" value="payment">

        <div class="item-list shadow-sm mb-4">
            <div class="p-3">
                <div class="row g-2 text-center align-items-center">
                    <div class="col-4 border-end">
                        <span class="text-muted d-block fs-12 fw-medium mb-1">{{ __('Invoice total') }}</span>
                        <span class="fs-18 fw-bold font-fanum text-dark d-block" id="display-invoice-total" data-total="{{ $total }}">
                            {{ number_format($total) }} <small class="fs-11 fw-normal text-muted">{{ __('Toman') }}</small>
                        </span>
                    </div>
                    <div class="col-4 border-end">
                        <span class="text-muted d-block fs-12 fw-medium mb-1">{{ __('Total payments') }}</span>
                        <span class="fs-18 fw-bold font-fanum text-success d-block" id="display-paid-total">
                            0 <small class="fs-11 fw-normal text-muted">{{ __('Toman') }}</small>
                        </span>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block fs-12 fw-medium mb-1">{{ __('Remaining balance') }}</span>
                        <span class="fs-18 fw-bold font-fanum text-danger d-block" id="display-remaining">
                            {{ number_format($total) }} <small class="fs-11 fw-normal text-muted">{{ __('Toman') }}</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="item-list shadow-sm mb-4">
            <div class="p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-bank-card-line text-primary fs-18"></i>
                        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Payment details') }}</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="add-payment-btn">
                        <i class="ri-add-line"></i>
                        <span>{{ __('Add another payment') }}</span>
                    </button>
                </div>

                <div id="payment-rows-container">
                    @foreach($payments as $index => $row)
                        <div class="payment-row border border-light-subtle rounded-3 p-3 mb-3 bg-light" data-row-index="{{ $index }}">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum px-2 py-1 payment-row-title">#{{ $index + 1 }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-payment-btn py-0 px-2 fs-12 d-inline-flex align-items-center gap-1 {{ count($payments) <= 1 ? 'd-none' : '' }}">
                                    <i class="ri-delete-bin-line"></i>
                                    <span>{{ __('Remove') }}</span>
                                </button>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Payment method') }} <span class="text-danger">*</span></label>
                                    <select name="payments[{{ $index }}][method]" class="form-select form-select-sm payment-method-select" required>
                                        @foreach($methods as $method)
                                            <option value="{{ $method->value }}" @selected(($row['method'] ?? '') === $method->value)>
                                                {{ $method->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Amount') }} ({{ __('Toman') }}) <span class="text-danger">*</span></label>
                                    <input type="text" name="payments[{{ $index }}][amount]"
                                           class="form-control form-control-sm font-fanum payment-amount-input"
                                           value="{{ $row['amount'] ?? '' }}" placeholder="50,000,000" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Related supplier') }}</label>
                                    <select name="payments[{{ $index }}][supplier_id]" class="form-select form-select-sm">
                                        <option value="">{{ __('— None (Gallery sale) —') }}</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((string) ($row['supplier_id'] ?? '') === (string) $supplier->id)>
                                                {{ $supplier->name }}@if($supplier->company_name) — {{ $supplier->company_name }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Destination account') }}</label>
                                    <select name="payments[{{ $index }}][bank_account_id]" class="form-select form-select-sm">
                                        <option value="">{{ __('— Select bank account —') }}</option>
                                        @foreach($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}" @selected((string) ($row['bank_account_id'] ?? '') === (string) $bank->id)>
                                                {{ $bank->bank_name }} ({{ $bank->card_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Tracking Number') }}</label>
                                    <input type="text" name="payments[{{ $index }}][tracking_number]"
                                           class="form-control form-control-sm font-monospace"
                                           value="{{ $row['tracking_number'] ?? '' }}" placeholder="12345678">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Payment Date') }}</label>
                                    <input type="text" name="payments[{{ $index }}][payment_date]"
                                           class="form-control form-control-sm font-fanum"
                                           value="{{ $row['payment_date'] ?? $jalaliToday }}" placeholder="{{ $jalaliToday }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Payment Time') }}</label>
                                    <input type="time" name="payments[{{ $index }}][payment_time]"
                                           class="form-control form-control-sm"
                                           value="{{ $row['payment_time'] ?? $currentTime }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fs-12 text-muted fw-semibold mb-1">{{ __('Receipt Image / Slip') }}</label>
                                    <input type="file" name="payments[{{ $index }}][slip]" class="form-control form-control-sm" accept="image/*,.pdf">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-info border border-info-subtle shadow-sm py-2 px-3 mt-3 mb-0 fs-13 d-flex align-items-center gap-2" id="payment-status-hint">
                    <i class="ri-information-line fs-5 text-info"></i>
                    <span id="payment-status-text">{{ __('If payments are entered now, they will be registered as settled.') }}</span>
                </div>
            </div>
        </div>

        <div class="item-list shadow-sm mb-4">
            <div class="p-3 p-md-4">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="handover" value="1" id="manual-handover"
                           @checked($draft['handover'] ?? false)>
                    <label class="form-check-label fw-bold text-dark fs-14" for="manual-handover">{{ __('Hand over to the customer now') }}</label>
                    <div class="form-text fs-12 text-muted" id="handover-help-text">
                        {{ __('For fully paid sales only. The invoice is marked as completed right after saving.') }}
                    </div>
                </div>

                <label for="manual-note" class="form-label fs-13 fw-semibold text-dark">
                    {{ __('Note') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
                </label>
                <textarea id="manual-note" name="note" rows="2" maxlength="500" class="form-control form-control-sm">{{ old('note', $draft['note'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between gap-2">
            <a href="{{ route('admin.invoice.create', ['step' => 'items']) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="ri-arrow-right-line"></i>
                <span>{{ __('Back') }}</span>
            </a>
            <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5">
                <span>{{ __('Next') }}</span>
                <i class="ri-arrow-left-line"></i>
            </button>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('payment-rows-container');
        var addBtn = document.getElementById('add-payment-btn');
        var total = parseInt(document.getElementById('display-invoice-total')?.getAttribute('data-total') || '0', 10);
        var displayPaid = document.getElementById('display-paid-total');
        var displayRemaining = document.getElementById('display-remaining');
        var handoverCheckbox = document.getElementById('manual-handover');
        var statusText = document.getElementById('payment-status-text');

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function parseAmount(val) {
            if (!val) return 0;
            var clean = val.toString().replace(/[\s,]/g, '').replace(/[۰-۹]/g, function (d) {
                return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
            });
            return parseInt(clean, 10) || 0;
        }

        function reindexRows() {
            var rows = container.querySelectorAll('.payment-row');
            rows.forEach(function (row, idx) {
                row.setAttribute('data-row-index', idx);
                var titleEl = row.querySelector('.payment-row-title');
                if (titleEl) {
                    titleEl.innerText = '#' + (idx + 1);
                }
                row.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (el.name) {
                        el.name = el.name.replace(/payments\[\d+\]/, 'payments[' + idx + ']');
                    }
                });
                var removeBtn = row.querySelector('.remove-payment-btn');
                if (removeBtn) {
                    if (rows.length <= 1) {
                        removeBtn.classList.add('d-none');
                    } else {
                        removeBtn.classList.remove('d-none');
                    }
                }
            });
        }

        function recalculate() {
            var inputs = container.querySelectorAll('.payment-amount-input');
            var sum = 0;
            inputs.forEach(function (inp) {
                sum += parseAmount(inp.value);
            });

            var remaining = Math.max(0, total - sum);

            displayPaid.innerHTML = formatNumber(sum) + ' <small class="fs-11 fw-normal text-muted">{{ __("Toman") }}</small>';
            displayRemaining.innerHTML = formatNumber(remaining) + ' <small class="fs-11 fw-normal text-muted">{{ __("Toman") }}</small>';

            if (remaining === 0 && total > 0) {
                displayRemaining.className = 'fs-18 fw-bold font-fanum text-success d-block';
                handoverCheckbox.disabled = false;
                statusText.innerText = '{{ __("Paid in full. The invoice can be handed over to the customer.") }}';
            } else {
                displayRemaining.className = 'fs-18 fw-bold font-fanum text-danger d-block';
                handoverCheckbox.checked = false;
                handoverCheckbox.disabled = true;
                if (sum === 0) {
                    statusText.innerText = '{{ __("No payments recorded. The invoice will wait for payment.") }}';
                } else {
                    statusText.innerText = '{{ __("Partially paid (:amount Toman remaining). The invoice will wait for payment.", ["amount" => ":amount"]) }}'.replace(':amount', formatNumber(remaining));
                }
            }
        }

        container.addEventListener('input', function (e) {
            if (e.target.classList.contains('payment-amount-input')) {
                recalculate();
            }
        });

        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-payment-btn');
            if (btn) {
                var row = btn.closest('.payment-row');
                if (container.querySelectorAll('.payment-row').length > 1) {
                    row.remove();
                    reindexRows();
                    recalculate();
                }
            }
        });

        addBtn.addEventListener('click', function () {
            var firstRow = container.querySelector('.payment-row');
            if (!firstRow) {
                return;
            }

            var newRow = firstRow.cloneNode(true);

            var currentInputs = container.querySelectorAll('.payment-amount-input');
            var currentSum = 0;
            currentInputs.forEach(function (inp) {
                currentSum += parseAmount(inp.value);
            });
            var diff = Math.max(0, total - currentSum);

            var amountInput = newRow.querySelector('.payment-amount-input');
            if (amountInput) {
                amountInput.value = diff > 0 ? diff : '';
            }

            var methodSelect = newRow.querySelector('.payment-method-select');
            if (methodSelect) {
                methodSelect.selectedIndex = 0;
            }

            var supplierSelect = newRow.querySelector('select[name$="[supplier_id]"]');
            if (supplierSelect) {
                supplierSelect.value = '';
            }

            var bankSelect = newRow.querySelector('select[name$="[bank_account_id]"]');
            if (bankSelect) {
                bankSelect.selectedIndex = 0;
            }

            var trackingInput = newRow.querySelector('input[name$="[tracking_number]"]');
            if (trackingInput) {
                trackingInput.value = '';
            }

            var dateInput = newRow.querySelector('input[name$="[payment_date]"]');
            if (dateInput) {
                dateInput.value = '{{ $jalaliToday }}';
            }

            var timeInput = newRow.querySelector('input[name$="[payment_time]"]');
            if (timeInput) {
                timeInput.value = '{{ $currentTime }}';
            }

            var slipInput = newRow.querySelector('input[name$="[slip]"]');
            if (slipInput) {
                slipInput.value = '';
            }

            container.appendChild(newRow);
            reindexRows();
            recalculate();
        });

        reindexRows();
        recalculate();
    });
    </script>
@endsection
