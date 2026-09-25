@extends('layouts.app')

@section('content')
    <div id="sort-control">
        <ol class="ol-sortable">
            @foreach($items as $item)
                <li data-id="{{$item->id}}" class="p-2">
                    <i class="ri-drag-move-2-line"></i>
                    {{$item->name}}
                </li>
            @endforeach
        </ol>
    </div>
    <input type="hidden" id="sort-data">

    <button
        data-link="{{getRoute('sort-save')}}"
        id="save-sort"
        class="action-btn circle-btn"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-custom-class="custom-tooltip"
        data-bs-title="{{__("Save")}}"
    >
        <i class="ri-save-2-line"></i>
    </button>
@endsection
