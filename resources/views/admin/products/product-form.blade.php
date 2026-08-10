@extends('layouts.app')

@section('title')
    @if(isset($item))
        {{__("Edit product")}} [{{$item->name}}]
    @else
        {{__("Add new product")}}
    @endif -
@endsection

@section('content')

    @if(hasRoute('create') && isset($item))
        <a class="action-btn circle-btn"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{__("Add another one")}}"
           href="{{getRoute('create')}}"
        >
            <i class="ri-add-line"></i>
        </a>
    @else
        <a class="action-btn circle-btn"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-custom-class="custom-tooltip"
           data-bs-title="{{__("Show list")}}"
           href="{{getRoute('index',[])}}"
        >
            <i class="ri-list-view"></i>
        </a>
    @endif

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
