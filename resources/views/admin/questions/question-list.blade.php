@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-questionnaire-line"></i>
    {{__("Questions list")}}
@endsection
@section('title')
    {{__("Questions list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    {{--    <option value="-"> - </option> --}}
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
