@extends('website.inc.website-layout')

@section('title')
    {{$category->name}} - {{config('app.name')}}
@endsection

@php
    $bg = $category->bg ? $category->bgUrl() : null;
    $title = $category->name;
    $subtitle = $category->subtitle;
    $currentSort = request('sort', 'latest');
    $currentSearch = request('q', '');
    $currentInStock = request()->boolean('in_stock') || request('only') === 'stock';
    $currentHasDiscount = request()->boolean('has_discount');
    $currentMinPrice = request('min_price', '');
    $currentMaxPrice = request('max_price', '');
    $hasActiveFilters = request()->hasAny(['q', 'in_stock', 'has_discount', 'min_price', 'max_price']) || ($currentSort && $currentSort !== 'latest');
    $parallelCategories = method_exists($category, 'parallelCategories') ? $category->parallelCategories() : [];
@endphp

@section('content')
<div class="category-page-view">

    <!-- Parallax / Page Header -->
    @include('client.partials.parallax-header', ['title' => $title, 'subtitle' => $subtitle, 'bg' => $bg])

    <div class="{{gfx()['container']}}">

        <!-- Breadcrumbs -->
        @include('client.partials.breadcrumbs')

        <!-- Subcategories Grid (Only if subcategories exist) -->
        @if($category->children && $category->children->isNotEmpty())
            <section class="SubCategoriesGrid py-4 mb-4">
                <div class="sub-categories-wrapper">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <h5 class="fs-4 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="ri-folder-3-fill text-primary"></i>
                            <span>{{__("Sub categories")}}</span>
                        </h5>
                    </div>

                    <div class="row g-3 g-md-4">
                        @foreach($category->children as $subCat)
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="{{$subCat->webUrl()}}" class="sub-category-card card border-0 shadow-sm rounded-4 overflow-hidden d-block text-decoration-none transition-all">
                                    <div class="card-img-box position-relative overflow-hidden bg-dark" style="height: 200px;">
                                        <img src="{{$subCat->imgUrl()}}" alt="{{$subCat->name}}" class="card-img-bg w-100 h-100 object-fit-cover opacity-85 transition-all" loading="lazy">
                                        <div class="card-overlay-gradient position-absolute inset-0"></div>
                                        <div class="card-content-overlay position-absolute bottom-0 start-0 end-0 p-3 text-center text-white z-2">
                                            <h4 class="category-title fs-15 fw-bold text-white mb-1 text-shadow">
                                                {{$subCat->name}}
                                            </h4>
                                            <span class="badge bg-white-20 text-white rounded-pill px-2.5 py-0.5 fs-12 border border-white-30 backdrop-blur d-inline-flex align-items-center gap-1">
                                                <span>{{__("View category")}}</span>
                                                <i class="ri-arrow-left-s-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Category Products Layout (Sidebar + Products Grid) -->
        <section class="ProductGridHiddenSidebar py-4" id="product-list-view">
            <div class="row g-4">
                <!-- Desktop Sidebar -->
                <div class="col-lg-4 col-xl-3 d-none d-lg-block">
                    <div class="sticky-sidebar-wrapper">
                        @include('client.partials.product-sidebar', ['category' => $category])
                    </div>
                </div>

                <!-- Products Column -->
                <div class="col-lg-8 col-xl-9">
                    <!-- Sorting & Toolbar -->
                    <div class="products-toolbar card border-0 shadow-sm rounded-4 p-2.5 mb-4 bg-white">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <!-- Desktop Sort Tabs -->
                            <div class="d-none d-md-flex align-items-center gap-1.5 flex-wrap">
                                <span class="text-muted fs-13 ms-2 fw-medium d-flex align-items-center gap-1">
                                    <i class="ri-sort-desc"></i>
                                    {{ __("Sort by") }}:
                                </span>
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
                                       class="sort-tab-btn btn btn-sm rounded-pill px-3 py-1 text-decoration-none {{ $currentSort === $sortKey ? 'btn-primary fw-bold active' : 'btn-light text-dark border-0' }}">
                                        {{ $sortLabel }}
                                    </a>
                                @endforeach
                            </div>

                            <!-- Mobile Sort Dropdown & Filter Button -->
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
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="offcanvas" data-bs-target="#mobileFilterDrawer" aria-controls="mobileFilterDrawer">
                                    <i class="ri-sound-module-line"></i>
                                    <span>{{ __("Filters") }}</span>
                                    @if($hasActiveFilters)
                                        <span class="badge bg-white text-primary rounded-pill px-1.5 py-0.5 fs-11">✓</span>
                                    @endif
                                </button>
                            </div>

                            <!-- Desktop Product Counts -->
                            <div class="d-none d-md-block text-muted fs-12 ms-auto">
                                @if($products->total() > 0)
                                    <span>{{ __('Showing :from-:to of :total', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    @if($products->count() > 0)
                        <div class="row g-3 g-md-4">
                            @foreach($products as $product)
                                <div class="col-6 col-md-6 col-lg-4">
                                    @include('client.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="products-pagination-wrapper mt-5 d-flex justify-content-center">
                            {{ $products->withQueryString()->links() }}
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                            <div class="empty-products-icon-bg mx-auto mb-3">
                                <i class="ri-search-eye-line fs-1 text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">
                                {{ __("No products found in this category matching your filters.") }}
                            </h5>
                            <p class="text-muted fs-14 mb-4 mx-auto" style="max-width: 480px;">
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
        </section>

        <!-- Parallel / Other Categories -->
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
                                    <div class="card-img-box position-relative overflow-hidden bg-dark" style="height: 200px;">
                                        <img src="{{$subCat->imgUrl()}}" alt="{{$subCat->name}}" class="card-img-bg w-100 h-100 object-fit-cover opacity-85 transition-all" loading="lazy">
                                        <div class="card-overlay-gradient position-absolute inset-0"></div>
                                        <div class="card-content-overlay position-absolute bottom-0 start-0 end-0 p-3 text-center text-white z-2">
                                            <h4 class="category-title fs-15 fw-bold text-white mb-1 text-shadow">
                                                {{$subCat->name}}
                                            </h4>
                                            <span class="badge bg-white-20 text-white rounded-pill px-2.5 py-0.5 fs-12 border border-white-30 backdrop-blur d-inline-flex align-items-center gap-1">
                                                <span>{{__("View category")}}</span>
                                                <i class="ri-arrow-left-s-line"></i>
                                            </span>
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
</div>

<!-- Mobile Filter Offcanvas Drawer -->
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
