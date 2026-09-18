@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-folder-shared-line"></i>
    {{__("Groups list")}}
@endsection
@section('title')
    {{__("Groups list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
@endsection
@section('list-actions')
    <a href="{{getRoute('sort')}}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="ri-sort-asc"></i>
        <span>{{__("Sort")}}</span>
    </a>
@endsection
