@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Users list")}}
@endsection
@section('title')
    {{__("Users list")}} -
@endsection
@section('filter')
    <select name="filter[role]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All roles")}}</option>
        @foreach(\App\Models\User::$roles as $role)
            <option value="{{$role}}" @if(request()->input('filter.role') == $role) selected @endif>
                {{__($role)}}
            </option>
        @endforeach
    </select>
@endsection
@section('bulk')
    @foreach(\App\Models\User::$roles as $role)
        <option value="role.{{$role}}"> {{__("Set")}} {{__("$role")}} </option>
    @endforeach
@endsection
