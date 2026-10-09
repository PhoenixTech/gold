<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 p-3 p-md-3.5 border-bottom">
        <i class="ri-file-list-3-line text-primary fs-18"></i>
        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Receipt review') }}</h5>
    </div>
    <div class="p-3 p-md-3.5">
        <p class="text-muted fs-13 mb-3">{{ __('Check the receipt against the invoice total, then confirm the payment or send it back to the customer.') }}</p>
        <div class="table-responsive border rounded-3 mb-3">
            <table class="table table-hover align-middle mb-0 fs-13">
                <thead class="table-light text-muted fs-12 fw-semibold">
                    <tr>
                        <th style="width: 60px;">{{ __('Preview') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Destination account') }}</th>
                        <th>{{ __('Payment Date & Time') }}</th>
                        <th>{{ __('Tracking Number') }}</th>
                        <th>{{ __('File') }}</th>
                        <th class="text-center" style="width: 90px;">{{ __('Action') }}</th>
                    </tr>
                </thead>
                                <tbody>
                                    @forelse($item->paymentReceipts as $receipt)
                                        @php $declaredAccount = $receipt->bankAccount; @endphp
                                        <tr>
                                            <td class="text-center p-1">
                                                <button type="button" class="btn btn-link p-0 border-0 receipt-zoom-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#receipt-preview-modal"
                                                        data-receipt-preview="{{ $receipt->url() }}"
                                                        data-receipt-name="{{ $receipt->original_name }}"
                                                        aria-label="{{ __('Preview receipt') }}">
                                                    @if($receipt->isImage())
                                                        <img src="{{ $receipt->url() }}" alt="{{ $receipt->original_name }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                            <i class="ri-file-pdf-2-line fs-4 text-danger"></i>
                                                        </div>
                                                    @endif
                                                </button>
                                            </td>
                                            <td>
                                                @if($receipt->amount)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-13 font-fanum fw-bold">
                                                        {{ number_format($receipt->amount) }} {{ __('Toman') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($declaredAccount)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-fanum fs-12" title="{{ $declaredAccount->card_number }}">
                                                        {{ $declaredAccount->bank_name }}
                                                    </span>
                                                    <small class="text-muted font-monospace fs-11 d-block" dir="ltr">{{ $declaredAccount->card_number }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($receipt->payment_date || $receipt->payment_time)
                                                    <div class="font-fanum text-dark fw-semibold">
                                                        {{ $receipt->payment_date ?: '-' }}
                                                        @if($receipt->payment_time)
                                                            <span class="text-muted font-monospace fs-12 ms-1">({{ $receipt->payment_time }})</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted font-fanum fs-12">{{ \App\Models\Invoice::formatPersianDateTime($receipt->created_at) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($receipt->tracking_number)
                                                    <code class="fw-bold text-dark font-monospace bg-light px-2 py-0.5 rounded border fs-12">
                                                        {{ $receipt->tracking_number }}
                                                    </code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-truncate d-block" style="max-width: 170px;" title="{{ $receipt->original_name }}">
                                                    {{ $receipt->original_name }}
                                                </span>
                                                @if($receipt->size)
                                                    <small class="text-muted font-fanum fs-11">{{ number_format($receipt->size / 1024, 1) }} KB</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ $receipt->url() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1 fs-12">
                                                    <i class="ri-external-link-line"></i>
                                                    <span>{{ __('View') }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="ri-inbox-line fs-4 d-block mb-2 opacity-50"></i>
                                                {{ __('No receipt uploaded yet.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @php
                            $receiptsTotal = $item->receiptsTotalAmount();
                            $remainingBalance = $item->remainingReceiptBalance();
                            $activeBankAccounts = $bankAccounts ?? \App\Models\BankAccount::where('is_active', true)->get();
                        @endphp

                        <div class="card bg-light border border-light-subtle rounded-3 p-3 mb-3">
                            <div class="row g-3 text-center">
                                <div class="col-md-4">
                                    <span class="text-muted fs-13 d-block">{{ __('Invoice Total Amount') }}</span>
                                    <strong class="fs-5 text-dark">{{ number_format($item->total_price) }} {{ __('Toman') }}</strong>
                                </div>
                                <div class="col-md-4 border-start border-end">
                                    <span class="text-muted fs-13 d-block">{{ __('Uploaded Sum') }}</span>
                                    <strong class="fs-5 text-success">{{ number_format($receiptsTotal) }} {{ __('Toman') }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-13 d-block">{{ __('Remaining Balance') }}</span>
                                    <strong class="fs-5 {{ $remainingBalance === 0 ? 'text-success' : 'text-danger' }}">{{ number_format($remainingBalance) }} {{ __('Toman') }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                @if($canConfirmPayment)
                                    <div class="card border border-primary-subtle shadow-sm rounded-3 p-3">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-primary">
                                            <i class="ri-shield-check-line fs-5"></i>
                                            {{ __('4-Point Payment Approval Safeguards') }}
                                        </h6>

                                        <form action="{{ route('admin.invoice.confirm-payment', $item) }}" method="post" id="approval-safeguard-form">
                                            @csrf

                                            <div class="mb-3">
                                                <label for="bank_account_id" class="form-label fs-13 fw-semibold text-dark">
                                                    {{ __('Destination bank account') }} <span class="text-danger">*</span>
                                                </label>
                                                <select name="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                                                    <option value="">{{ __('Select destination bank account') }}</option>
                                                    @foreach($activeBankAccounts as $bankAccount)
                                                        <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                            {{ $bankAccount->bank_name }} — {{ $bankAccount->card_number }} ({{ $bankAccount->account_holder_name }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('bank_account_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="checklist-items border-top pt-3 mb-3">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input approval-checklist-checkbox @error('receipt_info_checked') is-invalid @enderror" type="checkbox" name="receipt_info_checked" id="receipt_info_checked" value="1" {{ old('receipt_info_checked') ? 'checked' : '' }}>
                                                    <label class="form-check-label fs-13" for="receipt_info_checked">
                                                        {{ __('Receipt Info Checked') }}
                                                    </label>
                                                </div>

                                                <div class="form-check mb-2">
                                                    <input class="form-check-input approval-checklist-checkbox @error('account_selected') is-invalid @enderror" type="checkbox" name="account_selected" id="account_selected" value="1" {{ old('account_selected') ? 'checked' : '' }}>
                                                    <label class="form-check-label fs-13" for="account_selected">
                                                        {{ __('Account Selected') }}
                                                    </label>
                                                </div>

                                                <div class="form-check mb-2">
                                                    <input class="form-check-input approval-checklist-checkbox @error('bank_verified') is-invalid @enderror" type="checkbox" name="bank_verified" id="bank_verified" value="1" {{ old('bank_verified') ? 'checked' : '' }}>
                                                    <label class="form-check-label fs-13" for="bank_verified">
                                                        {{ __('Bank Verification') }}
                                                    </label>
                                                </div>

                                                <div class="form-check mb-2">
                                                    <input class="form-check-input approval-checklist-checkbox @error('zero_balance') is-invalid @enderror" type="checkbox" name="zero_balance" id="zero_balance" value="1" {{ old('zero_balance') ? 'checked' : '' }} {{ $remainingBalance > 0 ? 'disabled' : '' }}>
                                                    <label class="form-check-label fs-13" for="zero_balance">
                                                        {{ __('Zero Balance') }}
                                                        @if($remainingBalance > 0)
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-2">
                                                                {{ __('Remaining balance must be zero') }}
                                                            </span>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>

                                            <button type="submit" id="approve-payment-btn" class="btn btn-success w-100" disabled title="{{ __('Confirm payment') }}" aria-label="{{ __('Confirm payment') }}">
                                                <i class="ri-check-double-line me-1"></i> {{ __('Approve Payment') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <div class="col-lg-5">
                                @if($canReviewReceipt)
                                    <div class="card border border-warning-subtle shadow-sm rounded-3 p-3 mb-3">
                                        <h6 class="fw-bold mb-2 d-flex align-items-center gap-2 text-warning-emphasis">
                                            <i class="ri-refresh-line fs-5"></i>
                                            {{ __('Request Receipt Re-upload') }}
                                        </h6>
                                        <p class="fs-12 text-muted mb-3">
                                            {{ __('Keep invoice active, extend deadline by :hours hours, and ask customer for a new slip.', ['hours' => 3]) }}
                                        </p>
                                        <form action="{{ route('admin.invoice.request-receipt-reupload', $item) }}" method="post"
                                              data-confirm="{{ __('Request receipt re-upload from customer?') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="reupload_reason" class="form-label fs-13 text-muted">
                                                    {{ __('Reason for re-upload') }}
                                                </label>
                                                <input type="text" id="reupload_reason" name="reason" class="form-control"
                                                       placeholder="{{ __('e.g. Unreadable receipt image or incorrect amount') }}" required maxlength="255">
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 fw-bold">
                                                <i class="ri-refresh-line me-1"></i> {{ __('Request Re-upload') }}
                                            </button>
                                        </form>
                                    </div>

                                    <div class="card border border-danger-subtle shadow-sm rounded-3 p-3">
                                        <h6 class="fw-bold mb-2 d-flex align-items-center gap-2 text-danger">
                                            <i class="ri-close-circle-line fs-5"></i>
                                            {{ __('Decline and Cancel') }}
                                        </h6>
                                        <p class="fs-12 text-muted mb-3">
                                            {{ __('Permanently cancel the invoice and release reserved gold stock.') }}
                                        </p>
                                        <form action="{{ route('admin.invoice.decline-payment', $item) }}" method="post"
                                              data-confirm="{{ __('Are you sure you want to decline this payment and cancel the invoice?') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="decline_reason" class="form-label fs-13 text-muted">
                                                    {{ __('Decline reason (optional)') }}
                                                </label>
                                                <input type="text" id="decline_reason" name="reason" class="form-control"
                                                       placeholder="{{ __('Decline reason (optional)') }}" maxlength="255">
                                            </div>
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="ri-close-line me-1"></i> {{ __('Decline and cancel invoice') }}
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="alert alert-warning border border-warning-subtle shadow-sm rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="ri-information-line fs-5 text-warning flex-shrink-0"></i>
                                            <div>
                                                <strong class="d-block text-dark mb-1">{{ __('This receipt can no longer be reviewed here.') }}</strong>
                                                <span class="text-muted fs-13">
                                                    {{ __('The pending card payment for this invoice is no longer awaiting review, so approving, declining, or requesting a re-upload is disabled.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const bankSelect = document.getElementById('bank_account_id');
                                const check1 = document.getElementById('receipt_info_checked');
                                const check2 = document.getElementById('account_selected');
                                const check3 = document.getElementById('bank_verified');
                                const check4 = document.getElementById('zero_balance');
                                const approveBtn = document.getElementById('approve-payment-btn');

                                if (approveBtn) {
                                    function updateApprovalButton() {
                                        const hasBank = Boolean(bankSelect && bankSelect.value !== '');
                                        const c1 = Boolean(check1 && check1.checked);
                                        const c2 = Boolean(check2 && check2.checked);
                                        const c3 = Boolean(check3 && check3.checked);
                                        const c4 = Boolean(check4 && check4.checked && !check4.disabled);

                                        approveBtn.disabled = !(hasBank && c1 && c2 && c3 && c4);
                                    }

                                    if (bankSelect) {
                                        bankSelect.addEventListener('change', function () {
                                            if (check2 && bankSelect.value !== '') {
                                                check2.checked = true;
                                            }
                                            updateApprovalButton();
                                        });
                                    }

                                    [check1, check2, check3, check4].forEach(function (checkbox) {
                                        if (checkbox) {
                                            checkbox.addEventListener('change', updateApprovalButton);
                                        }
                                    });

                                    updateApprovalButton();
                                }

                                const preview = document.getElementById('receipt-preview-modal');
                                if (preview) {
                                    const img = document.getElementById('receipt-preview-image');
                                    const caption = document.getElementById('receipt-preview-caption');
                                    const openLink = document.getElementById('receipt-preview-open');

                                    document.querySelectorAll('.receipt-zoom-btn').forEach(function (btn) {
                                        btn.addEventListener('click', function () {
                                            const url = btn.getAttribute('data-receipt-preview');
                                            const name = btn.getAttribute('data-receipt-name') || '';
                                            const isImage = /\.(jpe?g|png|webp|gif|svg)(\?.*)?$/i.test(url);
                                            if (isImage && img) {
                                                img.src = url;
                                                img.classList.remove('d-none');
                                            } else if (img) {
                                                img.removeAttribute('src');
                                                img.classList.add('d-none');
                                            }
                                            if (caption) caption.textContent = name;
                                            if (openLink) openLink.href = url;
                                        });
                                    });
                                }
                            });
                        </script>

        <div class="modal fade" id="receipt-preview-modal" tabindex="-1" aria-hidden="true" aria-labelledby="receipt-preview-caption">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title fs-13 fw-bold" id="receipt-preview-caption"></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body text-center bg-light">
                        <img id="receipt-preview-image" src="" alt="" class="img-fluid rounded" style="max-height: 70vh;">
                        <p class="text-muted fs-12 mt-2 mb-0">
                            <a id="receipt-preview-open" href="#" target="_blank" rel="noopener noreferrer">{{ __('Open original file') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
