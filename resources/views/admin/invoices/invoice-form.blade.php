@extends('admin.templates.panel-form-template')
@section('title')
    @if(isset($item))
        {{__("Edit invoice")}} [{{$item->hash}}]
    @else
        {{__("Add new invoice")}}
    @endif -
@endsection
@section('out-of-form')
    @php
        $cardPayment = $item->cardPayment();
        // One precondition drives every receipt-review panel. Previously only the
        // approval form was gated, so an admin could be shown decline and
        // re-upload buttons that were guaranteed to fail server-side.
        $canReviewReceipt = $item->status === \App\Models\Invoice::AWAITING_PAYMENT
            && $cardPayment
            && $cardPayment->status === \App\Models\Payment::PENDING
            && $item->hasUploadedReceipt();
        $canConfirmPayment = $canReviewReceipt;
        $offlineHours = \App\Models\Invoice::offlinePaymentHours();
        $offlineIsExpired = $item->isOfflinePaymentExpired();
        $persianDeadline = $item->formattedDeadline();
        $displayStatus = $item->displayStatusKey();
        $declinedReason = $item->declinedReceiptReason();
        $reuploadReason = $item->reuploadRequestedReason();

        $customer = $item->customer;
        $successfulCount = $customer?->invoices()->whereIn('status', \App\Models\Invoice::successfulStatuses())->count() ?? 0;
        $waitingCount = $customer?->invoices()->whereIn('status', ['AWAITING_PAYMENT', 'PENDING'])->count() ?? 0;
        $failedCount = $customer?->invoices()->whereIn('status', ['CANCELED', 'FAILED'])->count() ?? 0;

        $isWaitingReceipt = $displayStatus === \App\Models\Invoice::WAITING_RECEIPT;
        $isWaitingConfirmation = $displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION;
        $isPickup = $item->isPickup();
        $isReadyForPickup = $displayStatus === \App\Models\Invoice::READY_FOR_PICKUP;
        $isShipping = in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING], true);
        $isPickupWorkflow = $isPickup && in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING, \App\Models\Invoice::OUT_FOR_DELIVERY, \App\Models\Invoice::READY_FOR_PICKUP], true);
        $isOutForDelivery = $displayStatus === \App\Models\Invoice::OUT_FOR_DELIVERY;
        $isCompleted = $displayStatus === \App\Models\Invoice::COMPLETED;
        $isClosed = in_array($displayStatus, [\App\Models\Invoice::FAILED, \App\Models\Invoice::CANCELED], true);

        $currentStep = 0;
        if ($isWaitingReceipt || $isWaitingConfirmation) {
            $currentStep = 2;
        } elseif ($isShipping) {
            $currentStep = 3;
        } elseif ($isReadyForPickup || $isOutForDelivery) {
            $currentStep = 4;
        } elseif ($isCompleted) {
            $currentStep = 5;
        }
        $steps = $isPickup
            ? [
                1 => __('Payment'),
                2 => __('Payment review'),
                3 => __('Preparing order'),
                4 => __('Ready for pickup'),
                5 => __('Collected'),
            ]
            : [
                1 => __('Payment'),
                2 => __('Payment review'),
                3 => __('Shipping'),
                4 => __('Order delivery'),
                5 => __('Completed'),
        ];

        $selectedTransport = $item->transport;
        $requiresCourier = $item->requiresDeliveryCode();
        $forceCourier = old('status') === \App\Models\Invoice::OUT_FOR_DELIVERY || $errors->has('courier_id');
        $showCourier = $requiresCourier || $forceCourier;
        $pickupLocation = (string) getSetting('address');
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
                        <a href="{{route('admin.customer.show',$customer->id)}}">
                            <span>{{__("Name")}}</span>
                            <b>{{$customer->name}}</b>
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{$customer->mobile}}" dir="ltr" title="{{ __('Call customer') }}">
                            <span>{{__("Mobile")}}</span>
                            <b>{{$customer->mobile}}</b>
                        </a>
                    </li>
                    @if($item->is_third_party)
                        <li>
                            <span>{{ __('Recipient (gift order)') }}</span>
                            <b>{{ $item->recipient_name ?: '—' }}</b>
                        </li>
                        <li>
                            <a href="tel:{{$item->recipient_mobile}}" dir="ltr" title="{{ __('Call recipient') }}">
                                <span>{{ __('Recipient mobile') }}</span>
                                <b>{{ $item->recipient_mobile ?: '—' }}</b>
                            </a>
                        </li>
                    @endif
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
                    @elseif($isReadyForPickup)
                        <li>{{ __('The order is ready for customer pickup. Mark it collected after the customer receives it.') }}</li>
                    @elseif($isPickup && in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING], true))
                        <li>{{ __('Prepare the order and mark it ready when the customer can collect it from the store.') }}</li>
                    @elseif($isOutForDelivery && $isPickup)
                        <li>{{ __('This pickup order must be marked ready before it can be collected.') }}</li>
                    @elseif($displayStatus === \App\Models\Invoice::PAID)
                        <li>{{__("Payment is confirmed. Choose shipping and send the order out for delivery.")}}</li>
                    @elseif($displayStatus === \App\Models\Invoice::PROCESSING)
                        <li>{{__("This order is being prepared. Assign a courier when it is ready.")}}</li>
                    @elseif($isOutForDelivery)
                        <li>{{__("The courier will ask the customer for the 4-digit code before handing over the gold.")}}</li>
                    @elseif($isCompleted)
                        <li>{{ $isPickup ? __('The customer collected this order from the store.') : __('This invoice was delivered and confirmed.') }}</li>
                    @elseif($isClosed)
                        <li>{{__("This invoice is closed and no further action is needed.")}}</li>
                    @endif
                    <li>{{__("Removing a paid item refunds its value to the customer credit.")}}</li>
                </ul>
            </div>

            @if(trim((string) $item->desc) !== '')
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
                            @elseif($isReadyForPickup)
                                {{ __('The order is ready for customer pickup at the store.') }}
                            @elseif($isPickup && in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING], true))
                                {{ __('Prepare the order and mark it ready when the customer can collect it from the store.') }}
                            @elseif($displayStatus === \App\Models\Invoice::PAID)
                                {{__("Payment is confirmed. Choose shipping and send the order out for delivery.")}}
                            @elseif($displayStatus === \App\Models\Invoice::PROCESSING)
                                {{__("This order is being prepared.")}}
                            @elseif($isOutForDelivery && $isPickup)
                                {{ __('This pickup order must be marked ready before it can be collected.') }}
                            @elseif($isOutForDelivery)
                                {{__("This order is out for motorcycle delivery. The customer received a confirmation code by SMS.")}}
                            @elseif($isCompleted)
                                {{ $isPickup ? __('The customer collected this order from the store.') : __('This invoice is completed.') }}
                            @elseif($displayStatus === \App\Models\Invoice::FAILED)
                                {{__("This invoice failed.")}}
                            @elseif($displayStatus === \App\Models\Invoice::CANCELED)
                                {{__("This invoice was canceled.")}}
                            @endif
                        </p>
                        <div class="d-flex align-items-center gap-2 mt-2" data-fulfillment-method="{{ $isPickup ? 'pickup' : 'delivery' }}">
                            <i class="{{ $isPickup ? 'ri-store-2-line' : 'ri-motorbike-line' }} text-primary"></i>
                            <span class="text-muted">{{ __('Fulfillment method:') }}</span>
                            <strong class="text-dark">{{ $isPickup ? __('Store pickup') : ($selectedTransport?->title ?? __('Delivery')) }}</strong>
                        </div>
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
                        <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end mb-2">
                            <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ __('Open the order board') }}">
                                <i class="ri-dashboard-2-line"></i>
                            </a>
                            @if(! $isPickup)
                                <a href="{{ route('admin.invoice.shipping-label', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ __('Shipping label') }}">
                                    <i class="ri-printer-line"></i>
                                </a>
                            @endif
                            @if($item->canPrint())
                                <a href="{{ route('admin.invoice.print', $item->hash) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ __('Print invoice') }}">
                                    <i class="ri-file-text-line"></i>
                                </a>
                            @endif
                        </div>
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
                        @php
                            // Steps 1 and 2 share one trigger: the customer paying.
                            // Before any money moves, nothing is complete -- and the
                            // current step is a "waiting on the customer" state rather
                            // than work for the admin. Once a receipt is on file,
                            // payment is done and the review step is current.
                            $isDone = match (true) {
                                $isWaitingReceipt => false,
                                $isWaitingConfirmation => $number < 2,
                                default => $number < $currentStep,
                            };
                            $isCurrent = $number === $currentStep;
                            $isWaiting = $isCurrent && ($isWaitingReceipt || $isWaitingConfirmation);
                            $stepClasses = array_filter([
                                'is-done' => $isDone,
                                'is-current' => $isCurrent,
                                'is-waiting' => $isWaiting,
                            ]);
                        @endphp
                        <li class="invoice-stepper__item {{ implode(' ', array_keys($stepClasses)) }}"
                            @if($isCurrent) aria-current="step" @endif>
                            <span class="invoice-stepper__dot">
                                @if($isDone)
                                    <i class="ri-check-line"></i>
                                @else
                                    {{ $number }}
                                @endif
                            </span>
                            <span class="invoice-stepper__label">{{ $label }}</span>
                            @if($isWaiting)
                                <span class="invoice-stepper__hint">{{ __('Waiting on customer') }}</span>
                            @endif
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

                        @if($reuploadReason)
                            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-refresh-line text-warning fs-3"></i>
                                    <div>
                                        <strong class="d-block text-dark">{{ __('A clearer receipt was requested from the customer.') }}</strong>
                                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $reuploadReason }}</span>
                                        @if($item->reuploadRequestedAt())
                                            <small class="text-muted fs-12 d-block">
                                                <i class="ri-time-line me-0.5"></i>{{ $item->reuploadRequestedAt()->jdate('Y/m/d H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($declinedReason)
                            <div class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-close-circle-line text-danger fs-3"></i>
                                    <div>
                                        <strong class="d-block text-dark">{{ __('A receipt was declined and needs to be uploaded again.') }}</strong>
                                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $declinedReason }}</span>
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
                                    {{-- The invoice says a receipt is waiting but the payment is no
                                         longer pending, so every review action would be rejected. --}}
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

                                // Receipt lightbox: the 48px thumbnail was the only
                                // way to inspect the evidence the four-point check
                                // asks the admin to verify.
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
            @endif

            {{-- Step 3: shipping / dispatch --}}
            @if($isPickupWorkflow)
                <div class="general-form item-list mb-3" data-fulfillment-method="pickup">
                    <div class="p-3">
                        <h4 class="mb-2"><i class="ri-store-2-line me-1"></i> {{ __('Store pickup') }}</h4>
                        @if($isReadyForPickup)
                            <p class="text-muted mb-3">{{ __('The order is ready for customer pickup at the store.') }}</p>
                        @else
                            <p class="text-muted mb-3">{{ __('Prepare the order and mark it ready when the customer can collect it from the store.') }}</p>
                        @endif

                        <div class="alert alert-info border border-info-subtle d-flex align-items-start gap-2 p-3 mb-3 rounded-3">
                            <i class="ri-map-pin-line text-primary fs-5"></i>
                            <div>
                                <strong class="d-block">{{ __('Pickup location') }}</strong>
                                <span>{{ $pickupLocation !== '' ? $pickupLocation : __('Gallery address is not configured.') }}</span>
                            </div>
                        </div>

                        @if($isReadyForPickup)
                            <div class="d-flex flex-wrap gap-2">
                                <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::COMPLETED }}">
                                    <button type="submit" class="btn btn-success fw-bold">
                                        <i class="ri-checkbox-circle-line me-1"></i>{{ __('Mark as collected') }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ \App\Models\Invoice::PROCESSING }}">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="ri-arrow-go-back-line me-1"></i>{{ __('Return to preparation') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('admin.invoice.update', $item) }}" method="post">
                                @csrf
                                <input type="hidden" name="status" value="{{ \App\Models\Invoice::READY_FOR_PICKUP }}">
                                <button type="submit" class="btn btn-primary fw-bold">
                                    <i class="ri-store-2-line me-1"></i>{{ __('Mark ready for pickup') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @elseif($isShipping)
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
                                         @forelse(($customer?->addresses ?? collect()) as $adr)
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
                                     <h5>{{ __('Shipping method') }}</h5>
                                     <div class="border rounded-3 bg-light p-3" data-fulfillment-method="delivery">
                                         <div class="d-flex align-items-center gap-2">
                                             <i class="{{ $requiresCourier ? 'ri-motorbike-line' : 'ri-truck-line' }} text-primary fs-5"></i>
                                             <strong>{{ $selectedTransport?->title ?? __('Delivery method unavailable') }}</strong>
                                         </div>
                                         @if($requiresCourier)
                                             <small class="text-muted d-block mt-1">{{ __('Needs delivery confirmation code') }}</small>
                                         @else
                                             {{-- Postal shipments are completed by the
                                                  post office, but an admin can still
                                                  hand the parcel to a courier. --}}
                                             <div class="form-check mt-2">
                                                 <input class="form-check-input" type="checkbox" id="use-courier-toggle"
                                                        @checked($requiresCourier) @disabled($requiresCourier)>
                                                 <label class="form-check-label fs-13" for="use-courier-toggle">
                                                     {{ __('Send with a motorcycle courier instead') }}
                                                 </label>
                                             </div>
                                         @endif
                                         <small class="text-muted d-block mt-2">{{ __('The fulfillment method selected at checkout cannot be changed here.') }}</small>
                                     </div>
                                 </div>
                                 <div class="col-md-6 mt-3 {{ $showCourier ? '' : 'd-none' }}" id="courier-assign">
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
                                        class="btn btn-warning fw-bold {{ $requiresCourier ? '' : 'd-none' }}"
                                        {{ $requiresCourier ? '' : 'data-needs-courier-toggle' }}>
                                    <i class="ri-motorbike-line"></i> {{ __('Send for delivery') }}
                                </button>
                                <button type="submit" name="status" value="{{ \App\Models\Invoice::COMPLETED }}"
                                        id="mark-completed-btn"
                                        class="btn btn-success {{ $requiresCourier ? 'd-none' : '' }}"
                                        {{ $requiresCourier ? 'data-needs-courier-toggle' : '' }}>
                                    <i class="ri-check-double-line"></i> {{ __('Mark as completed') }}
                                </button>
                            </div>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var toggle = document.getElementById('use-courier-toggle');
                                if (!toggle) return;

                                var courierField = document.getElementById('courier-assign');
                                var sendBtn = document.getElementById('send-for-delivery-btn');
                                var completeBtn = document.getElementById('mark-completed-btn');

                                function apply() {
                                    var useCourier = toggle.checked;
                                    if (courierField) courierField.classList.toggle('d-none', !useCourier);
                                    if (sendBtn) sendBtn.classList.toggle('d-none', !useCourier);
                                    if (completeBtn) completeBtn.classList.toggle('d-none', useCourier);
                                }

                                toggle.addEventListener('change', apply);
                                apply();
                            });
                        </script>
                    </div>
                </div>
            @endif

            {{-- Step 4: out for delivery --}}
            @if($isOutForDelivery && !$isPickup)
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
                        <h4 class="mb-2 text-success"><i class="ri-checkbox-circle-line me-1"></i> {{ $isPickup ? __('Collected') : __('Completed') }}</h4>
                        <p class="text-muted mb-1">{{ $isPickup ? __('The customer collected this order from the store. No further action is needed.') : __('This invoice was delivered and confirmed. No further action is needed.') }}</p>
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

@endsection
