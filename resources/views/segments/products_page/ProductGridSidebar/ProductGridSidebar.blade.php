@php
    $categoriesList = $categories ?? \App\Models\Category::where('hide', 0)->where(function($q) { $q->whereNull('parent_id')->orWhere('parent_id', 0); })->with(['children'])->withCount(['products' => function($q){ $q->where('status', 1); }])->get();
    $currentCategorySlug = request('category', isset($category) ? $category->slug : null);
    $activeCategory = $activeCategory ?? ($currentCategorySlug ? \App\Models\Category::where('slug', $currentCategorySlug)->orWhere('id', $currentCategorySlug)->first() : null);
    $currentSort = request('sort', 'latest');
    $currentSearch = request('q', '');
    $currentInStock = request()->boolean('in_stock') || request('only') === 'stock';
    $currentHasDiscount = request()->boolean('has_discount');
    $currentMinPrice = request('min_price', '');
    $currentMaxPrice = request('max_price', '');
    $hasActiveFilters = request()->hasAny(['q', 'category', 'in_stock', 'has_discount', 'min_price', 'max_price']) || ($currentSort && $currentSort !== 'latest');
    $productGridPart = \App\Models\Area::where('name','product-grid')->first()?->defPart() ?? 'segments.product_grid.ShivaProductGrid.ShivaProductGrid';
@endphp

<section class='ProductGridSidebar content live-setting py-4' data-live="{{$data->area_name.'_'.$data->part}}" id="product-list-view">
    <div class="{{gfx()['container']}}">

        <!-- Top Breadcrumb & Page Header -->
        <div class="products-header-wrapper mb-4">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb fs-13 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('client.welcome') }}" class="text-muted text-decoration-none hover-primary">{{ __("Home") }}</a></li>
                    <li class="breadcrumb-item {{ !$activeCategory ? 'active text-dark fw-bold' : '' }}">
                        @if($activeCategory)
                            <a href="{{ route('client.products') }}" class="text-muted text-decoration-none hover-primary">{{ __("Products") }}</a>
                        @else
                            {{ __("Products") }}
                        @endif
                    </li>
                    @if($activeCategory)
                        <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">{{ $activeCategory->name }}</li>
                    @endif
                </ol>
            </nav>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="page-title fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="ri-store-2-line text-primary"></i>
                        <span>
                            @if($activeCategory)
                                {{ $activeCategory->name }}
                            @elseif($currentSearch)
                                {{ __('Search results for ":q"', ['q' => $currentSearch]) }}
                            @else
                                {{ __("Products list") }}
                            @endif
                        </span>
                    </h5>
                    <p class="text-muted fs-13 mb-0">
                        {{ __("Showing :count products", ['count' => $products->total()]) }}
                    </p>
                </div>

                <!-- Mobile Filter Trigger Button -->
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-2 d-lg-none d-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="offcanvas" data-bs-target="#mobileFilterDrawer" aria-controls="mobileFilterDrawer">
                    <i class="ri-sound-module-line"></i>
                    <span>{{ __("Filters") }}</span>
                    @if($hasActiveFilters)
                        <span class="badge bg-white text-primary rounded-pill px-1.5 py-0.5 fs-11">✓</span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Top Category Explorer Strip -->
        @if($categoriesList->isNotEmpty())
            <div class="category-explorer-strip mb-4">
                <div class="category-chips-scroll d-flex align-items-center gap-2 pb-2">
                    <a href="{{ route('client.products', request()->except(['category', 'page'])) }}" class="category-chip-card d-flex align-items-center gap-2 text-decoration-none {{ !$currentCategorySlug ? 'active' : '' }}">
                        <div class="category-chip-icon">
                            <i class="ri-apps-2-line"></i>
                        </div>
                        <span class="category-chip-title">{{ __("All Products") }}</span>
                    </a>
                    @foreach($categoriesList as $cat)
                        @php $isCatActive = ($currentCategorySlug == $cat->slug || $currentCategorySlug == $cat->id); @endphp
                        <a href="{{ route('client.products', array_merge(request()->except(['category', 'page']), ['category' => $cat->slug])) }}" class="category-chip-card d-flex align-items-center gap-2 text-decoration-none {{ $isCatActive ? 'active' : '' }}">
                            <div class="category-chip-icon">
                                @if($cat->image)
                                    <img src="{{ $cat->imgUrl() }}" alt="{{ $cat->name }}" loading="lazy">
                                @else
                                    <i class="ri-folder-3-line"></i>
                                @endif
                            </div>
                            <span class="category-chip-title">{{ $cat->name }}</span>
                            @if($cat->products_count > 0)
                                <span class="category-chip-count">{{ $cat->products_count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content Layout (Sidebar + Product Grid) -->
        <div class="row g-4">
            <!-- Desktop Sidebar -->
            <div class="col-lg-4 col-xl-3 d-none d-lg-block">
                <div class="sticky-sidebar-wrapper">
                    @include('segments.products_page.ProductGridSidebar.inc.product-sidebar')
                </div>
            </div>

            <!-- Product Grid Column -->
            <div class="col-lg-8 col-xl-9">

                <!-- Sorting & Control Toolbar -->
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
                                <a href="{{ route('client.products', array_merge(request()->except(['sort', 'page']), ['sort' => $sortKey])) }}"
                                   class="sort-tab-btn btn btn-sm rounded-pill px-3 py-1 text-decoration-none {{ $currentSort === $sortKey ? 'btn-primary fw-bold active' : 'btn-light text-dark border-0' }}">
                                    {{ $sortLabel }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Mobile Sort Dropdown -->
                        <div class="d-flex d-md-none align-items-center gap-2 w-100 justify-content-between">
                            <div class="dropdown flex-grow-1">
                                <button class="btn btn-light border btn-sm rounded-pill dropdown-toggle w-100 text-start d-flex align-items-center justify-content-between px-3 py-1.5" type="button" data-bs-toggle="dropdown">
                                    <span><i class="ri-sort-desc me-1"></i> {{ $sortOptions[$currentSort] ?? __('Sort by') }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                    @foreach($sortOptions as $sortKey => $sortLabel)
                                        <li>
                                            <a class="dropdown-item fs-13 py-2 {{ $currentSort === $sortKey ? 'active bg-primary text-white' : '' }}" href="{{ route('client.products', array_merge(request()->except(['sort', 'page']), ['sort' => $sortKey])) }}">
                                                {{ $sortLabel }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <!-- Product Counts Summary -->
                        <div class="d-none d-md-block text-muted fs-12 ms-auto">
                            @if($products->total() > 0)
                                <span>{{ __('Showing :from-:to of :total', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Active Filter Badges -->
                @if($hasActiveFilters)
                    <div class="active-filter-chips d-flex align-items-center flex-wrap gap-2 mb-3">
                        <span class="text-muted fs-12 fw-medium">{{ __("Active filters") }}:</span>

                        @if($currentSearch)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('Search') }}: {{ $currentSearch }}</span>
                                <a href="{{ route('client.products', request()->except(['q', 'page'])) }}" class="text-primary text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($activeCategory)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('Category') }}: {{ $activeCategory->name }}</span>
                                <a href="{{ route('client.products', request()->except(['category', 'page'])) }}" class="text-primary text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentInStock)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('In-stock only') }}</span>
                                <a href="{{ route('client.products', request()->except(['in_stock', 'only', 'page'])) }}" class="text-success text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentHasDiscount)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>{{ __('Discounted only') }}</span>
                                <a href="{{ route('client.products', request()->except(['has_discount', 'page'])) }}" class="text-danger text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        @if($currentMinPrice || $currentMaxPrice)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill py-1.5 px-3 d-inline-flex align-items-center gap-1.5 fs-12">
                                <span>
                                    {{ __('Price') }}:
                                    @if($currentMinPrice) {{ __('From') }} {{ number_format($currentMinPrice) }} @endif
                                    @if($currentMaxPrice) {{ __('To') }} {{ number_format($currentMaxPrice) }} @endif
                                </span>
                                <a href="{{ route('client.products', request()->except(['min_price', 'max_price', 'page'])) }}" class="text-dark text-decoration-none hover-danger">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif

                        <a href="{{ route('client.products') }}" class="btn btn-link btn-sm text-danger text-decoration-none fs-12 p-0 ms-2 hover-underline">
                            {{ __("Clear All Filters") }}
                        </a>
                    </div>
                @endif

                <!-- Product Grid List -->
                @if($products->count() > 0)
                    <div class="row g-3 g-md-4">
                        @foreach($products as $product)
                            <div class="col-6 col-md-6 col-lg-4">
                                @include($productGridPart, compact('product'))
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="products-pagination-wrapper mt-5 d-flex justify-content-center">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @else
                    <!-- Modern Empty State -->
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
                        <div class="empty-products-icon-bg mx-auto mb-3">
                            <i class="ri-search-eye-line fs-1 text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">
                            {{ __("No products found matching your filters.") }}
                        </h5>
                        <p class="text-muted fs-14 mb-4 mx-auto" style="max-width: 480px;">
                            {{ __("Try adjusting your search or filters to find what you are looking for.") }}
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('client.products') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                                <i class="ri-refresh-line me-1"></i>
                                {{ __("Explore All Products") }}
                            </a>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <!-- Trust & Features Banner at Bottom -->
        <div class="products-trust-banner mt-5 p-4 rounded-4 bg-light border">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="trust-item">
                        <i class="ri-truck-line text-primary fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1 fs-14">{{ __("Fast & Secure Delivery") }}</h6>
                        <p class="text-muted fs-12 mb-0">{{ __("Delivery to your doorstep") }}</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-item">
                        <i class="ri-shield-check-line text-success fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1 fs-14">{{ __("Original Product Guarantee") }}</h6>
                        <p class="text-muted fs-12 mb-0">{{ __("100% genuine and verified") }}</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-item">
                        <i class="ri-bank-card-line text-warning fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1 fs-14">{{ __("Secure Online Payment") }}</h6>
                        <p class="text-muted fs-12 mb-0">{{ __("Connected to official bank gateways") }}</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-item">
                        <i class="ri-customer-service-2-line text-info fs-3"></i>
                        <h6 class="fw-bold mt-2 mb-1 fs-14">{{ __("24/7 Dedicated Support") }}</h6>
                        <p class="text-muted fs-12 mb-0">{{ __("We are here to help you") }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Mobile Filter Offcanvas Drawer -->
<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="mobileFilterDrawer" aria-labelledby="mobileFilterDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold fs-16 d-flex align-items-center gap-2" id="mobileFilterDrawerLabel">
            <i class="ri-filter-3-line text-primary"></i>
            {{ __("Filter products") }}
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body p-3">
        @include('segments.products_page.ProductGridSidebar.inc.product-sidebar')
    </div>
</div>

