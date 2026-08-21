@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-walk-line"></i>
    {{ __('Shop visits') }}
@endsection
@section('title')
    {{ __('Shop visits') }} -
@endsection
@section('filter')
@endsection
@section('bulk')
@endsection
@section('list-foot')
    <a href="{{ route('admin.shop-visit.export') }}" class="btn btn-outline-primary mt-3">
        <i class="ri-file-excel-2-line"></i>
        {{ __('Download Excel') }}
    </a>
@endsection
