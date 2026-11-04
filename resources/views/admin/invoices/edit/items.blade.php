@php $canRemoveItems = ! $isClosed && $item->orders->count() > 1; @endphp
<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 p-3 p-md-3.5 border-bottom">
        <i class="ri-shopping-bag-3-line text-primary fs-18"></i>
        <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Invoice items') }}</h5>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum fs-12">{{ number_format($item->orders->count()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 fs-13">
            <tbody>
                @foreach($item->orders as $order)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">
                                {{ $order->product?->name }}
                                @if($order->count > 1)
                                    <span class="text-muted font-fanum fw-normal">× {{ number_format($order->count) }}</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @if($order->quantity?->code)
                                    <span class="badge bg-light text-dark border font-monospace fs-11">{{ $order->quantity->code }}</span>
                                @endif
                                @if($order->quantity?->weight)
                                    <span class="badge bg-light text-dark border font-fanum fs-11">{{ $order->quantity->weight }} {{ __('gram') }}</span>
                                @endif
                                @foreach($order->quantity?->meta ?? [] as $m)
                                    <span class="badge bg-light text-dark border fs-11 fw-normal">
                                        <span class="text-muted">{{ $m['label'] }}:</span> {!! $m['human_value'] ?? '—' !!}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end font-fanum fw-bold text-dark text-nowrap">
                            {{ number_format($order->price_total) }}
                            <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                        </td>
                        @if($canRemoveItems)
                            <td class="text-center" style="width: 3rem">
                                <a href="{{ route('admin.invoice.remove-order', $order->id) }}" class="btn btn-sm btn-link link-secondary p-1 delete-confirm" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                    <i class="ri-delete-bin-line fs-16"></i>
                                </a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light border-top">
                @if((int) $item->transport_price > 0)
                    <tr>
                        <td class="text-muted fs-12">{{ __('Transport') }}</td>
                        <td class="text-end font-fanum text-dark text-nowrap">
                            {{ number_format($item->transport_price) }} <small class="text-muted fs-11">{{ __('Toman') }}</small>
                        </td>
                        @if($canRemoveItems)<td></td>@endif
                    </tr>
                @endif
                <tr>
                    <td class="fw-bold text-dark">{{ __('Total price') }}</td>
                    <td class="text-end font-fanum fw-bold text-primary fs-14 text-nowrap">
                        {{ number_format($item->total_price) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                    </td>
                    @if($canRemoveItems)<td></td>@endif
                </tr>
            </tfoot>
        </table>
    </div>
</div>
