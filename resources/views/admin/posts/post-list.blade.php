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

    <select name="filter[group_id]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All groups")}}</option>
        @foreach(\App\Models\Group::all(['id','name']) as $grp)
            <option value="{{$grp->id}}" @if(request()->input('filter.group_id') == $grp->id) selected @endif>
                {{$grp->name}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
    <option value="publish"> {{__("Publish")}} </option>
    <option value="draft"> {{__("Draft")}} </option>
@endsection
