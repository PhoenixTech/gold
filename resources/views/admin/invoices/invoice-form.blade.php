@extends('admin.templates.panel-form-template')
@section('title')
    @if(isset($item))
        {{__("Edit invoice")}} [{{$item->id}}]
    @else
        {{__("Add new invoice")}}
    @endif -
@endsection
@section('form')

    <div class="row">
        <div class="col-lg-3">

            @include('components.err')
            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-message-3-line"></i>
                    {{__("Tips")}}
                </h5>
                <ul>
                    <li>
                        {{__("If you cancel this, You must increase credit yourself.")}}
                    </li>
                    <li>
                        {{__("If you change transport method you must think about think about the price diffrance")}}
                    </li>
                    <li>
                        {{__("If you removed order from invoice, system adding amount to customer's credit automatically")}}
                    </li>
                </ul>
            </div>
            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-user-line"></i>
                    {{__("Customer")}}
                </h5>
                <ul>
                    <li class="mb-2">
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            {{__("Name")}}: {{$item->customer->name}}
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            {{__("Mobile")}}: {{$item->customer->mobile}}
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            {{__("Successfully Invoices")}}
                            : {{number_format($item->customer->invoices()->whereIn('status',[ 'PAID', 'PROCESSING', 'COMPLETED'])->count())}}
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            {{__("Awaiting Payment")}}
                            : {{number_format($item->customer->invoices()->where('status', 'AWAITING_PAYMENT')->count())}}
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="{{route('admin.customer.show',$item->customer->id)}}">
                            {{__("Failed Invoices")}}
                            : {{number_format($item->customer->invoices()->whereIn('status',[ 'PENDING', 'CANCELED', 'FAILED'])->count())}}
                        </a>
                    </li>
                </ul>
            </div>

            @if( $item->desc != null && trim($item->desc) != '')
            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-message-line"></i>
                    {{__("Description")}}
                </h5>
                    <p class="px-4">
                        {{$item->desc}}
                    </p>
            </div>
            @endif

        </div>
        <div class="col-lg-9 ps-xl-1 ps-xxl-1">
            <div class="general-form ">

                <h3>
                    @if(isset($item))
                        {{__("Edit invoice")}} [{{$item->id}}]
                    @else
                        {{__("Add new invoice")}}
                    @endif
                </h3>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="tracking_code">
                                {{__('Tracking code')}}
                            </label>
                            <input name="tracking_code" type="text"
                                   class="form-control @error('tracking_code') is-invalid @enderror" id="tracking_code"
                                   placeholder="{{__('Tracking code')}}" value="{{old('tracking_code',$item->tracking_code??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="status">
                                {{__('Status')}}
                            </label>
                            <searchable-select
                                :items='{{arrayNormalizeVueCompatible(\App\Models\Invoice::$invoiceStatus, true)}}'
                                title-field="name"
                                value-field="name"
                                xname="status"
                                @error('status') :err="true" @enderror
                                xvalue='{{old('status',$item->status??null)}}'
                                :close-on-Select="true"></searchable-select>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <h5>
                            {{__("Address")}}
                        </h5>
                        <ul class="list-group">
                            @foreach($item->customer->addresses as $adr)
                                <li class="list-group-item">
                                    <label>
                                        <input type="radio" name="address_id" value="{{$adr->id}}"
                                               @if($adr->id == $item->address_id) checked @endif/>
                                        {{$adr->address}}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-12 mt-3">
                        <h5>
                            {{__("Address")}}
                        </h5>
                        <ul class="list-group">
                            @foreach(\App\Models\Transport::all() as $t)
                                <li class="list-group-item">
                                    <label>
                                        <input type="radio" name="transport_id" value="{{$t->id}}"
                                               @if($t->id == $item->transport_id) checked @endif/>
                                        {{$t->title}} ({{number_format($t->price)}})
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <label> &nbsp;</label>
                        <input name="" type="submit" class="btn btn-primary mt-2" value="{{__('Save')}}"/>
                    </div>

                </div>
            </div>

            @php
                $cardPayment = $item->payments->firstWhere('type', 'CARD');
                $canConfirmPayment = $item->status === \App\Models\Invoice::AWAITING_PAYMENT
                    && $cardPayment
                    && $cardPayment->status === \App\Models\Payment::PENDING;
                $offlineHours = \App\Models\Invoice::offlinePaymentHours();
                $offlineDeadline = $item->offlinePaymentDeadline();
                $offlineIsExpired = $item->isOfflinePaymentExpired();
            @endphp

            <div class="general-form mt-3">
                <h3>{{__("Payment receipts")}}</h3>
                @if($item->isOfflineCardPayment() && $item->status === \App\Models\Invoice::AWAITING_PAYMENT && $offlineDeadline)
                    <div class="alert {{ $offlineIsExpired ? 'alert-danger' : 'alert-warning' }} py-2">
                        <i class="ri-time-line"></i>
                        @if($offlineIsExpired)
                            <strong>{{ __('Offline payment deadline passed') }}</strong>
                            <span>{{ __('Customer had :hours hours to upload a receipt. Deadline was :date.', ['hours' => $offlineHours, 'date' => $offlineDeadline->format('Y-m-d H:i')]) }}</span>
                        @else
                            <strong>{{ __('Offline payment deadline') }}</strong>
                            <span>{{ __('Customer must upload a receipt within :hours hours of creating the invoice.', ['hours' => $offlineHours]) }}
                                {{ __('Deadline:') }} <b dir="ltr">{{ $offlineDeadline->format('Y-m-d H:i') }}</b></span>
                        @endif
                    </div>
                @endif
                @if($item->paymentReceipts->count())
                    <ul class="list-group mb-3">
                        @foreach($item->paymentReceipts as $receipt)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ $receipt->url() }}" target="_blank" rel="noopener">
                                        {{ $receipt->original_name }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $receipt->created_at }}
                                        @if($receipt->size)
                                            — {{ number_format($receipt->size / 1024, 1) }} KB
                                        @endif
                                    </div>
                                </div>
                                @if($receipt->isImage())
                                    <a href="{{ $receipt->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $receipt->url() }}" alt="{{ $receipt->original_name }}"
                                             style="max-height: 64px; max-width: 96px; object-fit: cover;">
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">{{__("No payment receipts uploaded yet.")}}</p>
                @endif

                @if($canConfirmPayment)
                    <form action="{{ route('admin.invoice.confirm-payment', $item) }}" method="post"
                          onsubmit="return confirm('{{__("Confirm this payment and mark the invoice as PAID?")}}');">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ri-check-double-line"></i>
                            {{__("Confirm payment")}}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <table class="table table-striped align-middle">
                <tr>
                    <th>
                        #
                    </th>
                    <th>
                        {{__("Product")}}
                    </th>
                    <th>
                        {{__("Count")}}
                    </th>
                    <th>
                        {{__("Quantity")}}
                    </th>
                    <th>
                        {{__("Price")}}
                    </th>
                    <th>
                        -
                    </th>
                </tr>
                @foreach($item->orders as $k => $order)
                    <tr>
                        <td>
                            {{$k + 1}}
                        </td>
                        <td>
                            {{$order->product->name}}
                        </td>
                        <td>
                            {{number_format($order->count)}}
                        </td>
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
                        <td>
                            {{number_format($order->price_total)}}
                        </td>
                        <td>
                            <a href="{{route('admin.invoice.remove-order',$order->id)}}" class="btn btn-danger delete-confirm">
                                <i class="ri-close-circle-line"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td>
                        -
                    </td>
                    <td>
                        {{__("Transport")}}
                        {{number_format($item->transport_price)}}
                    </td>
                    <td colspan="2">
                        {{__("Total price")}}
                        {{number_format($item->total_price)}}
                    </td>
                    <td colspan="2">
                        {{__("Orders count")}}: ({{number_format($item->count)}})
                    </td>
                </tr>

            </table>
        </div>
    </div>
@endsection
