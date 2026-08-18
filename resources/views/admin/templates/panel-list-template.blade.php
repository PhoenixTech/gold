@extends('layouts.app')

@section('content')
    <div class="mb-5 pb-5">
        @include('components.err')

        {{-- WordPress Style Quick Filters Links Bar (All (10) | Mine (5) | Published (7) | Draft (2) | Trashed (1)) --}}
        @if(isset($quickCounts) && count($quickCounts) > 0)
            <div class="wp-quick-filters mb-2 px-1 fs-13">
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap gap-2 text-muted">
                    @php
                        $baseUrl = hasRoute('index') ? getRoute('index') : str_replace('/trashed', '', request()->url());
                        $currentStatus = request()->input('filter.status', null);
                        $currentMetal = request()->input('filter.metal_type', null);
                        $isAll = $currentStatus === null && $currentMetal === null && !request()->routeIs('*trashed*');
                    @endphp
                    <li class="list-inline-item m-0">
                        <a href="{{$baseUrl}}" class="text-decoration-none @if($isAll) fw-bold text-primary @else text-dark @endif">
                            {{__("All")}} <span class="text-muted">({{number_format($quickCounts['all'] ?? 0)}})</span>
                        </a>
                    </li>
                    @if(isset($quickCounts['gold']))
                        @php
                            $goldFilter = array_merge(request()->input('filter', []), ['metal_type' => 'gold']);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$baseUrl}}?{{http_build_query(['filter' => $goldFilter])}}" class="text-decoration-none @if($currentMetal === 'gold') fw-bold text-warning @else text-dark @endif">
                                {{__("Gold")}} <span class="text-muted">({{number_format($quickCounts['gold'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['silver']))
                        @php
                            $silverFilter = array_merge(request()->input('filter', []), ['metal_type' => 'silver']);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$baseUrl}}?{{http_build_query(['filter' => $silverFilter])}}" class="text-decoration-none @if($currentMetal === 'silver') fw-bold text-secondary @else text-dark @endif">
                                {{__("Silver")}} <span class="text-muted">({{number_format($quickCounts['silver'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['published']))
                        @php
                            $pubFilter = array_merge(request()->input('filter', []), ['status' => 1]);
                            unset($pubFilter['user_id']);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$baseUrl}}?{{http_build_query(['filter' => $pubFilter])}}" class="text-decoration-none @if($currentStatus === '1' || $currentStatus === 1) fw-bold text-primary @else text-dark @endif">
                                {{__("Published")}} <span class="text-muted">({{number_format($quickCounts['published'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['draft']))
                        @php
                            $draftFilter = array_merge(request()->input('filter', []), ['status' => 0]);
                            unset($draftFilter['user_id']);
                        @endphp
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{$baseUrl}}?{{http_build_query(['filter' => $draftFilter])}}" class="text-decoration-none @if($currentStatus === '0' || $currentStatus === 0) fw-bold text-primary @else text-dark @endif">
                                {{__("Draft")}} <span class="text-muted">({{number_format($quickCounts['draft'])}})</span>
                            </a>
                        </li>
                    @endif
                    @if(isset($quickCounts['trashed']) && hasRoute('trashed'))
                        <li class="list-inline-item m-0 text-black-50">|</li>
                        <li class="list-inline-item m-0">
                            <a href="{{getRoute('trashed')}}" class="text-decoration-none @if(request()->routeIs('*trashed*')) fw-bold text-danger @else text-dark @endif">
                                {{__("Trashed")}} <span class="text-muted">({{number_format($quickCounts['trashed'])}})</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        {{-- WordPress Style Compact Single Action & Filter Row --}}
        <div class="wp-tablenav mb-3 p-2 bg-white border rounded-3 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2">
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
                                        {{__($col)}}
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
                                                        {{strip_tags($item?->{$cols[0]}) }}
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
                                                         @if($stIsPublished)
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
                                                    @case('expire')
                                                    @case('created_at')
                                                    @case('updated_at')
                                                        {{$item->$col?->ldate("Y-m-d H:i")??'-'}}
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
                                                      @case('sku')
                                                          <code class="fw-bold text-primary">{{ $item->sku ?: '-' }}</code>
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
                                                        @elseif(strpos($col,'_type'))
                                                            {{str_replace('App\\Models\\', '' , $item->$col)}}
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
                                                        <li>
                                                            <a class="dropdown-item {{$btnData['class']}}"
                                                               href="{{getRoute($btn,$item->{$item->getRouteKeyName()})}}">
                                                                <i class="{{$btnData['icon']}}"></i>
                                                                &nbsp;
                                                                {{__($btnData['title'])}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                    @if(config('app.xlang.active') && isset($item->translatable))
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{route('admin.lang.model',[$item->id, get_class($item)])}}">
                                                                <i class="ri-translate"></i>
                                                                &nbsp;
                                                                {{__("Translate")}}
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif
                                        <div class="d-none d-xl-block  d-xxl-block">
                                            @foreach($buttons as $btn => $btnData)

                                                @if(strpos($btnData['class'],'delete') == false )
                                                    @if(strpos(request()->url(),'trashed') == false)

                                                        <a href="{{getRoute($btn,$item->{$item->getRouteKeyName()})}}"
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
                                                        <a href="{{getRoute($btn,$item->{$item->getRouteKeyName()})}}"
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
                                            @if(config('app.xlang.active') && isset($item->translatable))
                                                <a href="{{route('admin.lang.model',[$item->id, get_class($item)])}}"
                                                   class="btn btn-outline-secondary translat-btn btn-sm mx-1">
                                                    <i class="ri-translate"></i>
                                                </a>
                                            @endif
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
                                            class="btn btn-outline-light mx-2"
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
    @if(hasRoute('create'))
        <a class="action-btn circle-btn"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{__("Add another one")}}"
           href="{{getRoute('create')}}"
        >
            <i class="ri-add-line"></i>
        </a>
    @endif

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
