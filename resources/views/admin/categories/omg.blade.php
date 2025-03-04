@extends('layouts.app')
@section('title')
    {{__("OMG category")}} -
@endsection
@section('content')
    <form action="{{route('admin.category.omg.save')}}" method="post" id="dang">
        @csrf
        <div class="container">
            <div class="form-group">
                <label for="table">
                    {{__('Category Table')}}
                </label>
                <textarea name="table" class="ckeditorx @error('table') is-invalid @enderror"
                          placeholder="{{__('Description Table')}}"
                          id="table"
                          rows="8">{{old('table',$item->table??"<ul> <li> Mobile </li> <li> Desktop </li> </ul>")}}</textarea>
            </div>
            <div class="py-2">
                <input type="submit" value="{{__("Save")}}" class="btn btn-secondary w-100">
            </div>
        </div>
    </form>
@endsection

@section('js-content')

    <script>
        let isInit = false;
        document.addEventListener('DOMContentLoaded', function () {
            if (!isInit) {
                document.querySelector('#dang').addEventListener('submit', function (e) {
                    if (!confirm('this very dangours, are you sure?')) {
                        e.preventDefault();
                    }
                });
                isInit = true;
            }
        });
    </script>
@endsection
