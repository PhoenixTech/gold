<section id='ProductAria' class='content live-setting py-4' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-light-subtle p-2 px-3 rounded-pill border d-inline-flex align-items-center mb-0 fs-14">
                <li class="breadcrumb-item">
                    <a href="{{homeUrl()}}" class="text-decoration-none text-muted">
                        <i class="ri-home-2-line me-1"></i> {{config('app.name')}}
                    </a>
                </li>
                @if($product->category)
                    <li class="breadcrumb-item">
                        <a href="{{$product->category->webUrl()}}" class="text-decoration-none text-muted">
                            {{$product->category->name}}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active text-truncate" style="max-width: 280px;" aria-current="page">
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
                            <img src="{{$product->originalImageUrl()}}" alt="{{$product->name}}" class="img-fluid w-100 object-fit-cover rounded-4" style="height: 380px;">
                        </a>
                    </div>

                    @if($product->getMedia()->count() > 0)
                        <div id="aria-img-slider" class="d-flex align-items-center gap-2 overflow-auto pb-2">
                            @foreach($product->getMedia() as $media)
                                <div class="item flex-shrink-0" style="width: 80px; height: 80px;">
                                    <a href="{{$media->getUrl('product-image')}}" class="d-block h-100 w-100 rounded-3 overflow-hidden border">
                                        <img src="{{$media->getUrl('product-image')}}" alt="{{$product->name}}" class="h-100 w-100 object-fit-cover rounded-3">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Product Details Column -->
                <div class="col-lg-7 position-relative" id="aria-product-detail">
                    <!-- Quick Action Buttons -->
                    <div class="position-absolute top-0 end-0 z-2 d-flex align-items-center gap-2">
                        <a class="fav-btn btn btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                           style="width: 38px; height: 38px;"
                           data-slug="{{$product->slug}}" data-is-fav="{{$product->isFav()}}"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from favorites")}}">
                            <i class="ri-heart-line text-muted fs-18"></i>
                            <i class="ri-heart-fill text-danger fs-18 d-none"></i>
                        </a>

                        <a class="bookmark-btn btn btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                           style="width: 38px; height: 38px;"
                           data-slug="{{$product->slug}}" data-is-bookmarked="{{$product->isBookmarked()}}"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to / Remove from bookmarks")}}">
                            <i class="ri-bookmark-line text-muted fs-18"></i>
                            <i class="ri-bookmark-fill text-warning fs-18 d-none"></i>
                        </a>

                        <a href="#comments"
                           class="comment-btn btn btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                           style="width: 38px; height: 38px;"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Comments")}} ({{$product->approvedComments()->count()}})">
                            <i class="ri-chat-3-line text-muted fs-18"></i>
                        </a>

                        <button type="button"
                           class="share-btn btn btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                           style="width: 38px; height: 38px;"
                           data-url="{{$product->webUrl()}}"
                           data-title="{{$product->name}}"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Share")}}">
                            <i class="ri-share-forward-line text-muted fs-18"></i>
                        </button>

                        <a class="compare-btn btn btn-white rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center"
                           style="width: 38px; height: 38px;"
                           data-slug="{{$product->slug}}"
                           data-bs-custom-class="custom-tooltip"
                           data-bs-toggle="tooltip" data-bs-placement="auto" title="{{__("Add to/ Remove from compare list")}}">
                            <i class="ri-scales-3-line text-muted fs-18"></i>
                        </a>
                    </div>

                    <!-- Category badge -->
                    @if($product->category)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fs-13 mb-2 d-inline-block">
                            <i class="ri-folder-3-line me-1"></i> {{$product->category->name}}
                        </span>
                    @endif

                    <!-- Title -->
                    <h3 class="fs-2 fw-bold text-dark mb-3 pe-5">
                        {{$product->name}}
                    </h3>

                    <!-- Price Block -->
                    <div class="price-box bg-light-subtle p-3 rounded-3 border d-flex align-items-center gap-3 mb-4">
                        <div id="price" class="fs-3 fw-bold text-primary mb-0">
                            {{$product->getPrice()}}
                        </div>
                        @if($product->hasDiscount())
                            <div id="price-old" class="fs-5 text-muted text-decoration-line-through mb-0">
                                {{$product->oldPrice()}}
                            </div>
                        @endif
                    </div>

                    <!-- Excerpt -->
                    @if($product->excerpt)
                        <div class="description border-start border-primary border-3 ps-3 py-1 mb-4 text-muted fs-15 leading-relaxed">
                            <p class="mb-0">{{$product->excerpt}}</p>
                        </div>
                    @endif

                    <!-- Stock Status & Add to Cart -->
                    @php
                        $rawPrice = $product->lowestAvailablePrice();
                        $hasNoPrice = ($rawPrice == 0 || $rawPrice == '' || $rawPrice == null);
                        $availableStockItems = $product->availableQuantities()->get();
                    @endphp

                    <div class="add-to-cart-box mb-4">
                        @if($product->stock_status == 'IN_STOCK' && !$hasNoPrice)
                            @if($availableStockItems->count() > 0)
                                <quantities-add-to-card
                                    :qz='@json($availableStockItems)'
                                    :props='@json(usableProp($product->category->props))'
                                    currency="{{config('app.currency.symbol')}}"
                                    card-link="{{ route('client.product-card-toggle',$product->slug) }}"
                                    :translate='@json(['add-to-card' => __('Add to card'), 'weight' => __('Weight'), 'code' => __('Code')])'
                                    @if($product->hasDiscount())
                                        :discount='@json($product->activeDiscounts()->first())'
                                    @endif
                                ></quantities-add-to-card>
                            @else
                                <a href="{{ route('client.product-card-toggle',$product->slug) }}"
                                   class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm add-to-card">
                                    <i class="ri-shopping-bag-3-line fs-20"></i>
                                    <span>{{__("Add to card")}}</span>
                                </a>
                            @endif
                        @else
                            <button class="btn btn-light border text-muted btn-lg rounded-pill px-4 py-2.5 fw-semibold d-inline-flex align-items-center gap-2" disabled>
                                <i class="ri-shopping-bag-3-line fs-20"></i>
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

                    <!-- Meta Data Badges -->
                    <div class="product-metadata border-top pt-3 fs-14 text-muted d-flex flex-column gap-2">
                        @if($product->sku != null && $product->sku != '')
                            <div class="aria-product-data">
                                <span class="fw-semibold text-dark">{{__("SKU")}}:</span>
                                <span>{{$product->sku}}</span>
                            </div>
                        @endif

                        @if($product->categories()->count() > 0)
                            <div class="aria-product-data">
                                <span class="fw-semibold text-dark">{{__("Categories")}}:</span>
                                @foreach($product->categories()->where('id','<>',$product->category->id)->get() as $cat)
                                    <a href="{{$cat->webUrl()}}" class="text-decoration-none text-muted hover-primary">
                                        {{$cat->name}},
                                    </a>
                                @endforeach
                                <a href="{{$product->category->webUrl()}}" class="text-decoration-none text-muted hover-primary">
                                    {{$product->category->name}}
                                </a>
                            </div>
                        @endif

                        @if($product->tags()->count() > 0)
                            <div class="aria-product-data d-flex align-items-center flex-wrap gap-1 mt-1">
                                <span class="fw-semibold text-dark me-1">{{__("Tags")}}:</span>
                                @foreach($product->tags as $tag)
                                    <a href="{{tagUrl($tag->slug)}}" class="badge bg-light text-dark border rounded-pill px-3 py-1.5 text-decoration-none fs-13">
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
                            <button class="accordion-button fw-bold fs-16 bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#desc" aria-expanded="true" aria-controls="desc">
                                <i class="ri-file-text-line text-primary me-2 fs-18"></i> {{__("Description")}}
                            </button>
                        </h2>
                        <div id="desc" class="accordion-collapse collapse show" data-bs-parent="#product-detail">
                            <div class="accordion-body leading-relaxed fs-15">
                                {!! $product->description !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if($product->table != null && trim($product->table) != '')
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold fs-16 bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#table" aria-expanded="false" aria-controls="table">
                                <i class="ri-table-line text-primary me-2 fs-18"></i> {{__("Product table")}}
                            </button>
                        </h2>
                        <div id="table" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                            <div class="accordion-body">
                                {!! $product->table !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if($product->fullMeta())
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold fs-16 bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#info" aria-expanded="false" aria-controls="info">
                                <i class="ri-information-line text-primary me-2 fs-18"></i> {{__("Information")}}
                            </button>
                        </h2>
                        <div id="info" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                            <div class="accordion-body p-0">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="w-50 ps-3">{{__("Item")}}</th>
                                            <th class="text-center pe-3">{{__("Value")}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->fullMeta() as $meta)
                                            <tr>
                                                <td class="ps-3 fw-semibold text-dark">
                                                    <i class="{{$meta['data']->icon}} text-primary me-1"></i>
                                                    {{$meta['data']->label}}
                                                </td>
                                                <td class="text-center pe-3">
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
                        <button class="accordion-button collapsed fw-bold fs-16 bg-light text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#comments-collapse" aria-expanded="false" aria-controls="comments-collapse">
                            <i class="ri-chat-3-line text-primary me-2 fs-18"></i> {{__("Comments")}} ({{$product->approvedComments()->count()}})
                        </button>
                    </h2>
                    <div id="comments-collapse" class="accordion-collapse collapse" data-bs-parent="#product-detail">
                        <div class="accordion-body p-3 p-md-4">
                            @php
                                $approvedComments = $product->approvedComments()->whereNull('parent_id')->with(['approved_children'])->orderByDesc('id')->get();
                            @endphp

                            <!-- Comments list -->
                            @if($approvedComments->count() > 0)
                                <div class="comments-list mb-4 d-flex flex-column gap-3">
                                    @foreach($approvedComments as $comment)
                                        @include('segments.post.SimplePost.inc.comment-detail', ['comment' => $comment])
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted border rounded-3 bg-light mb-4">
                                    <i class="ri-chat-smile-3-line fs-2 d-block mb-1 text-secondary"></i>
                                    <p class="mb-0 fs-14">{{ __("No comments yet. Be the first to share your thoughts!") }}</p>
                                </div>
                            @endif

                            <!-- Comment Submit Form -->
                            <div class="comment-form-card border rounded-3 p-3 p-md-4 bg-light-subtle">
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="ri-edit-line text-primary"></i>
                                    <span>{{ __("Post your comment") }}</span>
                                </h6>
                                @include('components.err')
                                <form id="comment-form" class="safe-form" method="post" action="{{ route('client.comment.submit') }}">
                                    <div class="safe-url" data-url="{{route('client.comment.submit')}}"></div>
                                    @csrf
                                    <input type="hidden" name="commentable_type" value="{{\App\Models\Product::class}}">
                                    <input type="hidden" name="commentable_id" value="{{$product->id}}">
                                    <input type="hidden" name="parent_id" id="parent_id" value="">

                                    <div class="row g-3">
                                        @if(auth()->check())
                                            <div class="col-12">
                                                <div class="alert alert-info py-2 px-3 mb-0 fs-14 d-flex align-items-center gap-2">
                                                    <i class="ri-user-line"></i>
                                                    <span>{{ __("Commenting as") }}: <strong>{{ auth()->user()->name }}</strong> ({{ __('Admin') }})</span>
                                                </div>
                                            </div>
                                        @elseif(auth('customer')->check())
                                            <div class="col-12">
                                                <div class="alert alert-info py-2 px-3 mb-0 fs-14 d-flex align-items-center gap-2">
                                                    <i class="ri-user-line"></i>
                                                    <span>{{ __("Commenting as") }}: <strong>{{ auth('customer')->user()->name ?: auth('customer')->user()->mobile }}</strong></span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="col-md-6">
                                                <label for="comment-name" class="form-label fs-14 fw-semibold">{{ __("Name") }} <span class="text-danger">*</span></label>
                                                <input type="text" name="name" id="comment-name" class="form-control rounded-3" placeholder="{{ __("Your name") }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="comment-email" class="form-label fs-14 fw-semibold">{{ __("Email") }} <span class="text-danger">*</span></label>
                                                <input type="email" name="email" id="comment-email" class="form-control rounded-3" placeholder="name@example.com" required>
                                            </div>
                                        @endif

                                        <div class="col-12">
                                            <label for="comment-message" class="form-label fs-14 fw-semibold">{{ __("Message") }} <span class="text-danger">*</span></label>
                                            <textarea name="message" id="comment-message" rows="4" class="form-control rounded-3" placeholder="{{ __("Write your review or question about this product...") }}" required></textarea>
                                        </div>

                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
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

        <!-- Related Products Section -->
        @if($product->category && $product->category->products()->where('status',1)->where('id', '<>', $product->id)->count() > 0)
            <div class="related-products-section mb-4">
                <h5 class="fs-4 fw-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center gap-2">
                    <i class="ri-grid-fill text-primary"></i>
                    <span>{{__("Related products")}}</span>
                </h5>
                <div class="row g-3 g-md-4">
                    @foreach($product->category->products()->where('status',1)->where('id', '<>', $product->id)->limit(4)->get() as $p)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include(\App\Models\Area::where('name','product-grid')->first()->defPart(), ['product' => $p])
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
