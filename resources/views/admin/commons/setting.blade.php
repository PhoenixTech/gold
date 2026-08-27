@extends('layouts.app')

@section('title')
    {{__("Setting")}} -
@endsection

@section('content')
    <div class="mb-5 pb-5" id="setting-page">

        {{-- Action bar --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <h1 class="fs-4 mb-0 me-auto">
                <i class="ri-equalizer-line"></i>
                {{__("Manage settings")}}
            </h1>

            @if(auth()->user()->hasRole('developer'))
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#setting-create-modal">
                    <i class="ri-add-line"></i>
                    {{__("Add new setting")}}
                </button>
            @endif

            <a href="{{ route('admin.setting.cache-clear') }}"
               class="btn btn-outline-warning"
               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
               data-bs-title="{{__("Clear caches")}}"
               onclick="return confirm('{{__("Are you sure?")}}')">
                <i class="ri-delete-bin-line"></i>
                {{__("Clear caches")}}
            </a>

            <button type="submit" form="setting-form" class="btn btn-primary">
                <i class="ri-save-3-line"></i>
                {{__("Save changes")}}
            </button>

            @if(config('app.env') == 'production')
                <button type="submit" form="setting-form" name="build" value="1" class="btn btn-outline-primary">
                    <i class="ri-hammer-line"></i>
                    {{__("Save and build")}}
                </button>
            @endif
        </div>

        <form id="setting-form" action="{{route('admin.setting.update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="item-list">
                <div class="p-3 pb-0">
                    @include('components.err')

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs setting-tabs" role="tablist">
                        @foreach($tabs as $i => $tab)
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link @if($i === 0) active @endif"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-{{$tab['id']}}"
                                    type="button"
                                    role="tab"
                                >
                                    <i class="{{$tab['icon']}}"></i>
                                    {{$tab['label']}}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Panes --}}
                <div class="tab-content p-3">
                    @foreach($tabs as $i => $tab)
                        <div
                            class="tab-pane fade @if($i === 0) show active @endif"
                            id="tab-{{$tab['id']}}"
                            role="tabpanel"
                        >
                            @if($tab['intro'])
                                <div class="alert alert-info d-flex align-items-start gap-2 py-2">
                                    <i class="ri-information-line fs-5 mt-1"></i>
                                    <div>{{$tab['intro']}}</div>
                                </div>
                            @endif
                            <div class="row g-3">
                                @foreach($tab['settings'] as $setting)
                                    @include('components.setting-field')
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    @if(auth()->user()->hasRole('developer'))
        {{-- Add new setting --}}
        <div class="modal fade" id="setting-create-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="{{route('admin.setting.store')}}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="ri-add-line"></i>
                                {{__("Add new setting")}}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{__('Close')}}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label for="section">{{__('Section')}}</label>
                                <input name="section" type="text"
                                       list="known-sections"
                                       class="form-control @error('section') is-invalid @enderror"
                                       placeholder="{{__('Section')}}"
                                       value="{{old('section')}}"/>
                                <datalist id="known-sections">
                                    <option value="General"></option>
                                    <option value="SEO"></option>
                                    <option value="Media"></option>
                                    <option value="SMS"></option>
                                    <option value="theme"></option>
                                </datalist>
                            </div>

                            <div class="form-group mb-3">
                                <label for="type">{{__('Type')}}</label>
                                <select name="type" id="type"
                                        class="form-control @error('type') is-invalid @enderror">
                                    @foreach(\App\Models\Setting::$settingTypes as $type)
                                        <option value="{{$type}}"
                                                @if (old('type') == $type ) selected @endif >{{__($type)}} </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="title">{{__('Title')}}</label>
                                <input name="title" type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       placeholder="{{__('Title')}}"
                                       value="{{old('title')}}"/>
                            </div>

                            <div class="form-group mb-3">
                                <label for="key">{{__('Key')}}</label>
                                <input name="key" type="text"
                                       class="form-control @error('key') is-invalid @enderror"
                                       placeholder="{{__('Key')}}" value="{{old('key')}}"/>
                            </div>

                            <div class="form-group mb-3">
                                <label for="size">{{__('Size')}}</label>
                                <input name="size" type="number"
                                       class="form-control @error('size') is-invalid @enderror"
                                       placeholder="{{__('Size')}}" value="{{old('size',12)}}"/>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{__('Cancel')}}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{__('Add to setting')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
