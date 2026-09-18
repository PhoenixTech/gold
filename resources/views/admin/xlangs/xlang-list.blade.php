@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-global-line"></i>
    {{__("Languages list")}}
@endsection
@section('title')
    {{__("Languages list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
@endsection
@section('list-actions')
    <a href="{{getRoute('translate')}}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="ri-translate-2"></i>
        <span>{{__("Translate")}}</span>
    </a>
@endsection
