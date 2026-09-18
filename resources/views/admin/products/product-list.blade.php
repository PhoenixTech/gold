@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-vip-diamond-line"></i>
    {{__("Products list")}}
@endsection
@section('title')
    {{__("Products list")}} -
@endsection
@section('filter')
    <input type="hidden" id="category-edit-url" value="{{route('admin.product.category-edit','')}}/">

    <select name="filter[category_id]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All categories")}}</option>
        @foreach(\App\Models\Category::all(['id','name']) as $cat)
            <option value="{{$cat->id}}" @if(request()->input('filter.category_id') == $cat->id) selected @endif>
                {{$cat->name}}
            </option>
        @endforeach
    </select>

    <select name="filter[low_stock]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All stock levels")}}</option>
        <option value="1" @if(request()->input('filter.low_stock') === '1') selected @endif>
            {{__("Below minimum stock")}}
        </option>
    </select>

    <select name="filter[below_buy_price]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All price states")}}</option>
        <option value="1" @if(request()->input('filter.below_buy_price') === '1') selected @endif>
            {{__("Below purchase price")}}
        </option>
    </select>
@endsection
@section('bulk')
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
