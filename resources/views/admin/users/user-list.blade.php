@extends('admin.templates.panel-list-template')

@section('list-title')
    <i class="ri-user-3-line"></i>
    {{__("Users list")}}
@endsection
@section('title')
    {{__("Users list")}} -
@endsection
@section('filter')
    @php
        $selectedRoles = (array) request()->input('filter.role', []);
    @endphp
    <select name="filter[role][]" class="form-select form-select-sm w-auto" multiple style="min-width: 160px; max-height: 38px;">
        @foreach(\App\Models\User::$roles as $role)
            <option value="{{$role}}" @if(in_array($role, $selectedRoles)) selected @endif>
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
