@extends('admin.templates.panel-form-template')
@section('title')
    @if(isset($item))
        {{__("Edit invoice")}} [{{$item->hash}}]
    @else
        {{__("Add new invoice")}}
    @endif -
@endsection
@section('form')
@endsection
@section('out-of-form')
    @php
        $cardPayment = $item->cardPayment();
        $canConfirmPayment = $item->status === \App\Models\Invoice::AWAITING_PAYMENT
            && $cardPayment
            && $cardPayment->status === \App\Models\Payment::PENDING
            && $item->hasUploadedReceipt();
        $offlineHours = \App\Models\Invoice::offlinePaymentHours();
        $offlineIsExpired = $item->isOfflinePaymentExpired();
        $persianDeadline = $item->formattedDeadline();
        $displayStatus = $item->displayStatusKey();
        $declinedReason = $item->declinedReceiptReason();

        $successfulCount = $item->customer->invoices()->whereIn('status', \App\Models\Invoice::successfulStatuses())->count();
        $waitingCount = $item->customer->invoices()->whereIn('status', ['AWAITING_PAYMENT', 'PENDING'])->count();
        $failedCount = $item->customer->invoices()->whereIn('status', ['CANCELED', 'FAILED'])->count();

        $isWaitingReceipt = $displayStatus === \App\Models\Invoice::WAITING_RECEIPT;
        $isWaitingConfirmation = $displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION;
        $isShipping = in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING], true);
        $isOutForDelivery = $displayStatus === \App\Models\Invoice::OUT_FOR_DELIVERY;
        $isCompleted = $displayStatus === \App\Models\Invoice::COMPLETED;
        $isClosed = in_array($displayStatus, [\App\Models\Invoice::FAILED, \App\Models\Invoice::CANCELED], true);

        $currentStep = 0;
        if ($isWaitingReceipt) {
            $currentStep = 1;
        } elseif ($isWaitingConfirmation) {
            $currentStep = 2;
        } elseif ($isShipping) {
            $currentStep = 3;
        } elseif ($isOutForDelivery) {
            $currentStep = 4;
        } elseif ($isCompleted) {
            $currentStep = 5;
        }
        $steps = [
            1 => __('Payment'),
            2 => __('Payment review'),
            3 => __('Shipping'),
            4 => __('Order delivery'),
            5 => __('Completed'),
        ];

        $selectedTransportId = old('transport_id', $item->transport_id);
        $selectedTransport = \App\Models\Transport::query()->find($selectedTransportId);
        $requiresCourier = (bool) ($selectedTransport?->requires_delivery_code);
        $forceCourier = old('status') === \App\Models\Invoice::OUT_FOR_DELIVERY || $errors->has('courier_id');
        $showCourier = $requiresCourier || $forceCourier;
    @endphp

    <div class="invoice-manage row">
        <div class="col-lg-3">
            @include('components.err')

            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-user-line"></i>
                    {{__("Customer")}}
                </h5>
                <ul class="invoice-manage__customer">
                    <li>
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            <span>{{__("Name")}}</span>
                            <b>{{$item->customer->name}}</b>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            <span>{{__("Mobile")}}</span>
                            <b dir="ltr">{{$item->customer->mobile}}</b>
                        </a>
                    </li>
                    <li>
                        <span>{{__("Paid invoices")}}</span>
                        <b>{{number_format($successfulCount)}}</b>
                    </li>
                    <li>
                        <span>{{__("Waiting invoices")}}</span>
                        <b>{{number_format($waitingCount)}}</b>
                    </li>
                    <li>
                        <span>{{__("Failed invoices")}}</span>
                        <b>{{number_format($failedCount)}}</b>
                    </li>
                </ul>
            </div>

            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-lightbulb-line"></i>
                    {{__("What to do")}}
                </h5>
                <ul class="invoice-manage__tips">
                    @if($isWaitingReceipt)
                        <li>{{__("The customer still needs to pay by card-to-card and upload a receipt.")}}</li>
                    @elseif($isWaitingConfirmation)
                        <li>{{__("A receipt is waiting. Confirm or decline the payment.")}}</li>
                    @elseif($displayStatus === \App\Models\Invoice::PAID)
                        <li>{{__("Payment is confirmed. Choose shipping and send the order out for delivery.")}}</li>
                    @elseif($displayStatus === \App\Models\Invoice::PROCESSING)
                        <li>{{__("This order is being prepared. Assign a courier when it is ready.")}}</li>
                    @elseif($isOutForDelivery)
                        <li>{{__("The courier will ask the customer for the 4-digit code before handing over the gold.")}}</li>
                    @elseif($isCompleted)
                        <li>{{__("This invoice was delivered and confirmed.")}}</li>
                    @elseif($isClosed)
                        <li>{{__("This invoice is closed and no further action is needed.")}}</li>
                    @endif
                    <li>{{__("Removing a paid item refunds its value to the customer credit.")}}</li>
                </ul>
            </div>

            @if($item->desc != null && trim($item->desc) != '')
                <div class="item-list mb-3">
                    <h5 class="p-3">
                        <i class="ri-message-line"></i>
                        {{__("Description")}}
                    </h5>
                    <p class="px-4 pb-3 mb-0">{{$item->desc}}</p>
                </div>
            @endif
        </div>

        <div class="col-lg-9 ps-xl-1 ps-xxl-1">
            <div class="invoice-manage__now item-list mb-3">
                <div class="invoice-manage__now-main">
                    <div>
                        <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-12 mb-2 d-inline-block">
                            {{ __('Invoice') }} / #{{$item->hash}}
                        </code>
                        <h3 class="invoice-manage__title">
                            {{ $item->statusLabel() }}
                        </h3>
                        <p class="invoice-manage__lead mb-0">
                            @if($isWaitingConfirmation)
                                {{__("A receipt is waiting for your confirmation.")}}
                            @elseif($isWaitingReceipt && $offlineIsExpired)
                                {{__("Offline payment deadline passed")}}
                            @elseif($isWaitingReceipt)
                                {{__("Waiting for the customer to pay and upload a receipt.")}}
                            @elseif($displayStatus === \App\Models\Invoice::PAID)
                                {{__("Payment is confirmed. Choose shipping and send the order out for delivery.")}}
                            @elseif($displayStatus === \App\Models\Invoice::PROCESSING)
                                {{__("This order is being prepared.")}}
                            @elseif($isOutForDelivery)
                                {{__("This order is out for motorcycle delivery. The customer received a confirmation code by SMS.")}}
                            @elseif($isCompleted)
                                {{__("This invoice is completed.")}}
                            @elseif($displayStatus === \App\Models\Invoice::FAILED)
                                {{__("This invoice failed.")}}
                            @elseif($displayStatus === \App\Models\Invoice::CANCELED)
                                {{__("This invoice was canceled.")}}
                            @endif
                        </p>
                        @if($item->isOfflineCardPayment() && $persianDeadline && in_array($displayStatus, [\App\Models\Invoice::WAITING_RECEIPT, \App\Models\Invoice::WAITING_CONFIRMATION], true))
                            <p class="invoice-manage__deadline mb-0">
                                <i class="ri-time-line"></i>
                                @if($offlineIsExpired)
                                    {{ __('Customer had :hours hours to upload a receipt. Deadline was :date.', ['hours' => $offlineHours, 'date' => $persianDeadline]) }}
                                @else
                                    {{ __('Deadline:') }}
                                    <b>{{ $persianDeadline }}</b>
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="invoice-manage__now-actions">
                        <span class="{{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span>
                        <div class="invoice-manage__total text-end">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11 mb-2"><i class="ri-lock-line me-1"></i>{{ __('Auto-calculated') }}</span>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-end-0">{{__("Total price")}}</span>
                                <input type="text" class="form-control bg-light text-dark fw-bold border-start-0" readonly value="{{number_format($item->total_price)}}">
                                <span class="input-group-text">{{config('app.currency.symbol')}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @unless($isClosed)
                <ol class="invoice-stepper list-unstyled mb-3">
                    @foreach($steps as $number => $label)
                        <li class="invoice-stepper__item {{ $number < $currentStep ? 'is-done' : '' }} {{ $number === $currentStep ? 'is-current' : '' }}">
                            <span class="invoice-stepper__dot">
                                @if($number < $currentStep)
                                    <i class="ri-check-line"></i>
                                @else
                                    {{ $number }}
                                @endif
                            </span>
                            <span class="invoice-stepper__label">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            @endunless

            {{-- Step 1: waiting for the customer to pay and upload a receipt --}}
            @if($isWaitingReceipt)
                <div class="item-list mb-3">
                    <div class="p-3">
                        <h4 class="mb-2"><i class="ri-bank-card-line me-1"></i> {{ __('Payment') }}</h4>
                        <p class="text-muted mb-1">{{ __('Waiting for the customer to pay by card-to-card and upload a receipt.') }}</p>
                        <small class="text-muted d-flex align-items-center gap-1 mb-3">
                            <i class="ri-information-line text-primary"></i>
                            {{ __('Customer currently sees a countdown timer on their invoice page and a request to upload receipt. A background task (offline:expire) will automatically fail this order if the deadline passes.') }}
                        </small>

                        @if($declinedReason)
                            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-arrow-go-back-line text-warning fs-3"></i>
                                    <div>
                                        <strong class="d-block text-dark">{{ __('A receipt was declined and needs to be uploaded again.') }}</strong>
                                        <span class="text-muted fs-13">{{ $declinedReason }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <form action="{{ route('admin.invoice.update', $item) }}" method="post" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ \App\Models\Invoice::CANCELED }}">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="ri-close-circle-line"></i> {{ __('Cancel invoice') }}
                                </button>
                            </form>
                            <form action="{{ route('admin.invoice.update', $item) }}" method="post" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ \App\Models\Invoice::FAILED }}">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="ri-error-warning-line"></i> {{ __('Mark failed') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Step 2: review the uploaded receipt --}}
            @if($isWaitingConfirmation)
                <div class="item-list mb-3">
                    <div class="p-3">
                        <h4 class="mb-2"><i class="ri-file-list-3-line me-1"></i> {{ __('Payment review') }}</h4>
                        <p class="text-muted mb-1">{{ __('Review the uploaded receipt and confirm or decline the payment.') }}</p>
                        <small class="text-muted d-flex align-items-center gap-1 mb-3">
                            <i class="ri-information-line text-primary"></i>
                            {{ __('Customer currently sees "Payment receipt is under review". The countdown timer is paused.') }}
                        </small>

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-hover align-middle mb-0 fs-13">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">{{ __('Preview') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Payment Date & Time') }}</th>
                                        <th>{{ __('Tracking Number') }}</th>
                                        <th>{{ __('File') }}</th>
                                        <th class="text-center" style="width: 90px;">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->paymentReceipts as $receipt)
                                        <tr>
                                            <td class="text-center p-1">
                                                <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="d-inline-block">
                                                    @if($receipt->isImage())
                                                        <img src="{{ $receipt->url() }}" alt="{{ $receipt->original_name }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                            <i class="ri-file-pdf-2-line fs-4 text-danger"></i>
                                                        </div>
                                                    @endif
                                                </a>
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
                                                <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1 fs-12">
                                                    <i class="ri-external-link-line"></i>
                                                    <span>{{ __('View') }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
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
                                <div class="card border border-danger-subtle shadow-sm rounded-3 p-3">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-danger">
                                        <i class="ri-close-circle-line fs-5"></i>
                                        {{ __('Decline payment') }}
                                    </h6>
                                    <form action="{{ route('admin.invoice.decline-payment', $item) }}" method="post"
                                          onsubmit="return confirm('{{__("Are you sure you want to decline this payment and cancel the invoice?")}}');">
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

                                if (!approveBtn) {
                                    return;
                                }

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
                            });
                        </script>
                    </div>
                </div>
            @endif

            {{-- Step 3: shipping / dispatch --}}
            @if($isShipping)
                <div class="general-form item-list mb-3">
                    <h4 class="p-3 pb-0"><i class="ri-truck-line me-1"></i> {{ __('Shipping') }}</h4>
                    <div class="px-3 pb-2 pt-2">
                        <small class="text-muted d-flex align-items-center gap-1">
                            <i class="ri-information-line text-primary"></i>
                            {{ __('Customer sees their order is PAID or PROCESSING. The offline payment box is completely hidden.') }}
                        </small>
                    </div>
                    <div class="p-3 pt-0">
                        <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <div class="form-group">
                                        <label for="tracking_code">{{__('Tracking code')}}</label>
                                        <input name="tracking_code" type="text"
                                               class="form-control @error('tracking_code') is-invalid @enderror" id="tracking_code"
                                               placeholder="{{__('Tracking code')}}" value="{{old('tracking_code',$item->tracking_code??null)}}"/>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <h5>{{__("Delivery address")}}</h5>
                                    <ul class="list-group">
                                        @forelse($item->customer->addresses as $adr)
                                            <li class="list-group-item">
                                                <label class="mb-0 d-flex gap-2 align-items-start">
                                                    <input type="radio" name="address_id" value="{{$adr->id}}"
                                                           @checked($adr->id == old('address_id', $item->address_id))/>
                                                    <span>{{$adr->address}}</span>
                                                </label>
                                            </li>
                                        @empty
                                            <li class="list-group-item text-muted">{{__("No address registered.")}}</li>
                                        @endforelse
                                    </ul>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <h5>{{__("Shipping method")}}</h5>
                                    <ul class="list-group">
                                        @foreach(\App\Models\Transport::all() as $t)
                                            <li class="list-group-item">
                                                <label class="mb-0 d-flex gap-2 align-items-start">
                                                    <input type="radio" name="transport_id" value="{{$t->id}}"
                                                           data-requires-code="{{ $t->requires_delivery_code ? 1 : 0 }}"
                                                           @checked($t->id == old('transport_id', $item->transport_id))/>
                                                    <span>
                                                        {{$t->title}}
                                                        @if($t->requires_delivery_code)
                                                            <small class="text-muted d-block">{{ __('Needs delivery confirmation code') }}</small>
                                                        @endif
                                                    </span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="col-md-6 mt-3 {{ $showCourier ? '' : 'd-none' }}" id="courier-assign"
                                     data-force="{{ $forceCourier ? 1 : 0 }}">
                                    <div class="form-group">
                                        <label for="courier_id">{{ __('Courier') }}</label>
                                        <select name="courier_id" id="courier_id" class="form-select @error('courier_id') is-invalid @enderror">
                                            <option value="">{{ __('Select a courier') }}</option>
                                            @foreach(($couriers ?? collect()) as $courier)
                                                <option value="{{ $courier->id }}" @selected((string) old('courier_id', $item->activeDelivery?->courier_id) === (string) $courier->id)>
                                                    {{ $courier->name }}
                                                    @if($courier->mobile)
                                                        — {{ $courier->mobile }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">{{ __('Required when sending by motorcycle courier. A 4-digit code is SMS’d to the customer.') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="submit" name="status" value="{{ $item->status }}" class="btn btn-primary">
                                    <i class="ri-save-line"></i> {{ __('Save shipment details') }}
                                </button>
                                <button type="submit" name="status" value="{{ \App\Models\Invoice::OUT_FOR_DELIVERY }}"
                                        id="send-for-delivery-btn"
                                        class="btn btn-warning fw-bold {{ $requiresCourier ? '' : 'd-none' }}">
                                    <i class="ri-motorbike-line"></i> {{ __('Send for delivery') }}
                                </button>
                                <button type="submit" name="status" value="{{ \App\Models\Invoice::COMPLETED }}"
                                        id="mark-completed-btn"
                                        class="btn btn-success {{ $requiresCourier ? 'd-none' : '' }}">
                                    <i class="ri-check-double-line"></i> {{ __('Mark as completed') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Step 4: out for delivery --}}
            @if($isOutForDelivery)
                <div class="general-form item-list mb-3">
                    <div class="p-3">
                        <h4 class="mb-2"><i class="ri-motorbike-line me-1"></i> {{ __('Order delivery') }}</h4>
                        <p class="text-muted mb-1">{{ __('The courier will ask the customer for the 4-digit code before handing over the gold.') }}</p>
                        <small class="text-muted d-flex align-items-center gap-1 mb-3">
                            <i class="ri-information-line text-primary"></i>
                            {{ __('Customer was notified by SMS. They see a banner instructing them to give the 4-digit code to the courier.') }}
                        </small>

                        @if($item->activeDelivery)
                            <div class="p-3 border rounded bg-light mb-3">
                                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $item->activeDelivery->courier?->name }}
                                            <span class="ms-1 {{ $item->activeDelivery->status->badgeClass() }}">{{ $item->activeDelivery->status->label() }}</span>
                                        </div>
                                        <div class="text-muted fs-xs">
                                            @if($item->activeDelivery->sms_sent_at)
                                                <span class="me-2">{{ __('SMS sent') }} {{ \App\Models\Invoice::formatPersianDateTime($item->activeDelivery->sms_sent_at) }}</span>
                                            @endif
                                            @if($item->activeDelivery->failed_attempts)
                                                <span>{{ __('Failed attempts') }}: {{ $item->activeDelivery->failed_attempts }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <form action="{{ route('admin.invoice.resend-delivery-code', $item) }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="ri-send-plane-line"></i> {{ __('Resend delivery code') }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ \App\Models\Invoice::OUT_FOR_DELIVERY }}">
                                        <label class="form-label">{{ __('Reassign to another courier') }}</label>
                                        <div class="input-group">
                                            <select name="courier_id" class="form-select" required>
                                                <option value="">{{ __('Select a courier') }}</option>
                                                @foreach(($couriers ?? collect()) as $courier)
                                                    <option value="{{ $courier->id }}" @selected($item->activeDelivery->courier_id === $courier->id)>
                                                        {{ $courier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary">{{ __('Reassign') }}</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                                        <label class="form-label">{{ __('Cancel the delivery') }}</label>
                                        <button type="submit" class="btn btn-outline-secondary w-100">
                                            <i class="ri-arrow-go-back-line"></i> {{ __('Return to preparation') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-0 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-alarm-warning-fill text-warning fs-3"></i>
                                    <div>
                                        <strong class="d-block text-dark">{{ __('No active courier delivery.') }}</strong>
                                        <span class="text-muted fs-13">{{ __('The delivery may have been rejected or cancelled.') }}</span>
                                    </div>
                                </div>
                                <form action="{{ route('admin.invoice.update', $item) }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                                    <button type="submit" class="btn btn-sm btn-warning fw-bold px-3">
                                        <i class="ri-arrow-go-back-line me-1"></i>{{ __('Return to preparation') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Step 5: completed --}}
            @if($isCompleted)
                <div class="item-list mb-3">
                    <div class="p-3">
                        <h4 class="mb-2 text-success"><i class="ri-checkbox-circle-line me-1"></i> {{ __('Completed') }}</h4>
                        <p class="text-muted mb-1">{{ __('This invoice was delivered and confirmed. No further action is needed.') }}</p>
                        <small class="text-muted d-flex align-items-center gap-1 mb-0">
                            <i class="ri-information-line text-primary"></i>
                            {{ __('Order has moved to Previous Orders in the customer dashboard. They can now print their invoice.') }}
                        </small>
                    </div>
                </div>
            @endif

            {{-- Closed: failed or canceled --}}
            @if($isClosed)
                <div class="item-list mb-3">
                    <div class="p-3">
                        <h4 class="mb-2 {{ $displayStatus === \App\Models\Invoice::FAILED ? 'text-danger' : 'text-secondary' }}">
                            <i class="ri-close-circle-line me-1"></i>
                            {{ $displayStatus === \App\Models\Invoice::FAILED ? __('Failed') : __('Canceled') }}
                        </h4>
                        <p class="text-muted mb-0">{{ __('This invoice is closed and no further action is needed.') }}</p>
                        @if($declinedReason)
                            <div class="alert alert-danger-subtle border border-danger-subtle text-danger rounded-3 p-2.5 mt-3 mb-0 fs-13">
                                <strong>{{ __('Decline reason:') }}</strong> {{ $declinedReason }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="item-list mb-3">
                <h4 class="p-3 pb-0">{{__("Invoice items")}}</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <tr>
                            <th>#</th>
                            <th>{{__("Product")}}</th>
                            <th>{{__("Count")}}</th>
                            <th>{{__("Quantity")}}</th>
                            <th>{{__("Price")}}</th>
                            <th></th>
                        </tr>
                        @foreach($item->orders as $k => $order)
                            <tr>
                                <td>{{$k + 1}}</td>
                                <td>{{$order->product->name}}</td>
                                <td>{{number_format($order->count)}}</td>
                                <td>
                                    @if( ($order->quantity->meta??null) == null)
                                        -
                                    @else
                                        @foreach($order->quantity->meta as $m)
                                            <div title="{{$m['label']}}" class="float-start p-2">
                                                {{$m['label']}}:
                                                {!! $m['human_value']??'-' !!}
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td>{{number_format($order->price_total)}}</td>
                                <td>
                                    <a href="{{route('admin.invoice.remove-order',$order->id)}}" class="btn btn-danger delete-confirm">
                                        <i class="ri-close-circle-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2">{{__("Transport")}} {{number_format($item->transport_price)}}</td>
                            <td colspan="2">{{__("Total price")}} {{number_format($item->total_price)}}</td>
                            <td colspan="2">{{__("Orders count")}}: ({{number_format($item->count)}})</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var box = document.getElementById('courier-assign');
            if (!box) return;
            var sendBtn = document.getElementById('send-for-delivery-btn');
            var completeBtn = document.getElementById('mark-completed-btn');
            var forced = box.getAttribute('data-force') === '1';
            var radios = document.querySelectorAll('input[name="transport_id"]');
            var sync = function () {
                var selected = document.querySelector('input[name="transport_id"]:checked');
                var needs = selected && selected.getAttribute('data-requires-code') === '1';
                box.classList.toggle('d-none', !needs);
                if (sendBtn) sendBtn.classList.toggle('d-none', !needs);
                if (completeBtn) completeBtn.classList.toggle('d-none', needs);
            };
            radios.forEach(function (radio) {
                radio.addEventListener('change', sync);
            });
            if (!forced) sync();
        })();
    </script>
@endsection
