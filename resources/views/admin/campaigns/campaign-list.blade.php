@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-advertisement-fill"></i>
    {{__("Campaigns")}}
@endsection

@section('title')
    {{__("Campaigns list")}} -
@endsection

@section('filter')
    <select name="filter[status]" class="form-select form-select-sm w-auto" style="min-width: 160px;">
        <option value="">{{__("All statuses")}}</option>
        @foreach(\App\Enums\CampaignStatus::cases() as $case)
            <option value="{{$case->value}}" @if(request('filter.status') === $case->value) selected @endif>{{$case->label()}}</option>
        @endforeach
    </select>
@endsection

@section('bulk')
    <option value="end-now">{{__("End live campaigns now")}}</option>
@endsection
