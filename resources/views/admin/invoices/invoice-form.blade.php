@extends('admin.templates.panel-form-template')
@section('title')
    @if(isset($item))
        {{__("Edit invoice")}} [{{$item->hash}}]
    @else
        {{__("Add new invoice")}}
    @endif -
@endsection
@section('form')
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
        $successfulCount = $item->customer->invoices()->whereIn('status', ['PAID', 'PROCESSING', 'COMPLETED'])->count();
        $waitingCount = $item->customer->invoices()->whereIn('status', ['AWAITING_PAYMENT', 'PENDING'])->count();
        $failedCount = $item->customer->invoices()->whereIn('status', ['CANCELED', 'FAILED'])->count();
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
                    @if($displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION)
                        <li>{{__("A receipt is waiting. Confirm the payment if the transfer matches this invoice.")}}</li>
                    @elseif($displayStatus === \App\Models\Invoice::WAITING_RECEIPT)
                        <li>{{__("The customer still needs to pay by card-to-card and upload a receipt.")}}</li>
                    @else
                        <li>{{__("Update tracking when the order ships. Changing transport may change the total.")}}</li>
                    @endif
                    <li>{{__("Removing an item credits the customer automatically.")}}</li>
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
                            @if($displayStatus === \App\Models\Invoice::WAITING_CONFIRMATION)
                                {{__("A receipt is waiting for your confirmation.")}}
                            @elseif($displayStatus === \App\Models\Invoice::WAITING_RECEIPT && $offlineIsExpired)
                                {{__("Offline payment deadline passed")}}
                            @elseif($displayStatus === \App\Models\Invoice::WAITING_RECEIPT)
                                {{__("Waiting for the customer to pay and upload a receipt.")}}
                            @elseif($displayStatus === \App\Models\Invoice::PAID)
                                {{__("Payment is confirmed. Update tracking when the order ships.")}}
                            @elseif($displayStatus === \App\Models\Invoice::PROCESSING)
                                {{__("This order is being prepared.")}}
                            @elseif($displayStatus === \App\Models\Invoice::COMPLETED)
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
                        @if($canConfirmPayment)
                            <button type="submit" form="confirm-payment-form" class="btn btn-success">
                                <i class="ri-check-double-line"></i>
                                {{__("Confirm payment")}}
                            </button>
                        @endif
                        <div class="invoice-manage__total">
                            <span>{{__("Total price")}}</span>
                            <b>{{number_format($item->total_price)}} {{config('app.currency.symbol')}}</b>
                        </div>
                    </div>
                </div>
            </div>

            <div class="general-form item-list mb-3">
                <h3 class="p-3 pb-0">{{__("Payment receipts")}}</h3>
                <div class="px-3 pb-3">
                    @if($item->paymentReceipts->count())
                        <ul class="invoice-manage__receipts">
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
                    @else
                        <p class="text-muted mb-0">
                            @if($persianDeadline && $displayStatus === \App\Models\Invoice::WAITING_RECEIPT && ! $offlineIsExpired)
                                {{ __('No receipts yet. The customer still has time until :date.', ['date' => $persianDeadline]) }}
                            @else
                                {{__("No payment receipts uploaded yet.")}}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="general-form item-list mb-3">
                <h3 class="p-3 pb-0">{{__("Shipping and tracking")}}</h3>
                <div class="p-3 pt-0">
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
                            <div class="form-group">
                                <label for="status">{{__('Status')}}</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach(\App\Models\Invoice::editableStatuses() as $status)
                                        <option value="{{ $status }}" @selected(old('status', $item->status) === $status)>
                                            @if($status === \App\Models\Invoice::AWAITING_PAYMENT)
                                                {{ __('WAITING_RECEIPT') }}
                                            @else
                                                {{ __($status) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <h5>{{__("Delivery address")}}</h5>
                            <ul class="list-group">
                                @forelse($item->customer->addresses as $adr)
                                    <li class="list-group-item">
                                        <label class="mb-0 d-flex gap-2 align-items-start">
                                            <input type="radio" name="address_id" value="{{$adr->id}}"
                                                   @checked($adr->id == $item->address_id)/>
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
                                                   @checked($t->id == $item->transport_id)/>
                                            <span>{{$t->title}} ({{number_format($t->price)}})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary mt-3">{{__('Save')}}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item-list mb-3">
                <h3 class="p-3 pb-0">{{__("Invoice items")}}</h3>
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
@section('out-of-form')
    @if(isset($item) && $item->status === \App\Models\Invoice::AWAITING_PAYMENT && $item->hasUploadedReceipt())
        <form id="confirm-payment-form"
              action="{{ route('admin.invoice.confirm-payment', $item) }}"
              method="post"
              class="d-none"
              onsubmit="return confirm('{{__("Confirm this card-to-card payment?")}}');">
            @csrf
        </form>
    @endif
@endsection
