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
