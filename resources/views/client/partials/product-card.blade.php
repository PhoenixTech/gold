@php
    $rawPrice = $product->lowestAvailablePrice() ?: $product->price;
    $hasNoPrice = ($rawPrice == 0 || $rawPrice == '' || $rawPrice == null);
    $canBuy = $product->isAvailable() && !$hasNoPrice && $product->stock_status == 'IN_STOCK';
@endphp

<div class="ShivaProductGrid xshop-product-item h-100">
    <div class="product-card card h-100 border-0 shadow-xs hover-lift rounded-3 rounded-md-4 overflow-hidden d-flex flex-column transition-all bg-white">
        <div class="card-img-wrapper position-relative overflow-hidden bg-light">
            @if(!$canBuy)
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill position-absolute top-0 start-0 m-1.5 m-md-2 z-2 fs-11 px-2.5 py-1">
                    {{__("Not available")}}
                </span>
            @elseif($product->category)
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill position-absolute top-0 start-0 m-1.5 m-md-2 z-2 fs-11 fw-medium px-2.5 py-1">
                    {{$product->category->name}}
                </span>
            @endif

            <div class="card-quick-actions position-absolute top-0 end-0 m-1.5 m-md-2 z-2 d-flex flex-column gap-1">
                <a class="fav-btn btn btn-sm btn-light rounded-circle shadow-xs border p-0 d-flex align-items-center justify-content-center"
                   data-slug="{{$product->slug}}" data-is-fav="{{$product->isFav()}}"
                   data-bs-custom-class="custom-tooltip"
                   data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from favorites")}}"
                   style="width: 30px; height: 30px;">
                    <i class="ri-heart-line text-muted fs-14"></i>
                    <i class="ri-heart-fill text-danger fs-14 d-none"></i>
                </a>
                <a class="bookmark-btn btn btn-sm btn-light rounded-circle shadow-xs border p-0 d-flex align-items-center justify-content-center"
                   data-slug="{{$product->slug}}" data-is-bookmarked="{{$product->isBookmarked()}}"
                   data-bs-custom-class="custom-tooltip"
                   data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from bookmarks")}}"
                   style="width: 30px; height: 30px;">
                    <i class="ri-bookmark-line text-muted fs-14"></i>
                    <i class="ri-bookmark-fill text-warning fs-14 d-none"></i>
                </a>
            </div>

            <a href="{{$product->webUrl()}}" class="d-block h-100 w-100">
                <img src="{{$product->thumbUrl()}}" alt="{{$product->name}}" class="card-img-top h-100 w-100 object-fit-cover product-img-hover {{ !$canBuy ? 'opacity-75' : '' }}" {{ ($eager ?? false) ? 'loading=eager fetchpriority=high' : 'loading=lazy' }}>
            </a>
        </div>

        <div class="card-body p-2.5 p-md-3 d-flex flex-column flex-grow-1">
            <h3 class="product-title fs-13 fs-md-14 fw-semibold mb-2 leading-snug">
                <a href="{{$product->webUrl()}}" class="text-decoration-none text-main hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    {{$product->name}}
                </a>
            </h3>

            <div class="mt-auto pt-2 border-top border-light-subtle d-flex align-items-center justify-content-between gap-1 min-h-36">
                @if($canBuy)
                    <div class="product-prices d-flex flex-column min-w-0">
                        @if($product->hasDiscount())
                            <span class="old-price text-muted text-decoration-line-through fs-11 lh-1">
                                {{$product->oldPrice()}}
                            </span>
                        @endif
                        <span class="price fw-bold text-main fs-14 lh-sm text-truncate">
                            {{$product->getPrice()}}
                        </span>
                    </div>
                    <a href="{{ route('client.product-card-toggle',$product->slug) }}"
                       class="btn btn-outline-primary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center flex-shrink-0 add-to-card"
                       data-bs-custom-class="custom-tooltip"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="{{__("Add to card")}}"
                       style="width: 32px; height: 32px;">
                        <i class="ri-shopping-bag-3-line fs-15"></i>
                    </a>
                @else
                    <span class="text-muted fs-12">{{ __("Out of stock") }}</span>
                    <a href="{{$product->webUrl()}}" class="btn btn-light btn-sm rounded-pill px-2.5 py-1 fs-11 text-muted text-decoration-none">
                        {{ __("View") }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
