@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-attachment-line"></i>
    {{__("Attachments list")}}
@endsection
@section('title')
    {{__("Attachments list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
@endsection
