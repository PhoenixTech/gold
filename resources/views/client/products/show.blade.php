@extends('website.inc.website-layout')

@section('title')
    {{$product->name}} - {{config('app.name')}}
@endsection

@php
    $isGold = ($product->metal_type ?? 'gold') !== 'silver';
@endphp

@section('content')
<section id="ProductAria" class="product-show-view py-4">
    <div class="{{gfx()['container']}}">

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb fs-13 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                        {{config('app.name')}}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{route('client.products')}}" class="text-decoration-none text-muted hover-primary">
                        {{__("Products")}}
                    </a>
                </li>
                @if($product->category)
                    <li class="breadcrumb-item">
                        <a href="{{$product->category->webUrl()}}" class="text-decoration-none text-muted hover-primary">
                            {{$product->category->name}}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active text-dark fw-semibold text-truncate" style="max-width: 320px;" aria-current="page">
                    {{$product->name}}
                </li>
            </ol>
        </nav>

        <div class="product-main-card card border-0 shadow-xs rounded-4 p-3 p-md-4 mb-4 bg-white">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div id="preview" class="main-image-box position-relative overflow-hidden rounded-4 border bg-light mb-3">
                        <a href="{{$product->originalImageUrl()}}" id="aria-main-img" class="light-box d-block" data-gallery="aria-products">
                            <img src="{{$product->originalImageUrl()}}" alt="{{$product->name}}" class="img-fluid w-100 object-fit-cover rounded-4 gallery-main-img" style="aspect-ratio: 1 / 1;" fetchpriority="high">
                        </a>
                    </div>

                    @if($product->getMedia()->count() > 0)
                        <div id="aria-img-slider" class="d-flex align-items-center gap-2 overflow-auto pb-2">
                            @foreach($product->getMedia() as $media)
                                <div class="item flex-shrink-0" style="width: 72px; height: 72px;">
                                    <a href="{{$media->getUrl('product-image')}}" class="d-block h-100 w-100 rounded-3 overflow-hidden border">
                                        <img src="{{$media->getUrl('product-image')}}" alt="{{$product->name}}" class="h-100 w-100 object-fit-cover rounded-3" loading="lazy" width="72" height="72">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-lg-7" id="aria-product-detail">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        @if($product->category)
                            <a href="{{$product->category->webUrl()}}" class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1.5 fs-12 fw-medium text-decoration-none hover-primary">
                                {{$product->category->name}}
                            </a>
                        @else
                            <span></span>
                        @endif

                        <div class="d-flex align-items-center gap-1.5">
                            <a class="fav-btn btn btn-sm btn-light border rounded-circle shadow-xs p-0 d-flex align-items-center justify-content-center"
                               style="width: 34px; height: 34px;"
                               data-slug="{{$product->slug}}" data-is-fav="{{$product->isFav()}}"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from favorites")}}">
                                <i class="ri-heart-line text-muted fs-16"></i>
                                <i class="ri-heart-fill text-danger fs-16 d-none"></i>
                            </a>

                            <a class="bookmark-btn btn btn-sm btn-light border rounded-circle shadow-xs p-0 d-flex align-items-center justify-content-center"
                               style="width: 34px; height: 34px;"
                               data-slug="{{$product->slug}}" data-is-bookmarked="{{$product->isBookmarked()}}"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from bookmarks")}}">
                                <i class="ri-bookmark-line text-muted fs-16"></i>
                                <i class="ri-bookmark-fill text-warning fs-16 d-none"></i>
                            </a>

                            <a href="#comments-section"
                               class="comment-btn btn btn-sm btn-light border rounded-circle shadow-xs p-0 d-flex align-items-center justify-content-center"
                               style="width: 34px; height: 34px;"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Comments")}} ({{$product->approvedComments()->count()}})">
                                <i class="ri-chat-3-line text-muted fs-16"></i>
                            </a>

                            <button type="button"
                               class="share-btn btn btn-sm btn-light border rounded-circle shadow-xs p-0 d-flex align-items-center justify-content-center"
                               style="width: 34px; height: 34px;"
                               data-url="{{$product->webUrl()}}"
                               data-title="{{$product->name}}"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Share")}}">
                                <i class="ri-share-forward-line text-muted fs-16"></i>
                            </button>
                        </div>
                    </div>

                    <h1 class="product-title fs-22 fs-md-26 fw-bold text-main mb-3 leading-snug">
                        {{$product->name}}
                    </h1>

                    <div class="product-price-section mb-3 py-2 border-bottom border-light-subtle">
                        <div class="d-flex align-items-baseline gap-2">
                            <span id="price" class="fs-24 fs-md-28 fw-bold text-main mb-0">
                                {{$product->getPrice()}}
                            </span>
                            @if($product->hasDiscount())
                                <del id="price-old" class="fs-14 text-muted text-decoration-line-through">
                                    {{$product->oldPrice()}}
                                </del>
                            @endif
                        </div>
                    </div>

                    @if($product->excerpt)
                        <div class="description mb-3 text-muted fs-14 leading-relaxed">
                            <p class="mb-0">{{$product->excerpt}}</p>
                        </div>
                    @endif

                    @php
                        $offerPiece = $product->firstAvailableQuantity();
                        $rawPrice = $offerPiece?->price ?? $product->lowestAvailablePrice();
                        $hasNoPrice = ($rawPrice == 0 || $rawPrice == '' || $rawPrice == null);
                        $availableStockItems = $offerPiece ? [$offerPiece] : [];
                        $vueTranslations = [
                            'add-to-card' => __('Add to card'),
                            'weight' => __('Weight'),
                            'code' => __('Code'),
                            'gram' => __('gram'),
                            'select-piece-first' => __('Please select an item first'),
                        ];
                    @endphp

                    <div class="add-to-cart-box mb-4">
                        @if($product->stock_status == 'IN_STOCK' && !$hasNoPrice)
                            @if($offerPiece)
                                <quantities-add-to-card
                                    :qz='@json($availableStockItems)'
                                    :props='@json(usableProp($product->category->props))'
                                    currency="{{config('app.currency.symbol')}}"
                                    card-link="{{ route('client.product-card-toggle',$product->slug) }}"
                                    :translate='@json($vueTranslations)'
                                    @if($product->hasDiscount())
                                        :discount='@json($product->activeDiscounts()->first())'
                                    @endif
                                ></quantities-add-to-card>
                            @else
                                <a href="{{ route('client.product-card-toggle',$product->slug) }}"
                                   class="btn btn-primary btn-lg rounded-pill w-100 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2 shadow-sm add-to-card">
                                    <i class="ri-shopping-bag-3-line fs-20"></i>
                                    <span>{{__("Add to card")}}</span>
                                </a>
                            @endif
                        @else
                            <div class="out-of-stock-card p-3 rounded-4 border bg-light-subtle mb-3">
                                <div class="d-flex align-items-start gap-2.5 mb-2.5">
                                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="ri-customer-service-2-line fs-18"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark fs-14 mb-0.5">
                                            {{ $hasNoPrice ? __('Price on request & Custom inquiry') : __('Out of instant stock / Made to order') }}
                                        </h6>
                                        <p class="text-muted fs-13 mb-0 leading-relaxed">
                                            {{ __('This product can be ordered and custom crafted for you. Contact our atelier for latest pricing, exact weight, and production timeline.') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
                                    <a href="{{ route('client.contact') }}" class="btn btn-primary rounded-pill px-4 py-2 fs-13 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs">
                                        <i class="ri-chat-1-line"></i>
                                        <span>{{ __('Contact Us & Inquire') }}</span>
                                    </a>
                                    @php $tel = getSetting('tel'); @endphp
                                    @if(!empty($tel))
                                        <a href="tel:{{$tel}}" class="btn btn-outline-dark rounded-pill px-3.5 py-2 fs-13 fw-semibold d-inline-flex align-items-center gap-1.5" dir="ltr">
                                            <i class="ri-phone-line text-warning"></i>
                                            <span>{{$tel}}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row g-2 mb-3 py-2 border-top border-bottom">
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-11 fs-md-12">
                                <i class="ri-shield-check-line text-muted fs-14 fs-md-15"></i>
                                <span class="text-truncate">{{ $isGold ? __('18K Certified') : __('Original Product Guarantee') }}</span>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-11 fs-md-12">
                                <i class="ri-truck-line text-muted fs-14 fs-md-15"></i>
                                <span class="text-truncate">{{__("Insured Delivery")}}</span>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-11 fs-md-12">
                                <i class="ri-file-list-3-line text-muted fs-14 fs-md-15"></i>
                                <span class="text-truncate">{{__("Official Invoice")}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="product-metadata fs-12 text-muted d-flex flex-column gap-1.5">
                        @if($product->sku != null && $product->sku != '')
                            <div class="aria-product-data d-flex align-items-center gap-2">
                                <span>{{__("SKU")}}:</span>
                                <span class="fw-semibold text-dark">{{$product->sku}}</span>
                            </div>
                        @endif

                        @php
                            $allCats = $product->categories->isNotEmpty() ? $product->categories : ($product->category ? collect([$product->category]) : collect());
                        @endphp
                        @if($allCats->isNotEmpty())
                            <div class="aria-product-data d-flex align-items-center gap-1.5 flex-wrap">
                                <span>{{__("Categories")}}:</span>
                                @foreach($allCats as $cat)
                                    <a href="{{$cat->webUrl()}}" class="text-decoration-none text-dark hover-primary fw-medium">
                                        {{$cat->name}}
                                    </a>
                                    @if(!$loop->last)
                                        <span>،</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @if($product->tags()->count() > 0)
                            <div class="aria-product-data d-flex align-items-center flex-wrap gap-1.5 mt-1">
                                @foreach($product->tags as $tag)
                                    <a href="{{tagUrl($tag->slug)}}" class="badge bg-light text-muted border rounded-pill px-2.5 py-1 text-decoration-none fs-11 hover-primary">
                                        #{{$tag->name}}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php
            $platingLabels = $product->getPlatingColorLabels();
            $stoneLabels = $product->getStoneLabels();
            $accessoryLabels = $product->getAccessoryLabels();
            $occasionLabels = $product->getOccasionLabels();
            $hasAttributes = $isGold || !empty($platingLabels) || !empty($stoneLabels) || !empty($accessoryLabels) || !empty($occasionLabels);
            $fullMetaItems = $product->fullMeta();
            $hasMeta = !empty($fullMetaItems) && count($fullMetaItems) > 0;
            $hasTable = $product->table != null && trim($product->table) != '';
            $commentsCount = $product->approvedComments()->count();
        @endphp

        <div class="product-tabs-card card border-0 shadow-xs rounded-4 p-3 p-md-4 mb-4 bg-white" id="comments-section">
            <ul class="nav nav-pills flex-nowrap overflow-x-auto gap-2 border-bottom pb-3 mb-4" id="productNavTabs" role="tablist">
                @if($product->description)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill px-4 py-2 fs-14 fw-semibold" id="tab-desc-btn" data-bs-toggle="pill" data-bs-target="#desc" type="button" role="tab" aria-controls="desc" aria-selected="true">
                            {{__("Description")}}
                        </button>
                    </li>
                @endif
                @if($hasAttributes || $hasMeta || $hasTable)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ !$product->description ? 'active' : '' }} rounded-pill px-4 py-2 fs-14 fw-semibold" id="tab-info-btn" data-bs-toggle="pill" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="{{ !$product->description ? 'true' : 'false' }}">
                            {{__("Information")}}
                        </button>
                    </li>
                @endif
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ (!$product->description && !$hasAttributes && !$hasMeta && !$hasTable) ? 'active' : '' }} rounded-pill px-4 py-2 fs-14 fw-semibold" id="tab-comments-btn" data-bs-toggle="pill" data-bs-target="#comments-collapse" type="button" role="tab" aria-controls="comments-collapse" aria-selected="false">
                        {{__("Comments")}} ({{$commentsCount}})
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="productTabContent">
                @if($product->description)
                    <div class="tab-pane fade show active" id="desc" role="tabpanel" aria-labelledby="tab-desc-btn">
                        <div class="product-description leading-loose fs-14 text-body max-w-prose">
                            {!! $product->description !!}
                        </div>
                    </div>
                @endif

                @if($hasAttributes || $hasMeta || $hasTable)
                    <div class="tab-pane fade {{ !$product->description ? 'show active' : '' }}" id="info" role="tabpanel" aria-labelledby="tab-info-btn">
                        @if($hasAttributes || $hasMeta)
                            <div class="specs-table-wrapper max-w-lg mb-4">
                                <table class="table table-sm table-borderless mb-0 fs-14">
                                    <tbody>
                                        @if($isGold)
                                            <tr class="border-bottom">
                                                <td class="py-2.5 text-muted w-50">{{__("Gold Karat")}}</td>
                                                <td class="py-2.5 fw-semibold text-main text-end">{{ $product->getGoldKarat()->label() }} ({{ $product->getGoldKarat()->coefficient() }})</td>
                                            </tr>
                                        @endif
                                        @if(!empty($platingLabels))
                                            <tr class="border-bottom">
                                                <td class="py-2.5 text-muted w-50">{{__("Plating Color")}}</td>
                                                <td class="py-2.5 fw-semibold text-main text-end">{{ implode('، ', $platingLabels) }}</td>
                                            </tr>
                                        @endif
                                        @if(!empty($stoneLabels))
                                            <tr class="border-bottom">
                                                <td class="py-2.5 text-muted w-50">{{__("Stone")}}</td>
                                                <td class="py-2.5 fw-semibold text-main text-end">{{ implode('، ', $stoneLabels) }}</td>
                                            </tr>
                                        @endif
                                        @if(!empty($accessoryLabels))
                                            <tr class="border-bottom">
                                                <td class="py-2.5 text-muted w-50">{{__("Accessory")}}</td>
                                                <td class="py-2.5 fw-semibold text-main text-end">{{ implode('، ', $accessoryLabels) }}</td>
                                            </tr>
                                        @endif
                                        @if(!empty($occasionLabels))
                                            <tr class="border-bottom">
                                                <td class="py-2.5 text-muted w-50">{{__("Occasions")}}</td>
                                                <td class="py-2.5 fw-semibold text-main text-end">{{ implode('، ', $occasionLabels) }}</td>
                                            </tr>
                                        @endif
                                        @if($hasMeta)
                                            @foreach($fullMetaItems as $meta)
                                                <tr class="border-bottom">
                                                    <td class="py-2.5 text-muted w-50">{{$meta['data']->label}}</td>
                                                    <td class="py-2.5 fw-semibold text-main text-end">{!! $meta['human_value'] !!}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($hasTable)
                            <div class="product-extra-table fs-14 pt-2">
                                {!! $product->table !!}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="tab-pane fade {{ (!$product->description && !$hasAttributes && !$hasMeta && !$hasTable) ? 'show active' : '' }}" id="comments-collapse" role="tabpanel" aria-labelledby="tab-comments-btn">
                    @php
                        $approvedComments = $product->approvedComments()->whereNull('parent_id')->with(['approved_children'])->orderByDesc('id')->get();
                    @endphp

                    <div class="row g-4" id="comments">
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <h6 class="fw-bold mb-0 text-main fs-15">
                                    {{ __("Customer Reviews & Questions") }}
                                </h6>
                                <span class="badge bg-light text-muted border rounded-pill fs-12 px-2.5 py-1">
                                    {{ $commentsCount }}
                                </span>
                            </div>

                            @if($approvedComments->count() > 0)
                                <div class="comments-list d-flex flex-column gap-2.5">
                                    @foreach($approvedComments as $comment)
                                        @include('client.partials.comment-item', ['comment' => $comment])
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 px-3 text-muted border rounded-4 bg-light-subtle">
                                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                        <i class="ri-chat-smile-3-line fs-20"></i>
                                    </div>
                                    <h6 class="fw-semibold text-dark fs-14 mb-1">{{ __("No comments yet") }}</h6>
                                    <p class="mb-0 fs-13 text-muted">{{ __("Be the first to share your thoughts, questions, or reviews about this product!") }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-5">
                            <div class="comment-form-card border rounded-4 p-3.5 bg-light-subtle sticky-top" style="top: 2rem;">
                                <h6 class="fw-bold mb-3 fs-15 text-dark">
                                    {{ __("Post your comment") }}
                                </h6>
                                @include('components.err')
                                <form id="comment-form" class="safe-form" method="post" action="{{ route('client.comment.submit') }}">
                                    <div class="safe-url" data-url="{{route('client.comment.submit')}}"></div>
                                    @csrf
                                    <input type="hidden" name="commentable_type" value="{{\App\Models\Product::class}}">
                                    <input type="hidden" name="commentable_id" value="{{$product->id}}">
                                    <input type="hidden" name="parent_id" id="parent_id" value="">

                                    <div id="replying-to-badge" class="alert alert-secondary py-1 px-2.5 fs-12 d-none align-items-center justify-content-between mb-2 rounded-3">
                                        <span>{{ __('Reply') }}: <strong id="reply-to-name"></strong></span>
                                        <button type="button" class="btn-close fs-10" id="cancel-reply-btn" aria-label="{{ __('Cancel') }}"></button>
                                    </div>

                                    <div class="row g-2.5">
                                        @if(auth()->check())
                                            <div class="col-12">
                                                <div class="alert alert-info py-2 px-3 mb-0 fs-12 d-flex align-items-center gap-2 rounded-3">
                                                    <i class="ri-user-line"></i>
                                                    <span>{{ __("Commenting as") }}: <strong>{{ auth()->user()->name }}</strong> ({{ __('Admin') }})</span>
                                                </div>
                                            </div>
                                        @elseif(auth('customer')->check())
                                            <div class="col-12">
                                                <div class="alert alert-info py-2 px-3 mb-0 fs-12 d-flex align-items-center gap-2 rounded-3">
                                                    <i class="ri-user-line"></i>
                                                    <span>{{ __("Commenting as") }}: <strong>{{ auth('customer')->user()->name ?: auth('customer')->user()->mobile }}</strong></span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="col-12">
                                                <label for="comment-name" class="form-label fs-12 fw-medium mb-1">{{ __("Name") }} <span class="text-danger">*</span></label>
                                                <input type="text" name="name" id="comment-name" class="form-control form-control-sm rounded-3" placeholder="{{ __("Your name") }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="comment-email" class="form-label fs-12 fw-medium mb-1">{{ __("Email") }} <span class="text-danger">*</span></label>
                                                <input type="email" name="email" id="comment-email" class="form-control form-control-sm rounded-3" placeholder="name@example.com" required>
                                            </div>
                                        @endif

                                        <div class="col-12">
                                            <label for="comment-message" class="form-label fs-12 fw-medium mb-1">{{ __("Message") }} <span class="text-danger">*</span></label>
                                            <textarea name="message" id="comment-message" rows="3" class="form-control rounded-3 fs-13" placeholder="{{ __("Write your review or question about this product...") }}" required></textarea>
                                        </div>

                                        <div class="col-12 mt-2">
                                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold fs-14 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-xs">
                                                <i class="ri-send-plane-2-line"></i>
                                                <span>{{ __("Submit comment") }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($product->category)
            @php
                $relatedProducts = $product->category->products()
                    ->where('status', 1)
                    ->where('id', '<>', $product->id)
                    ->with(['category', 'availableQuantities', 'activeDiscounts'])
                    ->take(4)
                    ->get();
            @endphp
            @if($relatedProducts->isNotEmpty())
                <div class="related-products-section mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 fs-18">
                            <i class="ri-grid-fill text-primary"></i>
                            <span>{{__("Related products")}}</span>
                        </h5>
                        <a href="{{$product->category->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5">
                            <span>{{__("View category")}}</span>
                            <i class="ri-arrow-left-line"></i>
                        </a>
                    </div>
                    <div class="row g-3 g-md-4">
                        @foreach($relatedProducts as $relatedProduct)
                            <div class="col-6 col-md-4 col-lg-3">
                                @include('client.partials.product-card', ['product' => $relatedProduct])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    <div id="hidden-images" class="d-none">
        @foreach($product->getMedia() as $k => $media)
            <a href="{{$media->getUrl()}}" class="light-box" data-gallery="aria-products">
                <img src="{{$media->getUrl('product-image')}}" id="hidden-img-{{$k}}" alt="{{$product->name}}">
            </a>
        @endforeach
    </div>
</section>
@endsection
