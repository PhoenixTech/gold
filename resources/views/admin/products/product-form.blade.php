@extends('layouts.app')

@section('title')
    @if(isset($item))
        {{__("Edit product")}} [{{$item->name}}]
    @else
        {{__("Add new product")}}
    @endif -
@endsection

@section('content')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark fs-18">
            <i class="ri-file-edit-line text-primary"></i>
            <span>{{ isset($item) ? __('Edit product') : __('Add new product') }}</span>
            @if(isset($item))
                <span class="badge bg-light text-dark border font-monospace fs-12">{{ $item->sku ?: '#'.$item->id }}</span>
            @endif
        </h4>
        <div class="d-flex align-items-center gap-2">
            @if(hasRoute('create') && isset($item))
                <a href="{{ getRoute('create') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="ri-add-line"></i>
                    <span>{{ __('Add another one') }}</span>
                </a>
            @endif
            <a href="{{ getRoute('index', []) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="ri-list-check-2"></i>
                <span>{{ __('Show list') }}</span>
            </a>
            <button type="submit" form="{{ isset($item) ? 'product-form-edit' : 'product-form-create' }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="ri-save-line"></i>
                <span>{{ __('Save product') }}</span>
            </button>
        </div>
    </div>

    <form
        @if(isset($item))
            id="product-form-edit"
        action="{{getRoute('update',$item->{$item->getRouteKeyName()})}}"
        @else
            id="product-form-create"
        action="{{getRoute('store')}}"
        @endif
        class="product-form pb-5"
        method="post" enctype="multipart/form-data">
        @csrf
        @if(isset($item))
            <input type="hidden" name="id" value="{{$item->id}}"/>
        @endif

        <ul class="steps product-form-tabs">
            <li data-tab="step1" class="active">
                <i class="ri-file-info-line"></i>
                <span>{{__("Product info")}}</span>
            </li>
            <li data-tab="step2">
                <i class="ri-funds-line"></i>
                <span>{{__("Pricing")}}</span>
            </li>
            <li data-tab="step3">
                <i class="ri-stack-line"></i>
                <span>{{__("Stock pieces")}}</span>
            </li>
            <li data-tab="step4">
                <i class="ri-image-2-line"></i>
                <span>{{__("Medias")}}</span>
            </li>
            <li data-tab="step5">
                <i class="ri-list-check-2"></i>
                <span>{{__("Additional data")}}</span>
            </li>
        </ul>

        @include('components.err')

        <div id="step-tabs">
            <div id="step1" class="active">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent fw-bold">
                        {{__("Product info")}}
                    </div>
                    <div class="card-body">
                        @include('admin.products.sub-pages.product-step1')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{__("Save product")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-next">
                            {{__("Next")}}
                        </button>
                    </div>
                </div>
            </div>

            <div id="step2">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent fw-bold">
                        {{__("Pricing")}}
                    </div>
                    <div class="card-body">
                        @include('admin.products.sub-pages.product-step-pricing')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{__("Save product")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-next">
                            {{__("Next")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-prev">
                            {{__("Previous")}}
                        </button>
                    </div>
                </div>
            </div>

            <div id="step3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent fw-bold">
                        {{__("Stock pieces")}}
                    </div>
                    <div class="card-body">
                        @include('admin.products.sub-pages.product-step-stock')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{__("Save product")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-next">
                            {{__("Next")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-prev">
                            {{__("Previous")}}
                        </button>
                    </div>
                </div>
            </div>

            <div id="step4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent fw-bold">
                        {{__("Medias")}}
                    </div>
                    <div class="card-body">
                        @include('admin.products.sub-pages.product-step2')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{__("Save product")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-next">
                            {{__("Next")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-prev">
                            {{__("Previous")}}
                        </button>
                    </div>
                </div>
            </div>

            <div id="step5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent fw-bold">
                        {{__("Additional data")}}
                    </div>
                    <div class="card-body">
                        @include('admin.products.sub-pages.product-step3')
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{__("Save product")}}
                        </button>
                        <button type="button" class="btn btn-outline-secondary step-prev">
                            {{__("Previous")}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <br>
    <br>
    @yield('out-of-form')
@endsection
@section('js-content')
    <script>
        var currentEditLink = '{{route('admin.product.edit','')}}/';
        var currentUpdateLink = '{{route('admin.product.update','')}}/';
    </script>
@endsection
