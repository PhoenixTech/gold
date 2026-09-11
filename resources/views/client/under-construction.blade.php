@extends('website.inc.website-layout')
@section('title')
    {{$title}}
@endsection
@section('content')
    <div class="container text-center py-5 my-5">
        <img src="{{ asset('assets/default/under-construction.svg') }}" alt="{{ $title }}" class="img-fluid mb-4" style="max-height: 280px;">
        <h2 class="fw-bold">{{ $title }}</h2>
    </div>
@endsection
