@php
    $currentCategorySlug = request('category', isset($category) ? $category->slug : null);
    $currentSearch = request('q', '');
    $currentInStock = request()->boolean('in_stock') || request('only') === 'stock';
    $currentHasDiscount = request()->boolean('has_discount');
    $currentMinPrice = request('min_price', '');
    $currentMaxPrice = request('max_price', '');
    $currentSort = request('sort', 'latest');
    $categoriesList = $categories ?? \App\Models\Category::where('hide', 0)->where(function($q) { $q->whereNull('parent_id')->orWhere('parent_id', 0); })->with(['children' => function($q){ $q->where('hide', 0); }])->withCount(['products' => function($q){ $q->where('status', 1); }])->get();
@endphp

<div class="product-filters-sidebar">
    <form action="{{ route('client.products') }}" method="get" id="productFilterForm" class="filter-form">
        @if($currentSort && $currentSort !== 'latest')
            <input type="hidden" name="sort" value="{{ $currentSort }}">
        @endif

        <!-- Search Filter Block -->
        <div class="filter-card mb-3">
            <div class="filter-card-header">
                <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ri-search-2-line text-primary"></i>
                    <span>{{ __("Search") }}</span>
                </h6>
            </div>
            <div class="filter-card-body">
                <div class="search-input-wrapper position-relative">
                    <input type="search" name="q" value="{{ $currentSearch }}" class="form-control form-control-sm rounded-pill pe-5 ps-3" placeholder="{{ __('Search in products...') }}">
                    <button type="submit" class="btn btn-sm btn-primary rounded-circle position-absolute top-50 end-0 translate-middle-y me-1 p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" aria-label="{{ __('Search') }}">
                        <i class="ri-search-line fs-14"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toggles Block (In-Stock & Discount) -->
        <div class="filter-card mb-3">
            <div class="filter-card-body p-3">
                <div class="form-check form-switch d-flex align-items-center justify-content-between mb-2.5 ps-0">
                    <label class="form-check-label fw-medium text-dark cursor-pointer fs-13 mb-0" for="filterInStock">
                        <i class="ri-checkbox-circle-line text-success me-1"></i>
                        {{ __("In-stock only") }}
                    </label>
                    <input class="form-check-input ms-0 cursor-pointer" type="checkbox" name="in_stock" value="1" id="filterInStock" {{ $currentInStock ? 'checked' : '' }} onchange="this.form.submit()">
                </div>
                <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                    <label class="form-check-label fw-medium text-dark cursor-pointer fs-13 mb-0" for="filterDiscount">
                        <i class="ri-percent-line text-danger me-1"></i>
                        {{ __("Discounted only") }}
                    </label>
                    <input class="form-check-input ms-0 cursor-pointer" type="checkbox" name="has_discount" value="1" id="filterDiscount" {{ $currentHasDiscount ? 'checked' : '' }} onchange="this.form.submit()">
                </div>
            </div>
        </div>

        <!-- Categories Tree Block -->
        <div class="filter-card mb-3">
            <div class="filter-card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ri-folder-3-line text-primary"></i>
                    <span>{{ __("Categories") }}</span>
                </h6>
                @if($currentCategorySlug)
                    <a href="{{ route('client.products', request()->except(['category', 'page'])) }}" class="fs-12 text-danger text-decoration-none hover-underline">
                        {{ __("Clear") }}
                    </a>
                @endif
            </div>
            <div class="filter-card-body p-2">
                <ul class="category-tree-list list-unstyled mb-0">
                    <li class="category-tree-item {{ !$currentCategorySlug ? 'active' : '' }}">
                        <a href="{{ route('client.products', request()->except(['category', 'page'])) }}" class="category-tree-link d-flex align-items-center justify-content-between p-2 rounded-3 text-decoration-none">
                            <span class="d-flex align-items-center gap-2">
                                <i class="ri-apps-2-line fs-14"></i>
                                <span class="fs-13">{{ __("All categories") }}</span>
                            </span>
                            @if(!$currentCategorySlug)
                                <i class="ri-check-line text-primary fw-bold"></i>
                            @endif
                        </a>
                    </li>
                    @foreach($categoriesList as $cat)
                        @php
                            $isCatActive = ($currentCategorySlug == $cat->slug || $currentCategorySlug == $cat->id);
                            $hasActiveChild = $cat->children->contains(function($child) use ($currentCategorySlug) {
                                return $currentCategorySlug == $child->slug || $currentCategorySlug == $child->id;
                            });
                        @endphp
                        <li class="category-tree-item {{ ($isCatActive || $hasActiveChild) ? 'active open' : '' }}">
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 category-tree-row">
                                <a href="{{ route('client.products', array_merge(request()->except(['category', 'page']), ['category' => $cat->slug])) }}" class="category-tree-link d-flex align-items-center gap-2 text-decoration-none flex-grow-1">
                                    <i class="ri-folder-line fs-14 {{ $isCatActive ? 'text-primary' : 'text-muted' }}"></i>
                                    <span class="fs-13 {{ $isCatActive ? 'fw-bold text-primary' : 'text-dark' }}">{{ $cat->name }}</span>
                                </a>
                                @if($cat->children->count() > 0)
                                    <button type="button" class="btn btn-sm p-0 text-muted category-toggle-btn" data-bs-toggle="collapse" data-bs-target="#subCatCollapse{{ $cat->id }}" aria-expanded="{{ ($isCatActive || $hasActiveChild) ? 'true' : 'false' }}">
                                        <i class="ri-arrow-down-s-line fs-16"></i>
                                    </button>
                                @elseif($cat->products_count > 0)
                                    <span class="badge bg-light text-muted border rounded-pill fs-11 px-2">{{ $cat->products_count }}</span>
                                @endif
                            </div>

                            @if($cat->children->count() > 0)
                                <div class="collapse {{ ($isCatActive || $hasActiveChild) ? 'show' : '' }} ps-3 pe-1 py-1" id="subCatCollapse{{ $cat->id }}">
                                    <ul class="list-unstyled mb-0 border-start ps-2">
                                        @foreach($cat->children as $sub)
                                            @php $isSubActive = ($currentCategorySlug == $sub->slug || $currentCategorySlug == $sub->id); @endphp
                                            <li class="sub-category-item py-1">
                                                <a href="{{ route('client.products', array_merge(request()->except(['category', 'page']), ['category' => $sub->slug])) }}" class="sub-category-link d-flex align-items-center justify-content-between text-decoration-none {{ $isSubActive ? 'fw-bold text-primary active' : 'text-muted' }} fs-12">
                                                    <span>{{ $sub->name }}</span>
                                                    @if($isSubActive)
                                                        <i class="ri-check-line text-primary"></i>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Price Range Filter Block -->
        <div class="filter-card mb-3">
            <div class="filter-card-header">
                <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="ri-money-dollar-circle-line text-primary"></i>
                    <span>{{ __("Price range") }}</span>
                </h6>
            </div>
            <div class="filter-card-body p-3">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fs-11 text-muted mb-1">{{ __("From") }} ({{ config('app.currency.symbol') }}):</label>
                        <input type="number" name="min_price" value="{{ $currentMinPrice }}" class="form-control form-control-sm text-center" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-11 text-muted mb-1">{{ __("To") }} ({{ config('app.currency.symbol') }}):</label>
                        <input type="number" name="max_price" value="{{ $currentMaxPrice }}" class="form-control form-control-sm text-center" placeholder="حداکثر">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1 mt-1">
                    <i class="ri-filter-3-line me-1"></i>
                    {{ __("Apply") }}
                </button>
            </div>
        </div>

        <!-- Filter Actions Block -->
        <div class="filter-actions-block d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1 py-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                <i class="ri-filter-2-fill"></i>
                <span>{{ __("Apply Filters") }}</span>
            </button>
            <a href="{{ route('client.products') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2" title="{{ __('Clear All Filters') }}">
                <i class="ri-refresh-line"></i>
            </a>
        </div>
    </form>
</div>

