@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Invoices list")}}
@endsection

@section('top-content')
    <div class="alert alert-info border border-info-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-2.5">
            <i class="ri-dashboard-2-line text-info fs-3"></i>
            <div>
                <strong class="d-block text-dark">{{ __('Manager dashboard') }}</strong>
                <span class="text-muted fs-13">
                    {{ __('Clinic-style daily workflow table for tracking and processing active orders.') }}
                </span>
            </div>
        </div>
        <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-info text-dark fw-bold px-3">
            <i class="ri-dashboard-2-line me-1"></i>{{ __('Order board') }}
        </a>
    </div>
@endsection
@section('title')
    {{__("Invoices list")}} -
@endsection
@section('filter')
    <select name="filter[status]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All statuses")}}</option>
        @foreach(\App\Models\Invoice::adminFilterStatuses() as $st)
            <option value="{{$st}}" @if(request()->input('filter.status') == $st) selected @endif>
                {{__($st)}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
@endsection
