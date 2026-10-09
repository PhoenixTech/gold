<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between p-3 p-md-3.5 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-shopping-bag-3-line text-primary fs-18"></i>
            <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Invoice items') }}</h5>
        </div>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum fs-12 px-2.5 py-1">
            {{ __('Orders count') }}: {{ number_format($item->count ?: $item->orders->count()) }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 fs-13">
            <thead class="table-light text-muted fs-12 fw-semibold">
                <tr>
                    <th style="width: 2.5rem" class="text-center">#</th>
                    <th>{{ __('Product') }}</th>
                    <th style="width: 5rem" class="text-center">{{ __('Count') }}</th>
                    <th>{{ __('Specifications') }}</th>
                    <th class="text-end" style="width: 10rem">{{ __('Price') }}</th>
                    <th style="width: 3.5rem" class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($item->orders as $k => $order)
                    <tr>
                        <td class="text-center font-fanum text-muted">{{ $k + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark fs-13">{{ $order->product?->name }}</div>
                            @if($order->quantity?->code)
                                <span class="badge bg-light text-dark border font-monospace fs-11 mt-1">
                                    {{ $order->quantity->code }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center font-fanum text-dark">{{ number_format($order->count) }}</td>
                        <td>
                            @if(empty($order->quantity?->meta))
                                <span class="text-muted">—</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($order->quantity->meta as $m)
                                        <span class="badge bg-light text-dark border fs-11 fw-normal" title="{{ $m['label'] }}">
                                            <span class="text-muted">{{ $m['label'] }}:</span> {!! $m['human_value'] ?? '—' !!}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-end font-fanum fw-bold text-dark fs-13">
                            {{ number_format($order->price_total) }}
                            <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.invoice.remove-order', $order->id) }}" class="btn btn-sm btn-outline-danger border-0 p-1 delete-confirm" title="{{ __('Delete') }}">
                                <i class="ri-delete-bin-line fs-16"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light border-top">
                @if((int) $item->transport_price > 0)
                    <tr>
                        <td colspan="4" class="text-end text-muted fs-12">{{ __('Transport') }}</td>
                        <td class="text-end font-fanum fw-semibold text-dark fs-13">
                            {{ number_format($item->transport_price) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                        </td>
                        <td></td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4" class="text-end fw-bold text-dark fs-13">{{ __('Total price') }}</td>
                    <td class="text-end font-fanum fw-bold text-primary fs-14">
                        {{ number_format($item->total_price) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
