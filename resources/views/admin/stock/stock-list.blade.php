@extends('admin.templates.panel-list-template')

@section('title')
    {{ __('Stock inventory') }} -
@endsection

@section('list-title')
    <i class="ri-archive-stack-fill text-primary"></i>
    {{ __('Stock inventory') }}
@endsection

@section('top-content')
    <div class="row g-3 mb-4">
        <!-- Total Stock Count Box -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-primary-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-stack-line text-primary me-1"></i>{{ __('Total stock count') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($stockStats['total_count'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('pieces') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1 d-flex align-items-center gap-2">
                            <span><i class="ri-coins-line text-warning me-0.5"></i>{{ __('Gold') }}: <b>{{ number_format($stockStats['gold_count'] ?? 0) }}</b></span>
                            <span>·</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-0.5"></i>{{ __('Silver') }}: <b>{{ number_format($stockStats['silver_count'] ?? 0) }}</b></span>
                        </div>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-stack-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Stock Weight Box -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-warning-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-scales-3-line text-warning me-1"></i>{{ __('Total stock weight') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('g') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1 d-flex align-items-center gap-2">
                            <span><i class="ri-coins-line text-warning me-0.5"></i>{{ __('Gold') }}: <b>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['gold_weight'] ?? 0) }}</b> {{ __('g') }}</span>
                            <span>·</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-0.5"></i>{{ __('Silver') }}: <b>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['silver_weight'] ?? 0) }}</b> {{ __('g') }}</span>
                        </div>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-scales-3-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- In-stock Products Count -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-info-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-vip-diamond-line text-info me-1"></i>{{ __('In-stock products') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($stockStats['in_stock_products_count'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('products') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1">
                            <span>{{ __('Active products currently in stock') }}</span>
                        </div>
                    </div>
                    <div class="bg-info-subtle text-info rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-store-3-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Estimated Inventory Value -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-success-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-money-dollar-circle-line text-success me-1"></i>{{ __('Total inventory value') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($stockStats['total_value'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('Toman') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1">
                            <span>{{ __('Live calculated retail value') }}</span>
                        </div>
                    </div>
                    <div class="bg-success-subtle text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-bank-card-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('filter')
    <select name="filter[category_id]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All categories")}}</option>
        @foreach(\App\Models\Category::all(['id','name']) as $cat)
            <option value="{{$cat->id}}" @if(request()->input('filter.category_id') == $cat->id) selected @endif>
                {{$cat->name}}
            </option>
        @endforeach
    </select>

    <select name="filter[metal_type]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All metals")}}</option>
        <option value="gold" @if(request()->input('filter.metal_type') === 'gold') selected @endif>{{__("Gold")}}</option>
        <option value="silver" @if(request()->input('filter.metal_type') === 'silver') selected @endif>{{__("Silver")}}</option>
    </select>

    <select name="filter[low_stock]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All stock levels")}}</option>
        <option value="1" @if(request()->input('filter.low_stock') === '1') selected @endif>
            {{__("Below minimum stock")}}
        </option>
    </select>

    <select name="filter[below_buy_price]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All price states")}}</option>
        <option value="1" @if(request()->input('filter.below_buy_price') === '1') selected @endif>
            {{__("Below purchase price")}}
        </option>
    </select>
@endsection
