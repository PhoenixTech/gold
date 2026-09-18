@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-team-line"></i>
    {{__("Customers list")}}
@endsection
@section('title')
    {{__("Customers list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
@endsection
