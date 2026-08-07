@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Tickets list")}}
@endsection
@section('title')
    {{__("Tickets list")}} -
@endsection
@section('filter')
    @php
        $selectedTicketStatus = (array) request()->input('filter.status', []);
    @endphp
    <select name="filter[status][]" class="form-select form-select-sm w-auto" multiple style="min-width: 160px; max-height: 38px;">
        @foreach(\App\Models\Ticket::$ticket_statuses as $st)
            <option value="{{$st}}" @if(in_array($st, $selectedTicketStatus)) selected @endif>
                {{__($st)}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
    <option value="close"> {{__("Close")}} </option>
    <option value="pending"> {{__("Pending")}} </option>
    <option value="answered"> {{__("Answered")}} </option>
@endsection
