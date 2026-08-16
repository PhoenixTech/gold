<section id='ProductKaren' class='content live-setting' data-live="{{$data->area_name.'_'.$data->part}}">

    <div class="{{gfx()['container']}}">
        @include('components.err')
        <div class="row">
            <div class="col-lg-5">
                <div id="preview">
                    <a href="{{$product->originalImageUrl()}}" id="karen-main-img" class="light-box"
                       data-gallery="karen-products">
                        <img src="{{$product->originalImageUrl()}}" alt="{{$product->name}}" >
                    </a>
                </div>
                <div id="karen-img-slider">
                    @foreach($product->getMedia() as $media)
                        <div class="item">
                            <a href="{{$media->getUrl('product-image')}}">
                                <img src="{{$media->getUrl('product-image')}}" alt="{{$product->name}}">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-7" id="karen-product-detail">
                <nav aria-label="breadcrumb" class="pt-1">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{homeUrl()}}">
                                {{config('app.name')}}
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{$product->category->webUrl()}}">
                                {{$product->category->name}}
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{$product->name}}
                        </li>
                    </ol>
                </nav>


                <h3>
                    {{$product->name}}
                </h3>
                <div class="description">
                    <p>
                        {{$product->excerpt}}
                    </p>
                </div>
                <div class="row">
                    <div id="price" class="col">
                        {{$product->getPrice()}}
                    </div>

                    @if($product->hasDiscount())
                        <div id="price-old" class="col">
                            {{$product->oldPrice()}}
                        </div>
                    @endif
                </div>
                @if($product->sku != null && $product->sku != '')
                    <div class="karen-product-data">

                    <span>
                        {{__("SKU")}}:
                    </span>
                        <b>
                            {{$product->sku}}
                        </b>
                    </div>
                @endif
                @if($product->categories()->count() > 0)
                    <div class="karen-product-data">
                    <span>
                        {{__("Categories")}}:
                    </span>
                        @foreach($product->categories()->where('id','<>',$product->category->id)->get() as $cat)
                            <a href="{{$cat->webUrl()}}">
                                {{$cat->name}},
                            </a>
                        @endforeach
                        <a href="{{$product->category->webUrl()}}">
                            {{$product->category->name}}
                        </a>
                    </div>
                @endif
                @if($product->tags()->count() > 0)
                    <div class="karen-product-data">
                    <span>
                        {{__("Tags")}}:
                    </span>
                        @foreach($product->tags as $tag)
                            <a href="{{tagUrl($tag->slug)}}" class="tag me-2">
                                <i class="ri-price-tag-line"></i>
                                {{$tag->name}}
                            </a>
                        @endforeach
                    </div>
                @endif
                <div class="d-flex align-items-center flex-wrap gap-2 my-3">
                    <a class="fav-btn btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5" data-slug="{{$product->slug}}" data-is-fav="{{$product->isFav()}}">
                        <i class="ri-heart-line"></i>
                        <i class="ri-heart-fill d-none"></i>
                        <span>{{__("Like")}}</span>
                    </a>

                    <a class="bookmark-btn btn btn-outline-warning btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5" data-slug="{{$product->slug}}" data-is-bookmarked="{{$product->isBookmarked()}}">
                        <i class="ri-bookmark-line"></i>
                        <i class="ri-bookmark-fill d-none"></i>
                        <span>{{__("Bookmark")}}</span>
                    </a>

                    <a href="#comments" class="comment-btn btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5">
                        <i class="ri-chat-3-line"></i>
                        <span>{{__("Comments")}} ({{$product->approvedComments()->count()}})</span>
                    </a>

                    <button type="button" class="share-btn btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5" data-url="{{$product->webUrl()}}" data-title="{{$product->name}}">
                        <i class="ri-share-forward-line"></i>
                        <span>{{__("Share")}}</span>
                    </button>

                    <a class="compare-btn btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5" data-slug="{{$product->slug}}">
                        <i class="ri-scales-3-line"></i>
                        <span>{{__("Compare")}}</span>
                    </a>
                </div>

                @php
                    $rawPrice = $product->lowestAvailablePrice();
                    $hasNoPrice = ($rawPrice == 0 || $rawPrice == '' || $rawPrice == null);
                    $availableStockItems = $product->availableQuantities()->get();
                @endphp

                @if($product->stock_status == 'IN_STOCK' && !$hasNoPrice)

                    @if($availableStockItems->count()>0)
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
                           class="btn btn-outline-primary add-to-card btn-lg">
                            <i class="ri-shopping-bag-3-line"></i>
                            {{__("Add to card")}}
                        </a>
                    @endif

                @else
                    <a
                        class="btn btn-primary disabled">
                        <i class="ri-shopping-bag-3-line"></i>
                        @if($hasNoPrice)
                            {{__("Call us!")}}
                        @else
                            {{__("Not available")}}
                        @endif
                    </a>
                @endif
            </div>
        </div>
        <div class="navtabs">
            <div class="navtab active" data-target="desc">
                {{__("Description")}}
            </div>
            @if($product->table != null || trim($product->table) != '')
                <div class="navtab" data-target="table">
                    {{__("Product table")}}
                </div>
            @endif
            <div class="navtab" data-target="info">
                {{__("Information")}}
            </div>
            <div class="navtab" data-target="comments">
                {{__("Comments")}} ({{$product->approvedComments()->count()}})
            </div>
            @if(auth('customer')->check())
                <div class="navtab" data-target="rate">
                    {{__("Rate")}}
                </div>
            @endif
            <div class="underline"></div>
        </div>
        <div id="desc" class="tab-content active">
            {!! $product->description !!}
        </div>
        <div id="table" class="tab-content">
            {!! $product->table !!}
        </div>
        <div id="comments" class="tab-content">
            <div class="p-3">
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
                                    <label for="karen-comment-name" class="form-label fs-14 fw-semibold">{{ __("Name") }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="karen-comment-name" class="form-control rounded-3" placeholder="{{ __("Your name") }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="karen-comment-email" class="form-label fs-14 fw-semibold">{{ __("Email") }} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="karen-comment-email" class="form-control rounded-3" placeholder="name@example.com" required>
                                </div>
                            @endif

                            <div class="col-12">
                                <label for="karen-comment-message" class="form-label fs-14 fw-semibold">{{ __("Message") }} <span class="text-danger">*</span></label>
                                <textarea name="message" id="karen-comment-message" rows="4" class="form-control rounded-3" placeholder="{{ __("Write your review or question about this product...") }}" required></textarea>
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
        <div id="rate" class="tab-content">
            <form id="rating-form" method="post" data-url="{{route('client.rate')}}">
                @csrf
                <input type="hidden" name="rateable_id" value="{{$product->id}}">
                <input type="hidden" name="rateable_type" value="{{\App\Models\Product::class}}">
                @foreach($product->evaluations() as $e)
                    <rate-input xtitle="{{$e->title}}" xname="rate[{{ $e->id }}]" :xvalue="{{detectRateCustomer(\App\Models\Product::class,$product->id,$e->id)}}"></rate-input>
                    <hr>
                @endforeach
                    <button class="btn btn-primary w-100">
                        <i class="ri-send-plane-line"></i>
                    </button>
            </form>
        </div>
        <div id="info" class="tab-content">
            <table class="table table-striped table-bordered table-striped">
                <tr>
                    <th class="w-50">
                        {{__("Item")}}
                    </th>
                    <th>
                        {{__("Value")}}
                    </th>
                </tr>
                @foreach($product->fullMeta() as $meta)
                    <tr>
                        <td>
                            <i class="{{$meta['data']->icon}}"></i>
                            &nbsp;
                            {{$meta['data']->label}}
                        </td>
                        <td class="text-center">
                            {!! $meta['human_value'] !!}
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <h5 class="mt-4">
            {{__("Related products")}}
        </h5>
        <div id="rel-products" class="mb-2">
            @foreach($product->category->products()->where('status',1)->limit(10)->get() as $p)
                <div class="item">
                @include(\App\Models\Area::where('name','product-grid')->first()->defPart(),['$product' => $p])
                </div>
            @endforeach
        </div>
    </div>
    <div id="hidden-images">
        @foreach($product->getMedia() as $k => $media)
            <a href="{{$media->getUrl()}}" class="light-box"
               data-gallery="karen-products">
                <img src="{{$media->getUrl('product-image')}}" id="hidden-img-{{$k}}"
                     alt="{{$product->name}}">
            </a>
        @endforeach
    </div>
</section>
