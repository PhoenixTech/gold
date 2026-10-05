@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-lightbulb-flash-fill"></i>
    {{__("Campaigns")}}
@endsection

@section('title')
    {{__("Campaigns list")}} -
@endsection

@section('list-actions')
    <span class="badge bg-light text-dark border border-secondary-subtle">
        <i class="ri-layout-grid-line me-0.5"></i>
        {{__("Each campaign fills the 12th cell of the home page grid, one per gold/silver tab.")}}
    </span>
@endsection

@section('top-content')
    <div class="alert alert-info border border-info-subtle shadow-sm d-flex align-items-start justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
        <i class="ri-information-line fs-5"></i>
        <div class="flex-grow-1">
            {{__("There is exactly one campaign slot per tab. When two campaigns overlap, the higher priority one wins — check the priority column before scheduling.")}}
        </div>
    </div>
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