@extends('layouts.app')

@section('content')
    <div class="mb-5 pb-5">
        @include('components.err')
        @hasSection('top-content')
            @yield('top-content')
        @endif

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                @hasSection('list-title')
                    <h4 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark fs-18">
                        @yield('list-title')
                    </h4>
                @endif
                @if(hasRoute('create'))
                    <a href="{{getRoute('create')}}" class="btn btn-sm btn-primary text-white d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="ri-add-line"></i>
                        <span>{{__("Add new")}}</span>
                    </a>
                @endif
                @yield('list-actions')
            </div>
            @yield('list-header-right')
        </div>

        {{-- WordPress Style Quick Filters Links Bar (All (10) | Mine (5) | Published (7) | Draft (2) | Trashed (1)) --}}
        @if(isset($quickCounts) && count($quickCounts) > 0)
            <div class="wp-quick-filters mb-2 px-1 fs-13">
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap gap-2 text-muted">
                    @php
                        $baseUrl = hasRoute('index') ? getRoute('index') : str_replace('/trashed', '', request()->url());
                        $currentStatus = request()->input('filter.status', null);
                        $currentMetal = request()->input('filter.metal_type', null);
                        $currentLowStock = request()->input('filter.low_stock', null);
                        $currentBelowBuyPrice = request()->input('filter.below_buy_price', null);
                        $isAll = $currentStatus === null && $currentMetal === null && $currentLowStock === null && $currentBelowBuyPrice === null && !request()->routeIs('*trashed*');

                        $preservedParams = [];
                        if (request()->filled('q')) {
                            $preservedParams['q'] = request('q');
                        }
                        if (request()->filled('sort')) {
                            $preservedParams['sort'] = request('sort');
                            if (request()->filled('sortType')) {
                                $preservedParams['sortType'] = request('sortType');
                            }
                        }
                        $allQuery = count($preservedParams) ? '?' . http_build_query($preservedParams) : '';
                    @endphp
                    <li class="list-inline-item m-0">
                        <a href="{{$baseUrl}}{{$allQuery}}" class="text-decoration-none @if($isAll) fw-bold text-primary @else text-dark @endif">
                            {{__("All")}} <span class="text-muted">({{number_format($quickCounts['all'] ?? 0)}})</span>
                        </a>
                    </li>
                    @if(isset($quickCounts['gold']))
                        @php
                            $goldUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['metal_type' => 'gold']]));
                            $isGoldActive = $currentMetal === 'gold';
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$goldUrl}}" class="text-decoration-none @if($isGoldActive) fw-bold text-warning @else text-dark @endif">
                                {{__("Gold")}} <span class="text-muted">({{number_format($quickCounts['gold'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['silver']))
                        @php
                            $silverUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['metal_type' => 'silver']]));
                            $isSilverActive = $currentMetal === 'silver';
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$silverUrl}}" class="text-decoration-none @if($isSilverActive) fw-bold text-secondary @else text-dark @endif">
                                {{__("Silver")}} <span class="text-muted">({{number_format($quickCounts['silver'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['low_stock']))
                        @php
                            $lowStockUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['low_stock' => '1']]));
                            $isLowStockActive = $currentLowStock === '1';
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$lowStockUrl}}" class="text-decoration-none @if($isLowStockActive) fw-bold text-danger @else text-dark @endif">
                                {{__("Low stock")}} <span class="@if(($quickCounts['low_stock'] ?? 0) > 0) text-danger fw-bold @else text-muted @endif">({{number_format($quickCounts['low_stock'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['below_buy_price']))
                        @php
                            $belowBuyPriceUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['below_buy_price' => '1']]));
                            $isBelowBuyPriceActive = $currentBelowBuyPrice === '1';
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$belowBuyPriceUrl}}" class="text-decoration-none @if($isBelowBuyPriceActive) fw-bold text-danger @else text-dark @endif">
                                {{__("Below purchase price")}} <span class="@if(($quickCounts['below_buy_price'] ?? 0) > 0) text-danger fw-bold @else text-muted @endif">({{number_format($quickCounts['below_buy_price'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['published']))
                        @php
                            $pubUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['status' => 1]]));
                            $isPubActive = ($currentStatus === '1' || $currentStatus === 1);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$pubUrl}}" class="text-decoration-none @if($isPubActive) fw-bold text-primary @else text-dark @endif">
                                {{__("Published")}} <span class="text-muted">({{number_format($quickCounts['published'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['draft']))
                        @php
                            $draftUrl = $baseUrl . '?' . http_build_query(array_merge($preservedParams, ['filter' => ['status' => 0]]));
                            $isDraftActive = ($currentStatus === '0' || $currentStatus === 0);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$draftUrl}}" class="text-decoration-none @if($isDraftActive) fw-bold text-primary @else text-dark @endif">
                                {{__("Draft")}} <span class="text-muted">({{number_format($quickCounts['draft'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['trashed']) && hasRoute('trashed'))
                        @php
                            $trashedUrl = getRoute('trashed') . (count($preservedParams) ? '?' . http_build_query($preservedParams) : '');
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$trashedUrl}}" class="text-decoration-none @if(request()->routeIs('*trashed*')) fw-bold text-danger @else text-dark @endif">
                                {{__("Trashed")}} <span class="text-muted">({{number_format($quickCounts['trashed'])}})</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        {{-- WordPress Style Compact Single Action & Filter Row --}}
        <div class="wp-tablenav overflow-visible mb-3 p-2 bg-white border rounded-3 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2">
            <!-- Left Actions & Custom Filters -->
            <form action="" method="GET" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1 mb-0">
                @if(hasRoute('bulk'))
                    <div class="bulk-action-inline d-flex align-items-center gap-1">
                        <select data-bulk-action class="form-select form-select-sm w-auto" name="action" style="min-width: 140px;">
                            <option value="">{{__("Bulk actions")}}</option>
                            @if(strpos(request()->url(),'trashed') != false)
                                <option value="restore">{{__("Batch restore")}}</option>
                            @else
                                <option value="delete">{{__("Batch delete")}}</option>
                            @endif
                            @yield('bulk')
                        </select>
                        <button type="submit" form="main-form" data-bulk-run class="btn btn-sm btn-outline-secondary" disabled>
                            {{__("Apply")}}
                        </button>
                    </div>
                @endif

                @if(request()->has('q') && trim(request()->input('q')) != '')
                    <input type="hidden" name="q" value="{{request()->input('q')}}">
                @endif

                {{-- Custom Filters --}}
                @hasSection('filter')
                    @yield('filter')
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="ri-filter-3-line me-1"></i>{{__("Filter")}}
                    </button>
                @endif
            </form>

            <!-- Right Search Box with Separate Search Action Button -->
            <form action="" method="GET" class="d-flex align-items-center gap-1 ms-auto mb-0" style="max-width: 300px; min-width: 220px;">
                @if(request()->has('filter'))
                    @foreach(request()->input('filter', []) as $fk => $fv)
                        @if(is_array($fv))
                            @foreach($fv as $fval)
                                <input type="hidden" name="filter[{{$fk}}][]" value="{{$fval}}">
                            @endforeach
                        @elseif($fv !== null && $fv !== '')
                            <input type="hidden" name="filter[{{$fk}}]" value="{{$fv}}">
                        @endif
                    @endforeach
                @endif
                <input type="search" name="q" class="form-control form-control-sm" placeholder="{{__('Search')}}..." value="{{request()->input('q','')}}">
                <button type="submit" class="btn btn-sm btn-primary px-2.5" title="{{__('Search')}}">
                    <i class="ri-search-line"></i>
                </button>
            </form>
        </div>

        {{-- Table Content --}}
        <div class="w-100">
            <form class="item-list" id="main-form"
                  @if(hasRoute('bulk'))
                      action="{{getRoute('bulk',[])}}" method="POST"
                  @endif>
                @if(hasRoute('bulk'))
                    @csrf
                    <input type="hidden" name="action" id="main-form-action-input" value="">
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>
                                <div
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-custom-class="custom-tooltip"
                                    data-bs-title="{{__("Check all")}}"
                                    class="form-check form-switch mt-1 mx-2">
                                    <input class="form-check-input chkall"
                                           type="checkbox" role="switch">
                                </div>
                            </th>
                            @if(isset($items[0]) && method_exists($items[0],'imgUrl'))
                                <th>
                                    {{__("image")}}
                                </th>
                            @endif
                            @foreach($cols as $col)
                                <th>
                                    <a href="?sort={{$col}}{{sortSuffix($col)}}&{{queryBuilder('sort')}}">
                                        @if(request()->routeIs('admin.stock.*') && $col === 'stock_quantity')
                                            {{__("Net stock")}}
                                        @else
                                            {{__($col)}}
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(count($items) == 0)
                            <tr>
                                <td colspan="100%">
                                    {{__("There is nothing to show!")}}
                                </td>
                            </tr>
                        @else
                            @foreach($items as $item)
                                <tr>

                                    <td>
                                        <div class="form-check m-0 d-inline-flex align-items-center gap-1">
                                            <input type="checkbox" id="chk-{{$item->id}}" class="form-check-input chkbox m-0"
                                                   name="id[{{$item->id}}]" value="{{$item->id}}">
                                            <label class="form-check-label ms-1" for="chk-{{$item->id}}">
                                                {{$item->id}}
                                            </label>
                                        </div>
                                    </td>
                                    @if(isset($item) && method_exists($item,'imgUrl'))
                                        <td>
                                            <a href="{{getRoute('edit',$item->{$item->getRouteKeyName()})}}">
                                                <img src="{{$item->imgUrl()}}" class="image-x64" alt="">
                                            </a>
                                        </td>
                                    @endif
                                    @foreach($cols as $k => $col)
                                        @if($k == 0 && hasRoute('edit'))
                                            <td>
                                                <a href="{{getRoute('edit',$item->{$item->getRouteKeyName()})}}">
                                                    <b>
                                                        @if(in_array($cols[0], ['created_at', 'updated_at', 'expire'], true))
                                                            {{ $item->{$cols[0]}?->ldate('Y-m-d H:i') ?? '-' }}
                                                        @else
                                                            {{strip_tags($item?->{$cols[0]}) }}
                                                        @endif
                                                    </b>
                                                </a>
                                            </td>
                                        @else
                                            <td>
                                                @switch($col)
                                                    @case('parent_id')
                                                        {{ $item->parent?->{$cols[0]}??'-' }}
                                                        @break
                                                     @case('status')
                                                         @php
                                                             $stVal = (string) $item->status;
                                                             $stIsPublished = ($stVal === '1' || strtolower($stVal) === 'published');
                                                             $stIsDraft = ($stVal === '0' || strtolower($stVal) === 'draft');
                                                         @endphp
                                                         @if(method_exists($item, 'statusLabel'))
                                                             <span class="{{ $item->statusBadgeClass() }}">
                                                                 {{ $item->statusLabel() }}
                                                             </span>
                                                         @elseif($stIsPublished)
                                                             <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                 {{__("Published")}}
                                                             </span>
                                                         @elseif($stIsDraft)
                                                             <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                                 {{__("Draft")}}
                                                             </span>
                                                         @else
                                                             <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                                 {{ __($item->status) }}
                                                             </span>
                                                         @endif
                                                         @break
                                                    @case('user_id')
                                                        @if($item->user != null)
                                                            <a href="{{route('admin.user.edit',$item->user?->email)}}">
                                                                {{ $item->user?->name??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('customer_id')
                                                        @if($item->customer != null)
                                                            <a href="{{route('admin.customer.edit',$item->customer?->id)}}">
                                                                {{ $item->customer?->name??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('category_id')
                                                        @if($item->category != null)
                                                            <a href="{{route('admin.category.edit',$item->category?->slug)}}">
                                                                {{ $item->category?->name??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('state_id')
                                                        @if($item->state != null)
                                                            <a href="{{route('admin.state.edit',$item->state?->id)}}">
                                                                {{ $item->state?->name??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('product_id')
                                                        @if($item->product != null)
                                                            <a href="{{route('admin.product.edit',$item->product?->slug)}}">
                                                                {{ $item->product?->name??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('evaluation_id')
                                                        @if($item->evaluation != null)
                                                            <a href="{{route('admin.evaluation.edit',$item->evaluation_id)}}">
                                                                {{ $item->evaluation?->title??'-' }}
                                                            </a>
                                                        @else
                                                            {{__("Removed")}}
                                                        @endif
                                                        @break
                                                    @case('submitted_at')
                                                        {{ $item->submittedAtLabel() }}
                                                        @break
                                                    @case('expire')
                                                    @case('created_at')
                                                    @case('updated_at')
                                                        {{$item->$col?->ldate("Y-m-d H:i")??'-'}}
                                                        @break
                                                    @case('has_purchase')
                                                        {{ $item->purchaseLabel() }}
                                                        @break
                                                     @case('metal_type')
                                                         <span class="badge @if(($item->metal_type ?? 'gold') == 'silver') bg-secondary text-white @else bg-warning text-dark @endif">
                                                             {{ $item->metal_type == 'silver' ? __('Silver') : __('Gold') }}
                                                         </span>
                                                         @break
                                                     @case('target_group')
                                                         @php
                                                             $tgMap = [
                                                                 'women' => __("Women's"),
                                                                 'men' => __("Men's"),
                                                                 'children' => __("Children's"),
                                                                 'unisex' => __("Unisex"),
                                                             ];
                                                             $tgVal = $tgMap[$item->target_group ?? 'unisex'] ?? $item->target_group;
                                                         @endphp
                                                         <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                             {{ $tgVal }}
                                                         </span>
                                                         @break
                                                      @case('weight')
                                                          <span>{{ number_format($item->weight ?? 0, 3) }} {{__('g')}}</span>
                                                          @break
                                                      @case('total_weight')
                                                          <span class="fw-semibold text-dark">{{ \App\Services\AdminDashboardStats::formatWeight($item->total_weight ?? ($item->weight ?? 0)) }}</span>
                                                          <small class="text-muted fs-12">{{__('g')}}</small>
                                                          @break
                                                       @case('price')
                                                           <span class="fw-bold text-dark">{{ number_format($item->price ?? 0) }}</span>
                                                           <small class="text-muted fs-12">{{ __('Toman') }}</small>
                                                           @break
                                                      @case('total_price')
                                                          <span class="fw-bold text-dark">{{ number_format($item->total_price ?? ($item->price ?? 0)) }}</span>
                                                          <small class="text-muted fs-12">{{ __('Toman') }}</small>
                                                          @break
                                                        @case('sku')
                                                            <code class="fw-bold text-primary font-monospace">{{ $item->sku ?: '-' }}</code>
                                                            @break
                                                        @case('total_ordered')
                                                            @php
                                                                $totalOrd = method_exists($item, 'totalOrderedCount') ? $item->totalOrderedCount() : ($item->total_ordered_count ?? 0);
                                                            @endphp
                                                            <div class="d-inline-flex align-items-center gap-1">
                                                                <span class="badge bg-light text-dark border border-secondary-subtle fs-12 fw-semibold">
                                                                    {{ number_format($totalOrd) }} {{ __('pieces') }}
                                                                </span>
                                                                @if(request()->routeIs('admin.stock.*') && $totalOrd > 0)
                                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 fs-11 view-pieces-btn" data-product-id="{{ $item->id }}" data-product-name="{{ $item->name }}" title="{{ __('View piece codes') }}">
                                                                        <i class="ri-eye-line"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            @break
                                                        @case('scrapped_pieces')
                                                            @php
                                                                $scrappedCnt = method_exists($item, 'scrappedPiecesCount') ? $item->scrappedPiecesCount() : ($item->scrapped_pieces_count ?? 0);
                                                            @endphp
                                                            @if($scrappedCnt > 0)
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-12 fw-bold" title="{{ __('Defective or scrapped pieces') }}">
                                                                    <i class="ri-fire-line me-0.5"></i>{{ number_format($scrappedCnt) }} {{ __('pieces') }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted fs-12">—</span>
                                                            @endif
                                                            @break
                                                        @case('sold_pieces')
                                                            @php
                                                                $soldCnt = method_exists($item, 'soldPiecesCount') ? $item->soldPiecesCount() : ($item->sold_pieces_count ?? 0);
                                                            @endphp
                                                            @if($soldCnt > 0)
                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12 fw-semibold">
                                                                    <i class="ri-shopping-bag-3-line me-0.5"></i>{{ number_format($soldCnt) }} {{ __('pieces') }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted fs-12">—</span>
                                                            @endif
                                                            @break
                                                       @case('stock_quantity')
                                                           @php
                                                               $isLowStock = method_exists($item, 'isLowStock')
                                                                   ? $item->isLowStock()
                                                                   : (($item->min_stock_level ?? 0) > 0 && ($item->stock_quantity ?? 0) < ($item->min_stock_level ?? 0));
                                                           @endphp
                                                           <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
                                                               <span class="@if($isLowStock) text-danger fw-bold @endif">
                                                                   {{ number_format($item->stock_quantity ?? 0) }}
                                                               </span>
                                                               <small class="text-muted fs-12">{{ __('pieces') }}</small>
                                                              @if($isLowStock)
                                                                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{__('Below minimum stock (:min)', ['min' => $item->min_stock_level])}}">
                                                                      <i class="ri-alarm-warning-line me-1"></i>{{__('Low stock')}}
                                                                  </span>
                                                              @endif
                                                              @if(method_exists($item, 'isBelowBuyPrice') && $item->isBelowBuyPrice())
                                                                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="{{__('Selling price (:price) is below purchase price (:buy_price)', ['price' => number_format($item->price ?? 0), 'buy_price' => number_format($item->buy_price ?? 0)])}}">
                                                                      <i class="ri-error-warning-line me-1"></i>{{__('Below purchase price')}}
                                                                  </span>
                                                              @endif
                                                          </div>
                                                          @break
                                                     @case('icon')
                                                         <i class="{{$item->$col}}"></i>
                                                         @break
                                                    @default
                                                        @if(substr($col,0,3) == 'is_')
                                                            @if($item->$col == 1)
                                                                <i class="ri-check-line"></i>
                                                            @endif
                                                        @elseif(gettype($item->$col) == 'integer')
                                                            {{number_format($item->$col)}}
                                                        @elseif($col === 'role')
                                                            {{ __((string) $item->$col) }}
                                                        @elseif(strpos($col,'_type'))
                                                            {{ __(str_replace('App\\Models\\', '', (string) $item->$col)) }}
                                                        @else
                                                            {{$item->$col}}
                                                        @endif
                                                @endswitch
                                            </td>
                                        @endif
                                    @endforeach
                                    {{--                                    @yield('table-body')--}}
                                    <td>

                                        @if(strpos(request()->url(),'trashed') != false && hasRoute('restore'))
                                            <a href="{{getRoute('restore',$item->{$item->getRouteKeyName()})}}"
                                               class="btn btn-success btn-sm mx-1 d-xl-none d-xxl-none"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               data-bs-custom-class="custom-tooltip"
                                               data-bs-title="{{__("Restore")}}">
                                                <i class="ri-recycle-line"></i>
                                            </a>
                                        @else

                                            <div class="dropdown d-xl-none d-xxl-none">
                                                <a class="btn btn-outline-secondary dropdown-toggle" href="#"
                                                   role="button"
                                                   data-bs-toggle="dropdown" aria-expanded="false">
                                                </a>
                                                <ul class="dropdown-menu">
                                                    @foreach($buttons as $btn => $btnData)
                                                        @if(isset($btnData['can']) && is_callable($btnData['can']) && ! $btnData['can']($item))
                                                            @continue
                                                        @endif
                                                        @php
                                                            $btnUrl = isset($btnData['route']) ? route($btnData['route'], $item->{$item->getRouteKeyName()}) : getRoute($btn,$item->{$item->getRouteKeyName()});
                                                            $isDelete = strpos($btnData['class'], 'delete') !== false;
                                                            $dropItemClass = 'dropdown-item d-flex align-items-center gap-2 ' . ($isDelete ? 'delete-confirm text-danger' : 'text-dark');
                                                        @endphp
                                                        <li>
                                                            <a class="{{$dropItemClass}}"
                                                               href="{{$btnUrl}}">
                                                                <i class="{{$btnData['icon']}}"></i>
                                                                <span>{{__($btnData['title'])}}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <div class="d-none d-xl-block  d-xxl-block">
                                            @foreach($buttons as $btn => $btnData)
                                                @if(isset($btnData['can']) && is_callable($btnData['can']) && ! $btnData['can']($item))
                                                    @continue
                                                @endif

                                                @if(strpos($btnData['class'],'delete') == false )
                                                    @if(strpos(request()->url(),'trashed') == false)

                                                         @php
                                                             $btnUrl = isset($btnData['route']) ? route($btnData['route'], $item->{$item->getRouteKeyName()}) : getRoute($btn,$item->{$item->getRouteKeyName()});
                                                         @endphp
                                                         <a href="{{$btnUrl}}"
                                                            class="btn {{$btnData['class']}} btn-sm mx-1"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="{{__($btnData['title'])}}">
                                                             <i class="{{$btnData['icon']}}"></i>
                                                         </a>
                                                     @endif
                                                 @else
                                                     @if( hasRoute('restore') && $item->trashed())
                                                         <a class="btn btn-success btn-sm mx-1"
                                                            href="{{getRoute('restore',$item->id)}}"
                                                            {{--dont change this id to getRouteKeyName --}}
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="{{__("Restore")}}">
                                                             <i class="ri-recycle-line"></i>
                                                         </a>
                                                     @else
                                                         @php
                                                             $btnUrl = isset($btnData['route']) ? route($btnData['route'], $item->{$item->getRouteKeyName()}) : getRoute($btn,$item->{$item->getRouteKeyName()});
                                                         @endphp
                                                         <a href="{{$btnUrl}}"
                                                            class="btn {{$btnData['class']}} btn-sm mx-1"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="{{__($btnData['title'])}}">
                                                             <i class="{{$btnData['icon']}}"></i>
                                                         </a>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>

                            @endforeach
                        @endif

                        </tbody>


                        {{-- pagination and toggle button start --}}
                        <tfoot>
                        <tr>
                            <th colspan="100%">
                                <div class="row">
                                    <div class="col-md-3 text-start">
                                        <div
                                            id="toggle-select"
                                            class="btn btn-sm btn-outline-secondary mx-2"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="{{__("Toggle selection")}}">
                                            <i class="ri-toggle-line"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        {{$items->withQueryString()->links()}}
                                    </div>
                                    <div class="col-md-3 text-center">
                                    </div>
                                </div>
                            </th>
                        </tr>
                        </tfoot>
                        {{-- pagination and toggle button end --}}
                    </table>
                    </div>
                </form>
            </div>
    </div>

    @yield('list-foot')

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var actionInput = document.getElementById('main-form-action-input');
        var mainForm = document.getElementById('main-form');

        function updateAction() {
            var activeSelect = document.querySelector('[data-bulk-action]');
            if (activeSelect && actionInput) {
                actionInput.value = activeSelect.value || '';
            }
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.matches('[data-bulk-action]')) {
                updateAction();
            }
        });

        if (mainForm) {
            mainForm.addEventListener('submit', function () {
                updateAction();
            });
        }
    });
    </script>
@endsection
