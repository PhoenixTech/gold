@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-video-line"></i>
    {{__("Clips list")}}
@endsection
@section('title')
    {{__("Clips list")}} -
@endsection
@section('filter')
    {{--  Other filters --}}
@endsection
@section('bulk')
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
