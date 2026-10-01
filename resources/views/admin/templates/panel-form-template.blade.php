@extends('layouts.app')
@section('content')

    @if(hasRoute('create') && isset($item))
        <a class="action-btn circle-btn"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{__("Add another one")}}"
           href="{{getRoute('create')}}"
        >
            <i class="ri-add-line"></i>
        </a>
    @else
        <a class="action-btn circle-btn"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{__("Show list")}}"
           href="{{getRoute('index',[])}}"
        >
            <i class="ri-list-view"></i>
        </a>
    @endif
    {{-- Only wrap the content in a form when the child view actually fills the
         "form" section. Views that build their own inline forms in
         "out-of-form" (e.g. the invoice edit page) would otherwise get a
         zero-field multipart form wrapping nothing. --}}
    @hasSection('form')
        <form
            @if(isset($item))
                id="model-form-edit"
                action="{{getRoute('update',$item->{$item->getRouteKeyName()})}}"
            @else
                id="model-form-create"
                action="{{getRoute('store')}}"
            @endif
              method="post" enctype="multipart/form-data">
            @csrf
            @if(isset($item))
                <input type="hidden" name="id" value="{{$item->id}}"/>
            @endif
            @yield('form')
        </form>
    @endif
    @yield('out-of-form')
@endsection
