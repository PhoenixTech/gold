@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Invoices list")}}
@endsection
@section('title')
    {{__("Invoices list")}} -
@endsection
@section('filter')
    <select name="filter[status]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All statuses")}}</option>
        @foreach(\App\Models\Invoice::$invoiceStatus as $st)
            <option value="{{$st}}" @if(request()->input('filter.status') == $st) selected @endif>
                {{__($st)}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
@endsection
