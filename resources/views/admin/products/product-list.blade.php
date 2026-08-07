@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Products list")}}
@endsection
@section('title')
    {{__("Products list")}} -
@endsection
@section('filter')
    <input type="hidden" id="category-edit-url" value="{{route('admin.product.category-edit','')}}/">

    @php
        $selectedCats = (array) request()->input('filter.category_id', []);
    @endphp
    <select name="filter[category_id][]" class="form-select form-select-sm w-auto" multiple style="min-width: 160px; max-height: 38px;" title="{{__('Hold Ctrl/Cmd to select multiple')}}">
        @foreach(\App\Models\Category::all(['id','name']) as $cat)
            <option value="{{$cat->id}}" @if(in_array($cat->id, $selectedCats)) selected @endif>
                {{$cat->name}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
