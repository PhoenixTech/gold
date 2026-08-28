@extends('website.inc.website-layout')

@section('title')
    {{config('app.name')}} - {{getSetting('subtitle') ?: __('Online Gold & Jewelry Store')}}
@endsection

@section('content')
<div class="homepage-wrapper">

    <!-- Category Tabs Explorer Section -->
    @if(isset($mainCategories) && $mainCategories->isNotEmpty())
        <section class="WTFIndex py-4">
            <!-- Category Tabs Navigation Bar -->
            <div class="wtf-tabs-container bg-white border-top border-bottom shadow-sm mb-4">
                <div class="{{gfx()['container']}}">
                    <div id="wtf-main-btns" class="wtf-main-btns py-3">
                        @foreach($mainCategories as $k => $mainCategory)
                            <button type="button" class="btn main-dir rounded-pill px-4 py-2 fw-bold fs-14 transition-all @if($k == 0) active @endif shadow-sm"
                                    style="background: {{$mainCategory->bg_color ?: 'var(--xshop-primary)'}}; color: {{$mainCategory->color ?: '#ffffff'}};"
                                    data-id="#wtf-{{$mainCategory->id}}">
                                {{$mainCategory->name}}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Category Panels -->
            <div class="py-2">
                @foreach($mainCategories as $k => $mainCategory)
                    @php($words = explode(' ', $mainCategory->name))
                    <div class="{{gfx()['container']}} wtf-section" id="wtf-{{$mainCategory->id}}" @if($k == 0) style="display: block" @endif>
                        <div class="row g-3 g-md-4">
                            @foreach($mainCategory->children as $childCategory)
                                <div class="col-6 col-sm-4 col-md-3">
                                    <a class="wtf-cat-card card border-0 shadow-sm rounded-4 overflow-hidden text-decoration-none h-100 transition-all d-block position-relative" href="{{$childCategory->webUrl()}}">
                                        <div class="card-img-box position-relative bg-dark overflow-hidden">
                                            <img src="{{$childCategory->imgUrl()}}" alt="{{$childCategory->name}}" class="w-100 h-100 object-fit-cover cat-img-hover opacity-85" loading="lazy">
                                            <div class="card-overlay-vignette position-absolute inset-0"></div>
                                            <div class="position-absolute bottom-0 start-0 end-0 p-3 text-center z-2">
                                                <h5 class="cat-title fs-15 fw-bold text-white mb-1 text-shadow">
                                                    {{implode(' ', array_diff(explode(' ', $childCategory->name), $words)) ?: $childCategory->name}}
                                                </h5>
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
                @endforeach
            </div>
        </section>
    @endif

    <!-- Brand Story & Trust Badges Section -->
    <section class="home-brand-intro py-4 py-md-5 reveal-on-scroll">
        <div class="{{gfx()['container']}}">
            @include('client.partials.brand-intro')
        </div>
    </section>

    <!-- Latest / Featured Products Section -->
    @if(isset($latestProducts) && $latestProducts->isNotEmpty())
        <section class="featured-products py-5 bg-light-subtle border-top border-bottom reveal-on-scroll">
            <div class="{{gfx()['container']}}">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <div>
                        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="ri-sparkling-fill text-primary"></i>
                            <span>{{__("Latest Products")}}</span>
                        </h4>
                        <p class="text-muted fs-14 mb-0">{{__("Discover our newest fine jewelry & gold collection")}}</p>
                    </div>
                    <a href="{{route('client.products')}}" class="btn btn-outline-primary rounded-pill btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                        <span>{{__("View all")}}</span>
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>

                <div class="row g-3 g-md-4">
                    @foreach($latestProducts as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include('client.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Latest News / Articles Section -->
    @if(isset($latestPosts) && $latestPosts->isNotEmpty())
        <section class="NeginNews py-5 bg-light-subtle reveal-on-scroll">
            <div class="{{gfx()['container']}}">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <div>
                        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="ri-newspaper-line text-primary"></i>
                            <span>{{__("Latest Articles & Market News")}}</span>
                        </h4>
                        <p class="text-muted fs-14 mb-0">{{__("Read educational guides and gold market updates")}}</p>
                    </div>
                    <a href="{{route('client.posts')}}" class="btn btn-outline-primary rounded-pill btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                        <span>{{__("View all")}}</span>
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($latestPosts as $post)
                        <div class="col-12 col-sm-6 col-lg-3">
                            @include('client.partials.post-card', ['post' => $post])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Homepage FAQ Section -->
    <section class="home-faq-section py-5 bg-white border-top reveal-on-scroll">
        <div class="{{gfx()['container']}}">
            @include('client.partials.faq')
        </div>
    </section>

</div>
@endsection
