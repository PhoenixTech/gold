@extends('layouts.app')

@section('content')
    <div class="mb-5 pb-5">
        @include('components.err')

        {{-- Minimal Horizontal Filter Bar --}}
        <div class="item-list mb-3 p-2 p-md-3">
            <form action="" method="GET" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    {{-- Search Input --}}
                    <div class="search-input-wrapper" style="min-width: 220px; max-width: 340px; flex-grow: 1;">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" id="button-addon2">
                                <i class="ri-search-2-line text-muted"></i>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="{{__("Search")}}..."
                                   aria-label="{{__("Search")}}..." aria-describedby="button-addon2"
                                   value="{{request()->input('q','')}}">
                        </div>
                    </div>

                    {{-- Custom Filters --}}
                    @yield('filter')

                    <button type="submit" class="btn btn-primary px-3">
                        <i class="ri-filter-3-line me-1"></i>{{__("Filter")}}
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    @if(hasRoute('trashed'))
                        <a class="btn btn-outline-danger btn-sm px-2.5"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-title="{{__("Trashed items")}}"
                           href="{{getRoute('trashed')}}">
                            <i class="ri-delete-bin-6-line fs-6"></i>
                        </a>
                    @endif
                </div>
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
                @endif
                <div class="bulk-toolbar-mobile align-items-center justify-content-between gap-2 flex-wrap p-2">
                    <span class="small text-muted">
                        {{__("Bulk actions")}}
                    </span>
                    @include('admin.templates.partials.bulk-toolbar')
                </div>
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
                            {{--                            @yield('table-head')--}}
                            <th class="d-none d-md-table-cell">
                                @include('admin.templates.partials.bulk-toolbar')
                            </th>
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
                                                        <span class="badge bg-secondary float-start"
                                                              data-bs-toggle="tooltip"
                                                              data-bs-placement="top"
                                                              data-bs-custom-class="custom-tooltip"
                                                              data-bs-title="{{$item->status}}">{{$item->status}}</span>
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
@endsection
