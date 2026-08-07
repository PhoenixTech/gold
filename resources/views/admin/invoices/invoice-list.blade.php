@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Invoices list")}}
@endsection
@section('title')
    {{__("Invoices list")}} -
@endsection
@section('filter')
    @php
        $selectedStatus = (array) request()->input('filter.status', []);
    @endphp
    <select name="filter[status][]" class="form-select form-select-sm w-auto" multiple style="min-width: 160px; max-height: 38px;">
        @foreach(\App\Models\Invoice::$invoiceStatus as $st)
            <option value="{{$st}}" @if(in_array($st, $selectedStatus)) selected @endif>
                {{__($st)}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
@endsection
