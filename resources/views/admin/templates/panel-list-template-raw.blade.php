@extends('layouts.app')

@section('content')
    <div class="mb-5 pb-5">
        @include('components.err')

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

        {{-- Minimal Horizontal Filter Bar --}}
        <div class="item-list overflow-visible mb-3 p-2 p-md-3">
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
                    @hasSection('side-raw')
                        @yield('side-raw')
                    @endif
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

        {{-- Content --}}
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
                    @yield('table')
                </div>
            </form>
        </div>
    </div>
@endsection
