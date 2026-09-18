@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-price-tag-3-line"></i>
    {{__("Tags list")}}
@endsection
@section('title')
    {{__("Tags list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
@endsection
