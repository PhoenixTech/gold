@extends('admin.invoices.manual.layout')

@section('step')
    @php
        $allAvailable = $lines->isNotEmpty() && $lines->every(fn (array $line) => $line['available']);
    @endphp

    <div class="item-list shadow-sm mb-4">
        <div class="p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="ri-search-line text-primary fs-18"></i>
                <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Add stock pieces') }}</h5>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Only available pieces are listed. Pieces below the purchase price are hidden.') }}</p>

            <form method="GET" action="{{ route('admin.shop-invoice.create') }}" class="mb-4">
                <input type="hidden" name="step" value="items">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="ri-search-line"></i></span>
                    <input type="search" name="q" value="{{ $search }}" class="form-control border-start-0"
                           placeholder="{{ __('Product name, SKU or piece code') }}">
                    <button type="submit" class="btn btn-primary px-3">
                        {{ __('Search') }}
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.shop-invoice.store') }}">
                @csrf
                <input type="hidden" name="step" value="items">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="q" value="{{ $search }}">

                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted fs-12 fw-semibold">
                            <tr>
                                <th style="width: 2.75rem" class="text-center"></th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Piece code') }}</th>
                                <th>{{ __('Weight') }}</th>
                                <th>{{ __('Price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pieces as $row)
                                @php $piece = $row['piece']; @endphp
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" name="quantity_ids[]"
                                               value="{{ $piece->id }}" id="piece-{{ $piece->id }}">
                                    </td>
                                    <td>
                                        <label for="piece-{{ $piece->id }}" class="mb-0 fw-semibold text-dark fs-13 d-block cursor-pointer">
                                            {{ $piece->product->name }}
                                        </label>
                                        <small class="text-muted font-monospace fs-11">{{ $piece->product->sku }}</small>
                                    </td>
                                    <td class="font-monospace text-dark fs-12">{{ $piece->code ?: '—' }}</td>
                                    <td class="font-fanum text-dark fs-13">{{ $piece->weight ? $piece->weight.' '.__('g') : '—' }}</td>
                                    <td class="font-fanum fw-bold text-dark fs-13">
                                        {{ number_format($row['price']) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4 fs-13">
                                        {{ __('No available pieces match your search.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-1">
                    <div>{{ $pieces->links() }}</div>
                    <button type="submit" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ri-add-line"></i>
                        <span>{{ __('Add selected pieces') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="item-list shadow-sm mb-4">
        <div class="p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-shopping-bag-3-line text-primary fs-18"></i>
                    <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Selected pieces') }}</h5>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-fanum">
                    {{ number_format($lines->count()) }}
                </span>
            </div>

            @if($lines->isEmpty())
                <div class="p-4 text-center text-muted fs-13 rounded-3 bg-light border border-dashed">
                    <i class="ri-shopping-bag-line fs-2 d-block mb-1 opacity-50"></i>
                    {{ __('No pieces selected yet.') }}
                </div>
            @else
                <div class="table-responsive border rounded-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-muted fs-12 fw-semibold">
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Piece code') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lines as $line)
                                <tr class="{{ $line['available'] ? '' : 'table-danger' }}">
                                    <td class="fw-semibold text-dark fs-13">{{ $line['piece']?->product?->name ?? '#'.$line['id'] }}</td>
                                    <td class="font-monospace text-dark fs-12">{{ $line['piece']?->code ?: '—' }}</td>
                                    <td class="font-fanum fw-bold text-dark fs-13">
                                        {{ number_format($line['price']) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                    </td>
                                    <td>
                                        @if($line['available'])
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Available') }}</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('No longer available') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.shop-invoice.store') }}">
                                            @csrf
                                            <input type="hidden" name="step" value="items">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="quantity_id" value="{{ $line['id'] }}">
                                            <input type="hidden" name="q" value="{{ $search }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                                <i class="ri-close-circle-line"></i>
                                                <span>{{ __('Remove') }}</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-dark">{{ __('Total price') }}</th>
                                <th class="font-fanum fw-bold text-dark fs-14">
                                    {{ number_format($total) }} <small class="text-muted fw-normal fs-11">{{ __('Toman') }}</small>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between gap-2">
        <a href="{{ route('admin.shop-invoice.create') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="ri-arrow-right-line"></i>
            <span>{{ __('Back') }}</span>
        </a>
        @if($allAvailable)
            <a href="{{ route('admin.shop-invoice.create', ['step' => 'payment']) }}" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5">
                <span>{{ __('Next') }}</span>
                <i class="ri-arrow-left-line"></i>
            </a>
        @else
            <button type="button" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5" disabled>
                <span>{{ __('Next') }}</span>
                <i class="ri-arrow-left-line"></i>
            </button>
        @endif
    </div>
@endsection
