<div class="ShivaProductGrid xshop-product-item h-100">
    <div class="product-card card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column transition-all">
        <!-- Card Image Header -->
        <div class="card-img-wrapper position-relative overflow-hidden bg-light" style="height: 220px;">
            @if(!$product->isAvailable())
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill position-absolute top-0 start-0 m-2.5 z-2 fs-11 px-2.5 py-1">
                    {{__("Not available")}}
                </span>
            @elseif($product->category)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill position-absolute top-0 start-0 m-2.5 z-2 fs-12 px-2.5 py-1">
                    {{$product->category->name}}
                </span>
            @endif

            <!-- Quick Action Icons -->
            <div class="card-quick-actions position-absolute top-0 end-0 m-2.5 z-2 d-flex flex-column gap-1.5">
                <a class="fav-btn btn btn-sm btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                   style="width: 32px; height: 32px;"
                   data-slug="{{$product->slug}}" data-is-fav="{{$product->isFav()}}"
                   data-bs-custom-class="custom-tooltip"
                   data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from favorites")}}">
                    <i class="ri-heart-line text-muted"></i>
                    <i class="ri-heart-fill text-danger d-none"></i>
                </a>
                <a class="compare-btn btn btn-sm btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                   style="width: 32px; height: 32px;"
                   data-slug="{{$product->slug}}"
                   data-bs-custom-class="custom-tooltip"
                   data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to/ Remove from compare list")}}">
                    <i class="ri-scales-3-line text-muted"></i>
                </a>
            </div>

            <a href="{{$product->webUrl()}}" class="d-block h-100 w-100">
                <img src="{{$product->thumbUrl()}}" alt="{{$product->name}}" class="card-img-top h-100 w-100 object-fit-cover product-img-hover {{ !$product->isAvailable() ? 'opacity-75' : '' }}" loading="lazy">
            </a>
        </div>

        <!-- Card Body -->
        <div class="card-body p-3.5 d-flex flex-column flex-grow-1">
            <h5 class="product-title fs-15 fw-bold mb-2">
                <a href="{{$product->webUrl()}}" class="text-decoration-none text-dark hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    {{$product->name}}
                </a>
            </h5>

            @if($product->excerpt)
                <p class="text-muted fs-13 mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    {{$product->excerpt}}
                </p>
            @endif

            <!-- Price Row -->
            <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-1">
                @php
                    $rawPrice = $product->quantities()->count() > 0 ? $product->quantities()->min('price') : $product->price;
                    $hasNoPrice = ($rawPrice == 0 || $rawPrice == '' || $rawPrice == null);
                    $isAvailable = $product->isAvailable() && !$hasNoPrice;
                @endphp

                <div class="product-prices d-flex flex-column">
                    @if($isAvailable)
                        @if($product->hasDiscount())
                            <span class="old-price text-muted text-decoration-line-through fs-12 ms-1">
                                {{$product->oldPrice()}}
                            </span>
                        @endif
                        <span class="price fw-bold text-primary fs-15">
                            {{$product->getPrice()}}
                        </span>
                    @else
                        <span class="price fw-medium text-muted fs-13">
                            {{ $hasNoPrice ? __('Call us!') : __('Not available') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-transparent border-0 p-3 pt-0">
            @if($product->stock_status == 'IN_STOCK' && !$hasNoPrice)
                <a href="{{ route('client.product-card-toggle',$product->slug) }}"
                   class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1.5 add-to-card">
                    <i class="ri-shopping-bag-3-line"></i>
                    <span>{{__("Add to card")}}</span>
                </a>
            @else
                <button class="btn btn-light border text-muted btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1.5" disabled>
                    <i class="ri-forbid-line text-muted"></i>
                    <span>
                        @if($hasNoPrice)
                            {{__("Call us!")}}
                        @else
                            {{__("Not available")}}
                        @endif
                    </span>
                </button>
            @endif
        </div>
    </div>
</div>
