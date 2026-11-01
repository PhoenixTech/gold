@extends('website.inc.website-layout')

@section('title')
    {{ $title ?? $category->name }} - {{ config('app.name') }}
@endsection

@php
    $bg = $category->bg ? $category->bgUrl() : null;
    $title = $title ?? $category->name;
    $subtitle = $subtitle ?? $category->subtitle;
    $currentSort = request('sort', 'latest');
    $currentSearch = request('q', '');
    $currentMetal = request('metal', '');
    $currentTargetGroup = request('target_group', '');
    $currentInStock = request()->boolean('in_stock') || request('only') === 'stock';
    $currentHasDiscount = request()->boolean('has_discount');
    $currentMinPrice = request('min_price', '');
    $currentMaxPrice = request('max_price', '');
    $hasActiveFilters = request()->hasAny(['q', 'metal', 'target_group', 'in_stock', 'has_discount', 'min_price', 'max_price']) || ($currentSort && $currentSort !== 'latest');
    $parallelCategories = method_exists($category, 'parallelCategories') ? $category->parallelCategories() : [];
@endphp

@section('content')
<section class="ProductGridSidebar py-4" id="product-list-view">
    <div class="{{gfx()['container']}}">

        <div class="products-header-wrapper mb-4">
            @include('client.partials.breadcrumbs')

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="page-title fw-bold text-dark mb-1 d-flex align-items-center gap-2 fs-20">
                        <i class="ri-folder-3-line text-primary"></i>
                        <span>
                            @if($currentSearch)
                                {{ __('Search results for ":q"', ['q' => $currentSearch]) }}
                            @else
                                {{ $title }}
                            @endif
                        </span>
                    </h1>
                    @if(!empty(trim($subtitle ?? '')))
                        <p class="text-muted fs-13 mb-1">{{ $subtitle }}</p>
                    @endif
                    <p class="text-muted fs-13 mb-0">
                        {{ __("Showing :count products", ['count' => $products->total()]) }}
                    </p>
                </div>

                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-2 d-lg-none d-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="offcanvas" data-bs-target="#mobileFilterDrawer" aria-controls="mobileFilterDrawer">
                    <i class="ri-sound-module-line"></i>
                    <span>{{ __("Filters") }}</span>
                    @if($hasActiveFilters)
                        <span class="badge bg-white text-primary rounded-pill px-1.5 py-0.5 fs-11">✓</span>
                    @endif
                </button>
            </div>
        </div>

        @if($category->children && $category->children->isNotEmpty())
            <section class="SubCategoriesGrid py-3 mb-4">
                <div class="sub-categories-wrapper">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h5 class="fs-5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="ri-folder-3-fill text-primary"></i>
                            <span>{{__("Sub categories")}}</span>
                        </h5>
                    </div>

                    <div class="row g-3 g-md-4">
                        @foreach($category->children as $subCat)
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="{{$subCat->webUrl()}}" class="sub-category-card card border-0 shadow-sm rounded-4 overflow-hidden d-block text-decoration-none transition-all">
                                    <div class="card-img-box position-relative overflow-hidden bg-dark" style="height: 140px;">
                                        <img src="{{$subCat->imgUrl()}}" alt="{{$subCat->name}}" class="card-img-bg w-100 h-100 object-fit-cover opacity-85 transition-all" loading="lazy">
                                        <div class="card-overlay-gradient position-absolute inset-0"></div>
                                        <div class="card-content-overlay position-absolute bottom-0 start-0 end-0 p-2.5 text-center text-white z-2">
                                            <h4 class="category-title fs-14 fw-bold text-white mb-0 text-shadow">
                                                {{$subCat->name}}
                                            </h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <div class="row g-4">
            <div class="col-lg-4 col-xl-3 d-none d-lg-block">
                <div class="sticky-sidebar-wrapper">
                    @include('client.partials.product-sidebar', ['category' => $category])
                </div>
            </div>

            <div class="col-lg-8 col-xl-9">
                <div class="products-toolbar card border-0 shadow-xs rounded-4 p-2 mb-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-none d-md-flex align-items-center gap-1.5 flex-wrap">
                            @php
                                $sortOptions = [
                                    'latest' => __('Latest'),
                                    'cheap' => __('Cheapest'),
                                    'expensive' => __('Most Expensive'),
                                    'sale' => __('Best Sellers'),
                                    'popular' => __('Most Popular'),
                                ];
                            @endphp
                            @foreach($sortOptions as $sortKey => $sortLabel)
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['sort', 'page']), ['sort' => $sortKey])) }}"
                                   class="sort-tab-btn btn btn-sm rounded-pill px-3 py-1 text-decoration-none fs-12 {{ $currentSort === $sortKey ? 'btn-primary fw-semibold active' : 'btn-light text-muted border-0' }}">
                                    {{ $sortLabel }}
                                </a>
                            @endforeach
                        </div>

                        <div class="d-flex d-md-none align-items-center gap-2 w-100 justify-content-between">
                            <div class="dropdown flex-grow-1">
                                <button class="btn btn-light border btn-sm rounded-pill dropdown-toggle w-100 text-start d-flex align-items-center justify-content-between px-3 py-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="true">
                                    <span><i class="ri-sort-desc me-1"></i> {{ $sortOptions[$currentSort] ?? __('Sort by') }}</span>
                                </button>
                                <ul class="dropdown-menu shadow-sm border rounded-3 w-100 py-1">
                                    @foreach($sortOptions as $sortKey => $sortLabel)
                                        <li>
                                            <a class="dropdown-item fs-13 py-2 d-flex align-items-center justify-content-between {{ $currentSort === $sortKey ? 'active bg-primary text-white fw-bold' : '' }}" href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['sort', 'page']), ['sort' => $sortKey])) }}">
                                                <span>{{ $sortLabel }}</span>
                                                @if($currentSort === $sortKey)
                                                    <i class="ri-check-line"></i>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="d-none d-md-block text-muted fs-12 ms-auto px-2">
                            @if($products->total() > 0)
                                <span>{{ __('Showing :from-:to of :total', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($hasActiveFilters)
                    <div class="active-filter-chips d-flex align-items-center flex-wrap gap-2 mb-3">
                        @if($currentSearch)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ $currentSearch }}</span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['q', 'page']))) }}" class="text-primary text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentMetal)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ $currentMetal === 'gold' ? __('Gold') : __('Silver') }}</span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['metal', 'page']))) }}" class="text-primary text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentTargetGroup)
                            @php
                                $tgLabels = ['women' => __('Women'), 'men' => __('Men'), 'children' => __('Children'), 'unisex' => __('Unisex')];
                            @endphp
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ $tgLabels[$currentTargetGroup] ?? $currentTargetGroup }}</span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['target_group', 'page']))) }}" class="text-primary text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentInStock)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('In-stock only') }}</span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['in_stock', 'only', 'page']))) }}" class="text-success text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentHasDiscount)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('Discounted only') }}</span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['has_discount', 'page']))) }}" class="text-danger text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentMinPrice || $currentMaxPrice)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>
                                    @if($currentMinPrice) {{ __('From') }} {{ number_format($currentMinPrice) }} @endif
                                    @if($currentMaxPrice) {{ __('To') }} {{ number_format($currentMaxPrice) }} @endif
                                </span>
                                <a href="{{ route('client.category', array_merge(['category' => $category->slug], request()->except(['min_price', 'max_price', 'page']))) }}" class="text-dark text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        <a href="{{ route('client.category', $category->slug) }}" class="btn btn-link btn-sm text-danger text-decoration-none fs-12 p-0 ms-2 hover-underline">
                            {{ __("Clear All Filters") }}
                        </a>
                    </div>
                @endif

                @if($products->count() > 0)
                    <div class="row g-3 g-md-4">
                        @foreach($products as $product)
                            <div class="col-6 col-md-6 col-lg-4">
                                @include('client.partials.product-card', ['product' => $product, 'eager' => $loop->index < 4])
                            </div>
                        @endforeach
                    </div>

                    <div class="products-pagination-wrapper mt-5 d-flex justify-content-center">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @else
                    <div class="card border-0 shadow-xs rounded-4 p-5 text-center my-4 bg-white">
                        <div class="empty-products-icon-bg mx-auto mb-3">
                            <i class="ri-search-eye-line fs-1 text-primary"></i>
                        </div>
                        <h2 class="fw-bold text-dark fs-18 mb-2">
                            {{ __("No products found in this category matching your filters.") }}
                        </h2>
                        <p class="text-muted fs-14 mb-4 max-w-md mx-auto">
                            {{ __("Try adjusting your search or filters to find what you are looking for.") }}
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('client.category', $category->slug) }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                                <i class="ri-refresh-line me-1"></i>
                                {{ __("Reset Category Filters") }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if(count($parallelCategories) > 0)
            <section class="ParallelCategoriesGrid py-5 border-top">
                <div class="parallel-categories-wrapper">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <h5 class="fs-4 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="ri-grid-fill text-primary"></i>
                            <span>{{ __('Other categories') }}</span>
                        </h5>
                    </div>

                    <div class="row g-3 g-md-4">
                        @foreach($parallelCategories as $subCat)
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="{{$subCat->webUrl()}}" class="parallel-category-card card border-0 shadow-sm rounded-4 overflow-hidden d-block text-decoration-none transition-all">
                                    <div class="card-img-box position-relative overflow-hidden bg-dark" style="height: 140px;">
                                        <img src="{{$subCat->imgUrl()}}" alt="{{$subCat->name}}" class="card-img-bg w-100 h-100 object-fit-cover opacity-85 transition-all" loading="lazy">
                                        <div class="card-overlay-gradient position-absolute inset-0"></div>
                                        <div class="card-content-overlay position-absolute bottom-0 start-0 end-0 p-2.5 text-center text-white z-2">
                                            <h4 class="category-title fs-14 fw-bold text-white mb-0 text-shadow">
                                                {{$subCat->name}}
                                            </h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    </div>
</section>

<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="mobileFilterDrawer" aria-labelledby="mobileFilterDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold fs-16 d-flex align-items-center gap-2" id="mobileFilterDrawerLabel">
            <i class="ri-filter-3-line text-primary"></i>
            {{ __("Filter products") }}
        </h5>
        <button type="button" class="btn btn-sm btn-light rounded-circle d-flex align-items-center justify-content-center p-0 shadow-xs" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}" style="width: 34px; height: 34px;">
            <i class="ri-close-line fs-20 text-dark"></i>
        </button>
    </div>
    <div class="offcanvas-body p-3">
        @include('client.partials.product-sidebar', ['category' => $category])
    </div>
</div>
@endsection
