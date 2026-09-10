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
                        <span class="invoice-manage__eyebrow">{{__("Invoice")}} {{$item->hash}}</span>
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
                        <div class="invoice-manage__total">
                            <span>{{__("Total price")}}</span>
                            <b>{{number_format($item->total_price)}} {{config('app.currency.symbol')}}</b>
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
                        <p class="text-muted mb-3">{{ __('Waiting for the customer to pay by card-to-card and upload a receipt.') }}</p>

                        @if($declinedReason)
                            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center gap-2 p-2 rounded-3">
                                <i class="ri-arrow-go-back-line fs-4"></i>
                                <div>
                                    <strong class="d-block">{{ __('A receipt was declined and needs to be uploaded again.') }}</strong>
                                    <span class="text-muted fs-13">{{ $declinedReason }}</span>
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
                        <p class="text-muted">{{ __('Review the uploaded receipt and confirm or decline the payment.') }}</p>

                        <ul class="invoice-manage__receipts mb-3">
                            @foreach($item->paymentReceipts as $receipt)
                                <li>
                                    <a href="{{ $receipt->url() }}" target="_blank" rel="noopener" class="invoice-manage__receipt">
                                        @if($receipt->isImage())
                                            <img src="{{ $receipt->url() }}" alt="{{ $receipt->original_name }}">
                                        @else
                                            <i class="ri-file-pdf-2-line"></i>
                                        @endif
                                        <span>
                                            {{ $receipt->original_name }}
                                            <small>
                                                {{ \App\Models\Invoice::formatPersianDateTime($receipt->created_at) }}
                                                @if($receipt->size)
                                                    — {{ number_format($receipt->size / 1024, 1) }} KB
                                                @endif
                                            </small>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="row g-2 align-items-start">
                            <div class="col-lg-6">
                                @if($canConfirmPayment)
                                    <form action="{{ route('admin.invoice.confirm-payment', $item) }}" method="post"
                                          onsubmit="return confirm('{{__("Confirm this card-to-card payment?")}}');">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="ri-check-double-line"></i> {{ __('Confirm payment') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="col-lg-6">
                                <form action="{{ route('admin.invoice.decline-payment', $item) }}" method="post"
                                      onsubmit="return confirm('{{__("Decline this receipt? The customer will need to upload a new one.")}}');">
                                    @csrf
                                    <div class="input-group">
                                        <input type="text" name="reason" class="form-control"
                                               placeholder="{{ __('Decline reason (optional)') }}" maxlength="255">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="ri-close-line"></i> {{ __('Decline payment') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Step 3: shipping / dispatch --}}
            @if($isShipping)
                <div class="general-form item-list mb-3">
                    <h4 class="p-3 pb-0"><i class="ri-truck-line me-1"></i> {{ __('Shipping') }}</h4>
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
                        <p class="text-muted">{{ __('The courier will ask the customer for the 4-digit code before handing over the gold.') }}</p>

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
                        <p class="text-muted mb-0">{{ __('This invoice was delivered and confirmed. No further action is needed.') }}</p>
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
