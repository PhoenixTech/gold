@extends('layouts.app')
@section('title')
    {{ __('Edit invoice') }} [{{ $item->hash }}] -
@endsection
@section('content')
    @php
        $workflow = app(\App\Services\InvoiceWorkflow::class);
        $steps = $workflow->steps($item);
        $currentStep = $workflow->currentStep($item);

        $displayStatus = $item->displayStatusKey();
        $isPickup = $item->isPickup();
        $customer = $item->customer;

        $cardPayment = $item->cardPayment();
        $canReviewReceipt = $item->status === \App\Models\Invoice::AWAITING_PAYMENT
            && $cardPayment
            && $cardPayment->status === \App\Models\Payment::PENDING
            && $item->hasUploadedReceipt();
        $canConfirmPayment = $canReviewReceipt;

        $isWaitingReceipt = $displayStatus === \App\Models\Invoice::WAITING_RECEIPT;
        $isWaitingConfirmation = $displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION;
        $isPrepare = in_array($displayStatus, [\App\Models\Invoice::PAID, \App\Models\Invoice::PROCESSING], true);
        $isReadyForPickup = $displayStatus === \App\Models\Invoice::READY_FOR_PICKUP;
        $isOutForDelivery = $displayStatus === \App\Models\Invoice::OUT_FOR_DELIVERY;
        $isCompleted = $displayStatus === \App\Models\Invoice::COMPLETED;
        $isClosed = in_array($displayStatus, [\App\Models\Invoice::FAILED, \App\Models\Invoice::CANCELED], true);

        $offlineIsExpired = $item->isOfflinePaymentExpired();
        $hasPendingCardPayment = $cardPayment?->status === \App\Models\Payment::PENDING;
        $persianDeadline = $hasPendingCardPayment ? $item->formattedDeadline() : null;
        $isShopSale = $item->isManual();
        // The admin's turn unless the customer or the courier has the next move.
        $isMyTurn = ! (($isWaitingReceipt && ! $isShopSale) || $isOutForDelivery);
        $declinedReason = $item->declinedReceiptReason();
        $reuploadReason = $item->reuploadRequestedReason();
        $pickupLocation = (string) getSetting('address');
        $canCancel = in_array($item->status, \App\Services\InvoiceWorkflow::cancelableStatuses(), true);
        $refundAmount = $item->refundableAmount();

        $nowTitle = match (true) {
            $isWaitingReceipt && $isShopSale => __('Record the payment'),
            $isWaitingReceipt => __('Waiting for the customer'),
            $isWaitingConfirmation => __('Review the receipt'),
            $isPrepare && $isPickup => __('Prepare the order for pickup'),
            $isPrepare => __('Prepare the order and send it with a courier'),
            $isReadyForPickup => __('Hand the order to the customer'),
            $isOutForDelivery => __('Courier is delivering the order'),
            $isCompleted => __('Order completed'),
            default => __('Invoice closed'),
        };
        $nowHelp = match (true) {
            $isWaitingReceipt && $isShopSale => __('Record each payment the customer makes in the shop.'),
            $isWaitingReceipt => __('The customer must pay by card-to-card and upload a receipt. You do not need to do anything.'),
            $isWaitingConfirmation => __('Open the receipt, compare the amount and account, then confirm or send it back.'),
            $isPrepare && $isPickup => __('Pack the order. Press the button when the customer can collect it from the store.'),
            $isPrepare => __('Pack the order, choose a courier and send it. A 4-digit code is sent to the customer by SMS.'),
            $isReadyForPickup => __('Press the button after the customer has received the order in the store.'),
            $isOutForDelivery => __('The courier enters the customer code to finish the delivery. You can resend the code or change the courier.'),
            $isCompleted => __('No further action is needed.'),
            default => __('No further action is needed.'),
        };
    @endphp

    <div class="invoice-manage pb-5">
        @include('components.err')
        @include('admin.invoices.edit.header')
        @unless($isClosed)
            @include('admin.invoices.edit.stepper')
        @endunless

        <div class="row g-3">
            <div class="col-lg-8">
                @unless($isClosed)
                    <div class="alert {{ $isMyTurn ? 'alert-primary border-primary-subtle' : 'alert-light' }} border shadow-sm d-flex align-items-start gap-3 p-3 mb-4 rounded-3" data-current-step="{{ $currentStep }}">
                        <i class="{{ $isMyTurn ? 'ri-arrow-left-circle-fill text-primary' : 'ri-time-line text-secondary' }} fs-3"></i>
                        <div>
                            <div class="fs-12 text-muted fw-semibold">{{ $isMyTurn ? __('Your turn') : __('Waiting') }}</div>
                            <strong class="d-block fs-16 text-dark mt-0.5">{{ $nowTitle }}</strong>
                            <span class="fs-13 text-muted">{{ $nowHelp }}</span>
                        </div>
                    </div>
                @endunless

                @if($isWaitingReceipt)
                    @include('admin.invoices.edit.step-payment')
                @elseif($isWaitingConfirmation)
                    @include('admin.invoices.edit.step-review')
                @elseif($isPrepare)
                    @include('admin.invoices.edit.step-prepare')
                @elseif($isReadyForPickup || $isOutForDelivery)
                    @include('admin.invoices.edit.step-handover')
                @elseif($isCompleted)
                    @include('admin.invoices.edit.completed')
                @elseif($isClosed)
                    @include('admin.invoices.edit.closed')
                @endif

                @include('admin.invoices.edit.items')
            </div>
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 1rem; z-index: 10;">
                    @include('admin.invoices.edit.side')
                </div>
            </div>
        </div>
    </div>

    @if($canCancel)
        @include('admin.invoices.edit.cancel-modal')
    @endif
@endsection
