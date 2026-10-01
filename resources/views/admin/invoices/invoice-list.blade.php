@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-file-list-3-line"></i>
    {{__("Invoices list")}}
@endsection

@section('top-content')

    {{-- Status quick filters with live counts --}}
    @if(!empty($statusChips))
        @php $currentStatus = request()->input('filter.status'); @endphp
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3 px-1">
            <a href="{{ getRoute('index', request()->except(['filter.status', 'filter'])) }}"
               class="btn btn-sm {{ $currentStatus === null ? 'btn-light' : 'btn-outline-secondary' }} rounded-pill px-3">
                {{ __('All') }}
                <span class="ms-1 opacity-75">{{ number_format($quickCounts['all'] ?? 0) }}</span>
            </a>
            @foreach($statusChips as $chip)
                <a href="{{ getRoute('index', array_merge(request()->except(['filter.status','filter']), ['filter' => ['status' => $chip['key']]])) }}"
                   class="btn btn-sm {{ $currentStatus === $chip['key'] ? 'btn-light' : 'btn-outline-secondary' }} rounded-pill px-3"
                   title="{{ $chip['label'] }}">
                    <i class="{{ $chip['icon'] }} me-1"></i>{{ $chip['label'] }}
                    <span class="ms-1 opacity-75">{{ number_format($chip['count']) }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@section('title')
    {{__("Invoices list")}} -
@endsection

@section('filter')
    <select name="filter[status]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All statuses")}}</option>
        @foreach(\App\Models\Invoice::adminFilterStatuses() as $st)
            <option value="{{$st}}" @if(request()->input('filter.status') == $st) selected @endif>
                {{__($st)}}
            </option>
        @endforeach
    </select>
    <select name="filter[delivery_type]" class="form-select form-select-sm w-auto">
        <option value="">{{ __("All delivery types") }}</option>
        <option value="address" @selected(request()->input('filter.delivery_type') === 'address')>{{ __("Shipped to address") }}</option>
        <option value="pickup" @selected(request()->input('filter.delivery_type') === 'pickup')>{{ __("Store pickup") }}</option>
    </select>
@endsection

@section('list-foot')
    @if(!empty($listTotals) && !request()->routeIs('*trashed*'))
        <div class="card border-0 shadow-sm rounded-3 mt-3">
            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted fs-13">
                        <i class="ri-list-check-2 me-1"></i>
                        {{ __('Showing :rows of :total', [
                            'rows' => number_format($listTotals['rows']),
                            'total' => number_format($listTotals['total_count']),
                        ]) }}
                    </span>
                    @if($listTotals['page_weight'] > 0)
                        <span class="text-muted fs-13">
                            <i class="ri-scales-3-line me-1"></i>
                            {{ __('Weight on this page') }}:
                            <b class="text-dark font-fanum">{{ \App\Services\AdminDashboardStats::formatWeight($listTotals['page_weight']) }} {{ __('g') }}</b>
                        </span>
                    @endif
                </div>
                <span class="text-muted fs-13">
                    {{ __('Amount on this page') }}:
                    <b class="text-dark font-fanum fs-6">{{ number_format($listTotals['page_total']) }}</b>
                    <small class="text-muted">{{ $listTotals['currency'] }}</small>
                </span>
            </div>
        </div>
    @endif

    @if(!empty($perPageOptions))
        <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
            <label class="text-muted fs-12" for="per_page_select">{{ __('Rows per page') }}</label>
            <select id="per_page_select" class="form-select form-select-sm w-auto">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($items->perPage() === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <script>
            document.getElementById('per_page_select')?.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        </script>
    @endif
@endsection

@section('bulk')
@endsection
