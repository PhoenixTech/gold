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
    <option value="export">{{ __('Export CSV') }}</option>
@endsection
@section('list-actions')
    <a href="{{ route('admin.shop-visit.export') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="ri-file-excel-2-line"></i>
        <span>{{ __('Download Excel') }}</span>
    </a>
@endsection
