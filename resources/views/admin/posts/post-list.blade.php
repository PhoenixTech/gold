@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Posts list")}}
@endsection
@section('title')
    {{__("Posts list")}} -
@endsection
@section('filter')
    <input type="hidden" id="group-edit-url" value="{{route('admin.post.group-edit','')}}/">

    @php
        $selectedGroups = (array) request()->input('filter.group_id', []);
    @endphp
    <select name="filter[group_id][]" class="form-select form-select-sm w-auto" multiple style="min-width: 160px; max-height: 38px;" title="{{__('Hold Ctrl/Cmd to select multiple')}}">
        @foreach(\App\Models\Group::all(['id','name']) as $grp)
            <option value="{{$grp->id}}" @if(in_array($grp->id, $selectedGroups)) selected @endif>
                {{$grp->name}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
