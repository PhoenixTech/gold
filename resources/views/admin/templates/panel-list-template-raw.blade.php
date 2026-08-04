@extends('layouts.app')

@section('content')
    <div class="mb-5 pb-5">
        <div class="row">

            {{--  list side bar start--}}
            <div class="col-xl-3">
                @include('components.err')
                <div class="item-list mb-3">
                    @if(hasRoute('trashed'))
                        <div class="text-end p-2">
                            <a class="btn btn-outline-danger btn-sm"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-title="{{__("Trashed items")}}"
                               href="{{getRoute('trashed')}}"
                            >
                                <i class="ri-delete-bin-6-line"></i>
                            </a>
                        </div>
                    @endif
                    <form action="" class="p-3">
                        <div class="input-group mb-3">
                            <span class="btn btn-outline-secondary" type="button" id="button-addon2">
                                <i class="ri-search-2-line"></i>
                            </span>
                            <input type="text" name="q" class="form-control" placeholder="{{__("Search")}}..."
                                   aria-label="{{__("Search")}}..." aria-describedby="button-addon2"
                                   value="{{request()->input('q','')}}">
                        </div>
                        @yield('filter')
                        <button class="btn btn-primary w-100">
                            {{__("Search & Filter")}}
                        </button>
                    </form>
                </div>

                @yield('side-raw')

                <div class="item-list mb-3 py-3">
                    <div class="grid-equal text-center p-1">
                        <span>
                             {{__("Total")}}
                        </span>
                        <span>
                            ({{$items->total()}})
                        </span>
                    </div>
                    <hr>
                    <div class="grid-equal text-center p-1">
                        <span>
                             {{__("From - To")}}
                        </span>
                        <span>
                             @paginated($items)
                        </span>
                    </div>
                </div>



            </div>
            {{--  list side bar end--}}


            {{--   list content start--}}
            <div class="col-xl-9 ps-xl-0">
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
                    @yield('table')
                </form>
            </div>
        </div>
        {{--   list content end--}}
    </div>

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
