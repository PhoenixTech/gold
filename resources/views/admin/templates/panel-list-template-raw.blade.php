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
                            <span class="input-group-text bg-light border-end-0" id="button-addon2">
                                <i class="ri-search-2-line text-muted"></i>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="{{__("Search")}}..."
                                   aria-label="{{__("Search")}}..." aria-describedby="button-addon2"
                                   value="{{request()->input('q','')}}">
                        </div>
                        @yield('filter')
                        <button class="btn btn-primary w-100 py-2 font-semibold">
                            <i class="ri-filter-3-line me-1"></i>{{__("Search & Filter")}}
                        </button>
                    </form>
                </div>

                @yield('side-raw')





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
                    <div class="table-responsive">
                        @yield('table')
                    </div>
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
