@extends('website.inc.website-layout')

@section('title')
    {{$product->name}} - {{config('app.name')}}
@endsection

@section('content')
<section id="ProductAria" class="product-show-view py-4">
    <div class="{{gfx()['container']}}">

        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-light-subtle p-2 px-3 rounded-pill border d-inline-flex align-items-center mb-0 fs-12 flex-wrap">
                <li class="breadcrumb-item">
                    <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                        <i class="ri-home-2-line me-1"></i> {{config('app.name')}}
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
                <li class="breadcrumb-item active text-dark fw-bold text-truncate" style="max-width: 280px;" aria-current="page">
                    {{$product->name}}
                </li>
            </ol>
        </nav>

        <!-- Main Product Card Grid -->
        <div class="product-main-card card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-5">
            <div class="row g-4">
                <!-- Gallery Column -->
                <div class="col-lg-5">
                    <div id="preview" class="main-image-box position-relative overflow-hidden rounded-4 border bg-light mb-3">
                        <a href="{{$product->originalImageUrl()}}" id="aria-main-img" class="light-box d-block" data-gallery="aria-products">
                            <img src="{{$product->originalImageUrl()}}" alt="{{$product->name}}" class="img-fluid w-100 object-fit-cover rounded-4 gallery-main-img" style="aspect-ratio: 1 / 1;">
                        </a>
                    </div>

                    @if($product->getMedia()->count() > 0)
                        <div id="aria-img-slider" class="d-flex align-items-center gap-2 overflow-auto pb-2">
                            @foreach($product->getMedia() as $media)
                                <div class="item flex-shrink-0" style="width: 72px; height: 72px;">
                                    <a href="{{$media->getUrl('product-image')}}" class="d-block h-100 w-100 rounded-3 overflow-hidden border">
                                        <img src="{{$media->getUrl('product-image')}}" alt="{{$product->name}}" class="h-100 w-100 object-fit-cover rounded-3">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Product Details Column -->
                <div class="col-lg-7" id="aria-product-detail">
                    <!-- Top Action Bar (Category + Quick Actions) -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        @if($product->category)
                            <a href="{{$product->category->webUrl()}}" class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1.5 fs-12 fw-semibold text-decoration-none hover-primary">
                                <i class="ri-folder-3-line me-1"></i> {{$product->category->name}}
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

                            <a href="#comments"
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

                            <a class="compare-btn btn btn-sm btn-light border rounded-circle shadow-xs p-0 d-flex align-items-center justify-content-center"
                               style="width: 34px; height: 34px;"
                               data-slug="{{$product->slug}}"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to/ Remove from compare list")}}">
                                <i class="ri-scales-3-line text-muted fs-16"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Product Title (Refactoring UI: Balanced Heading Scale) -->
                    <h1 class="product-title fs-20 fw-bold text-dark mb-3 leading-snug">
                        {{$product->name}}
                    </h1>

                    <!-- Price Box -->
                    <div class="product-price-card p-3 rounded-3 mb-3 border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="fs-12 text-muted fw-medium d-block mb-0.5">{{__("Price")}}:</span>
                                <div class="d-flex align-items-baseline gap-2">
                                    <span id="price" class="fs-22 fw-bold text-dark mb-0">
                                        {{$product->getPrice()}}
                                    </span>
                                    @if($product->hasDiscount())
                                        <del id="price-old" class="fs-14 text-muted text-decoration-line-through">
                                            {{$product->oldPrice()}}
                                        </del>
                                    @endif
                                </div>
                            </div>

                            <span class="badge bg-white text-dark border rounded-pill px-2.5 py-1 fs-11 d-inline-flex align-items-center gap-1 shadow-xs">
                                <i class="ri-line-chart-line text-warning"></i>
                                <span>{{__("Live price calculation")}}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Excerpt / Highlights -->
                    @if($product->excerpt)
                        <div class="description border-start border-warning border-3 ps-3 py-1 mb-3 text-muted fs-14 leading-relaxed">
                            <p class="mb-0">{{$product->excerpt}}</p>
                        </div>
                    @endif

                    <!-- Stock Status & Add to Cart -->
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
                            <div class="out-of-stock-card p-3.5 rounded-4 border bg-light-subtle mb-3">
                                <div class="d-flex align-items-start gap-2.5 mb-2.5">
                                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                        <i class="ri-customer-service-2-line fs-20"></i>
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
                                    <a href="{{ route('client.contact') }}" class="btn btn-primary rounded-pill px-4 py-2 fs-13 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
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

                    <!-- Trust Strip Under Add To Cart -->
                    <div class="row g-2 mb-3 py-2 border-top border-bottom">
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-12">
                                <i class="ri-shield-check-line text-success fs-15"></i>
                                <span>{{__("18K Certified")}}</span>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-12">
                                <i class="ri-truck-line text-primary fs-15"></i>
                                <span>{{__("Insured Delivery")}}</span>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted fs-12">
                                <i class="ri-file-list-3-line text-warning fs-15"></i>
                                <span>{{__("Official Invoice")}}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Data Badges -->
                    <div class="product-metadata fs-13 text-muted d-flex flex-column gap-1.5">
                        @if($product->sku != null && $product->sku != '')
                            <div class="aria-product-data d-flex align-items-center gap-2">
                                <span class="text-muted">{{__("SKU")}}:</span>
                                <span class="fw-semibold text-dark">{{$product->sku}}</span>
                            </div>
                        @endif

                        @if($product->categories()->count() > 0)
                            <div class="aria-product-data d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-muted">{{__("Categories")}}:</span>
                                @foreach($product->categories()->where('id','<>',$product->category?->id)->get() as $cat)
                                    <a href="{{$cat->webUrl()}}" class="text-decoration-none text-dark hover-primary fw-medium">
                                        {{$cat->name}},
                                    </a>
                                @endforeach
                                @if($product->category)
                                    <a href="{{$product->category->webUrl()}}" class="text-decoration-none text-dark hover-primary fw-medium">
                                        {{$product->category->name}}
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if($product->tags()->count() > 0)
                            <div class="aria-product-data d-flex align-items-center flex-wrap gap-1.5 mt-1">
                                <span class="text-muted me-1">{{__("Tags")}}:</span>
                                @foreach($product->tags as $tag)
                                    <a href="{{tagUrl($tag->slug)}}" class="badge bg-light text-dark border rounded-pill px-2.5 py-1 text-decoration-none fs-12 hover-primary">
                                        # {{$tag->name}}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Information Accordion / Tabs -->
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-5">
            <div class="accordion accordion-flush" id="product-detail">
                @if($product->description)
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden border">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold fs-15 bg-light-subtle text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#desc" aria-expanded="true" aria-controls="desc">
                                <i class="ri-file-text-line text-warning me-2 fs-18"></i> {{__("Description")}}
                            </button>
                        </h2>
                        <div id="desc" class="accordion-collapse collapse show" data-bs-parent="#product-detail">
                            <div class="accordion-body leading-relaxed fs-14 text-body p-3.5">
                                {!! $product->description !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if($product->table != null && trim($product->table) != '')
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold fs-15 bg-light-subtle text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#table" aria-expanded="false" aria-controls="table">
                                <i class="ri-table-line text-warning me-2 fs-18"></i> {{__("Product table")}}
                            </button>
                        </h2>
                        <div id="table" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                            <div class="accordion-body p-3.5 fs-14">
                                {!! $product->table !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if($product->fullMeta())
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold fs-15 bg-light-subtle text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#info" aria-expanded="false" aria-controls="info">
                                <i class="ri-information-line text-warning me-2 fs-18"></i> {{__("Information")}}
                            </button>
                        </h2>
                        <div id="info" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                            <div class="accordion-body p-0">
                                <table class="table table-hover mb-0 fs-14">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="w-50 ps-3 fs-13 text-muted fw-semibold">{{__("Item")}}</th>
                                            <th class="text-center pe-3 fs-13 text-muted fw-semibold">{{__("Value")}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->fullMeta() as $meta)
                                            <tr>
                                                <td class="ps-3 fw-medium text-dark">
                                                    <i class="{{$meta['data']->icon}} text-warning me-1.5"></i>
                                                    {{$meta['data']->label}}
                                                </td>
                                                <td class="text-center pe-3 text-body">
                                                    {!! $meta['human_value'] !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Comments & Reviews Accordion Item -->
                <div class="accordion-item border-0 rounded-3 overflow-hidden border" id="comments">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold fs-15 bg-light-subtle text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#comments-collapse" aria-expanded="false" aria-controls="comments-collapse">
                            <i class="ri-chat-3-line text-warning me-2 fs-18"></i> {{__("Comments")}} ({{$product->approvedComments()->count()}})
                        </button>
                    </h2>
                    <div id="comments-collapse" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                        <div class="accordion-body p-3 p-md-4">
                            @php
                                $approvedComments = $product->approvedComments()->whereNull('parent_id')->with(['approved_children'])->orderByDesc('id')->get();
                            @endphp

                            <div class="row g-4">
                                <!-- Left Column: Comments List -->
                                <div class="col-lg-7">
                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-1 border-bottom">
                                        <h6 class="fw-bold mb-0 text-dark fs-15 d-flex align-items-center gap-1.5">
                                            <i class="ri-discuss-line text-warning"></i>
                                            <span>{{ __("Customer Reviews & Questions") }}</span>
                                        </h6>
                                        <span class="badge bg-light text-dark border rounded-pill fs-12 px-2.5 py-1">
                                            {{ $product->approvedComments()->count() }} {{ __("Comments") }}
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
                                            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px;">
                                                <i class="ri-chat-smile-3-line fs-22"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark fs-14 mb-1">{{ __("No comments yet") }}</h6>
                                            <p class="mb-0 fs-13 text-muted">{{ __("Be the first to share your thoughts, questions, or reviews about this product!") }}</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Right Column: Comment Submit Form -->
                                <div class="col-lg-5">
                                    <div class="comment-form-card border rounded-4 p-3.5 bg-light-subtle sticky-top" style="top: 2rem;">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 fs-15 text-dark">
                                            <i class="ri-edit-box-line text-warning"></i>
                                            <span>{{ __("Post your comment") }}</span>
                                        </h6>
                                        @include('components.err')
                                        <form id="comment-form" class="safe-form" method="post" action="{{ route('client.comment.submit') }}">
                                            <div class="safe-url" data-url="{{route('client.comment.submit')}}"></div>
                                            @csrf
                                            <input type="hidden" name="commentable_type" value="{{\App\Models\Product::class}}">
                                            <input type="hidden" name="commentable_id" value="{{$product->id}}">
                                            <input type="hidden" name="parent_id" id="parent_id" value="">

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
                                                        <label for="comment-name" class="form-label fs-12 fw-semibold mb-1">{{ __("Name") }} <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" id="comment-name" class="form-control form-control-sm rounded-3" placeholder="{{ __("Your name") }}" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="comment-email" class="form-label fs-12 fw-semibold mb-1">{{ __("Email") }} <span class="text-danger">*</span></label>
                                                        <input type="email" name="email" id="comment-email" class="form-control form-control-sm rounded-3" placeholder="name@example.com" required>
                                                    </div>
                                                @endif

                                                <div class="col-12">
                                                    <label for="comment-message" class="form-label fs-12 fw-semibold mb-1">{{ __("Message") }} <span class="text-danger">*</span></label>
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
            </div>
        </div>

        <!-- Related Products Section -->
        @if($product->category && $product->category->products()->where('status', 1)->where('id', '<>', $product->id)->count() > 0)
            <div class="related-products-section mb-4">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 fs-18">
                        <i class="ri-grid-fill text-warning"></i>
                        <span>{{__("Related products")}}</span>
                    </h5>
                    <a href="{{$product->category->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5">
                        <span>{{__("View category")}}</span>
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>
                <div class="row g-3 g-md-4">
                    @foreach($product->category->products()->where('status', 1)->where('id', '<>', $product->id)->take(4)->get() as $relatedProduct)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include('client.partials.product-card', ['product' => $relatedProduct])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Hidden Lightbox Gallery Images -->
    <div id="hidden-images" class="d-none">
        @foreach($product->getMedia() as $k => $media)
            <a href="{{$media->getUrl()}}" class="light-box" data-gallery="aria-products">
                <img src="{{$media->getUrl('product-image')}}" id="hidden-img-{{$k}}" alt="{{$product->name}}">
            </a>
        @endforeach
    </div>
</section>
@endsection
