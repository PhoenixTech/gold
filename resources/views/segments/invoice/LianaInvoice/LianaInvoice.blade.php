<section class='LianaInvoice live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="p-3">
        <div class="liana-card">

            <div class="liana-head">
                <div class="liana-brand">
                    <img src="{{asset('upload/images/logo.png')}}" class="liana-logo" alt="">
                    <div class="liana-brand-meta">
                        <h3>{{config('app.name')}}</h3>
                        <span class="inv-badge inv-{{$invoice->status}}">{{__($invoice->status)}}</span>
                    </div>
                </div>
                <div class="liana-qr">
                    <img src="{{$qr->render(route('client.invoice',$invoice->hash))}}" alt="qr code"
                         class="qr-code">
                </div>
            </div>

            <div class="liana-meta">
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-calendar-line"></i> {{__("Date")}}</span>
                    <b>{{$invoice->created_at->ldate('Y-m-d')}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-file-list-3-line"></i> {{__("ID")}}</span>
                    <b>{{$invoice->hash}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-user-3-line"></i> {{__("Customer")}}</span>
                    <b>{{$invoice->customer->name}}</b>
                </div>
                <div class="liana-meta-item">
                    <span class="liana-meta-label"><i class="ri-phone-line"></i> {{__("Customer mobile")}}</span>
                    <b dir="ltr">{{$invoice->customer->mobile}}</b>
                </div>
            </div>

            <div class="liana-table-wrap">
                <table class="liana-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__("Product")}}</th>
                            <th>{{__("Count")}}</th>
                            <th>{{__("Quantity")}}</th>
                            <th class="text-end">{{__("Price")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->orders as $k => $order)
                            <tr>
                                <td data-label="#">{{$k + 1}}</td>
                                <td data-label="{{__('Product')}}">{{$order->product->name}}</td>
                                <td data-label="{{__('Count')}}">{{number_format($order->count)}}</td>
                                <td data-label="{{__('Quantity')}}">
                                    @if( ($order->quantity->meta??null) == null)
                                        -
                                    @else
                                        @foreach($order->quantity->meta as $m)
                                            <span>{!! $m['human_value']??'-' !!}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td data-label="{{__('Price')}}" class="liana-price">
                                    {{number_format($order->price_total)}} {{config('app.currency.symbol')}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="liana-summary">
                <div class="liana-summary-item">
                    <span><i class="ri-truck-line"></i> {{__("Transport")}}</span>
                    <b>{{number_format($invoice->transport_price)}} {{config('app.currency.symbol')}}</b>
                </div>
                <div class="liana-summary-item">
                    <span><i class="ri-shopping-bag-4-line"></i> {{__("Orders count")}}</span>
                    <b>{{number_format($invoice->count)}}</b>
                </div>
                <div class="liana-summary-item liana-summary-total">
                    <span><i class="ri-bank-card-2-line"></i> {{__("Total price")}}</span>
                    <b>{{number_format($invoice->total_price)}} {{config('app.currency.symbol')}}</b>
                </div>
            </div>

            <div class="liana-footer">
                <p class="liana-desc">
                    {{$invoice->desc}}
                </p>
                <div class="liana-address">
                    <i class="ri-map-pin-2-line"></i>
                    {{__("Address")}}:
                    {{$invoice->address->state->name}}, {{$invoice->address->city->name}}, {{$invoice->address->address}}
                    , {{$invoice->address->zip}}
                </div>
                @if(trim(getSetting($data->area_name.'_'.$data->part.'_desc')) != '')
                    <hr>
                    <div class="liana-dyn">
                        {!! getSetting($data->area_name.'_'.$data->part.'_desc') !!}
                    </div>
                @endif
            </div>

            <div class="no-print liana-print-btn" onclick="window.print()">
                <i class="ri-printer-line"></i>
                {{__("Print")}}
            </div>
        </div>
    </div>
</section>
