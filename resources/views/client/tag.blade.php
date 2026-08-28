@extends('website.inc.website-layout')

@section('title')
    {{$title}} - {{config('app.name')}}
@endsection

@php
    $defaultTab = 'products';
    if (count($products) == 0 && count($posts) > 0) {
        $defaultTab = 'posts';
    } elseif (count($products) == 0 && count($posts) == 0 && count($clips) > 0) {
        $defaultTab = 'clips';
    }
@endphp

@section('content')
    <div class="{{gfx()['container']}} content tag-page py-4">
        <!-- Page Title Header -->
        <div class="search-header text-center mb-4">
            <h3 class="fs-2 fw-bold text-dark mb-2">{{$title}}</h3>
            @if(!empty($subtitle))
                <p class="text-muted fs-15">{{$subtitle}}</p>
            @endif
        </div>

        <!-- Search / Tag Navigation Tabs -->
        <div class="search-tabs-wrapper mb-4">
            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
                <button type="button" class="search-tab-btn btn @if($defaultTab == 'products') btn-primary text-white active @else btn-light text-dark border @endif rounded-pill px-4 py-2 fw-semibold fs-14 transition-all" data-tab-target="products">
                    <i class="ri-shopping-bag-3-line me-1"></i>
                    {{__("Products")}}
                    <span class="badge bg-white text-dark border rounded-pill ms-1 px-2 py-0.5">{{count($products)}}</span>
                </button>
                <button type="button" class="search-tab-btn btn @if($defaultTab == 'posts') btn-primary text-white active @else btn-light text-dark border @endif rounded-pill px-4 py-2 fw-semibold fs-14 transition-all" data-tab-target="posts">
                    <i class="ri-article-line me-1"></i>
                    {{__("Posts")}}
                    <span class="badge bg-white text-dark border rounded-pill ms-1 px-2 py-0.5">{{count($posts)}}</span>
                </button>
                <button type="button" class="search-tab-btn btn @if($defaultTab == 'clips') btn-primary text-white active @else btn-light text-dark border @endif rounded-pill px-4 py-2 fw-semibold fs-14 transition-all" data-tab-target="clips">
                    <i class="ri-video-line me-1"></i>
                    {{__("Video clips")}}
                    <span class="badge bg-white text-dark border rounded-pill ms-1 px-2 py-0.5">{{count($clips)}}</span>
                </button>
            </div>
        </div>

        <!-- Tab Panes -->
        <div class="search-tab-contents">
            <!-- PRODUCTS TAB -->
            <div class="search-tab-pane @if($defaultTab != 'products') d-none @endif" id="products">
                @if(count($products) == 0)
                    <div class="alert alert-info border-0 shadow-sm rounded-4 text-center py-4 my-3">
                        <i class="ri-information-line fs-2 d-block mb-2 text-info"></i>
                        {{__("There is nothing to show!")}}
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($products as $product)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="product-card card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column transition-all">
                                    <div class="card-img-wrapper position-relative overflow-hidden bg-light" style="height: 200px;">
                                        @if($product->category)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill position-absolute top-0 start-0 m-2 z-2 fs-12 px-2.5 py-1">
                                                {{$product->category->name}}
                                            </span>
                                        @endif
                                        <a href="{{$product->webUrl()}}" class="d-block h-100 w-100">
                                            <img src="{{$product->thumbUrl()}}" alt="{{$product->name}}" class="card-img-top h-100 w-100 object-fit-cover" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column flex-grow-1">
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
                                        <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-primary fs-14">{{$product->getPrice()}}</span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                        <a href="{{$product->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1">
                                            <span>{{__("View product")}}</span>
                                            <i class="ri-arrow-left-line"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{$products->appends(request()->query())->links()}}
                    </div>
                @endif
            </div>

            <!-- POSTS TAB -->
            <div class="search-tab-pane @if($defaultTab != 'posts') d-none @endif" id="posts">
                @if(count($posts) == 0)
                    <div class="alert alert-info border-0 shadow-sm rounded-4 text-center py-4 my-3">
                        <i class="ri-information-line fs-2 d-block mb-2 text-info"></i>
                        {{__("There is nothing to show!")}}
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($posts as $post)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="grid-post-card card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column transition-all">
                                    <div class="card-img-wrapper position-relative overflow-hidden bg-light" style="height: 190px;">
                                        @if($post->mainGroup)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill position-absolute top-0 start-0 m-3 z-2 fs-12 px-3 py-1.5">
                                                <i class="ri-folder-3-line me-1"></i> {{$post->mainGroup->name}}
                                            </span>
                                        @endif
                                        <a href="{{$post->webUrl()}}" class="d-block h-100 w-100">
                                            <img src="{{$post->imgUrl()}}" alt="{{$post->title}}" class="card-img-top h-100 w-100 object-fit-cover" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="card-body p-3.5 d-flex flex-column flex-grow-1">
                                        <h5 class="post-card-title fs-16 fw-bold mb-2">
                                            <a href="{{$post->webUrl()}}" class="text-decoration-none text-dark hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{$post->title}}
                                            </a>
                                        </h5>
                                        @if($post->subtitle)
                                            <p class="card-text text-muted fs-14 mb-3 leading-relaxed" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{$post->subtitle}}
                                            </p>
                                        @endif
                                        <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between text-muted fs-13">
                                            <span class="d-inline-flex align-items-center">
                                                <i class="ri-calendar-line text-primary me-1"></i>
                                                {{$post->created_at->ldate('Y/m/d')}}
                                            </span>
                                            <span class="d-inline-flex align-items-center">
                                                <i class="ri-eye-line text-primary me-1"></i>
                                                {{number_format($post->view)}}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                        <a href="{{$post->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1">
                                            <span>{{__("Read more")}}</span>
                                            <i class="ri-arrow-left-line"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{$posts->appends(request()->query())->links()}}
                    </div>
                @endif
            </div>

            <!-- CLIPS TAB -->
            <div class="search-tab-pane @if($defaultTab != 'clips') d-none @endif" id="clips">
                @if(count($clips) == 0)
                    <div class="alert alert-info border-0 shadow-sm rounded-4 text-center py-4 my-3">
                        <i class="ri-information-line fs-2 d-block mb-2 text-info"></i>
                        {{__("There is nothing to show!")}}
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($clips as $clip)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="clip-card card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column transition-all">
                                    <div class="card-img-wrapper position-relative overflow-hidden bg-dark" style="height: 200px;">
                                        <a href="{{$clip->webUrl()}}" class="d-block h-100 w-100">
                                            <img src="{{$clip->imgUrl()}}" alt="{{$clip->title}}" class="card-img-top h-100 w-100 object-fit-cover opacity-85" loading="lazy">
                                            <div class="position-absolute top-50 start-50 translate-middle text-white fs-1">
                                                <i class="ri-play-circle-fill shadow-lg rounded-circle"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="card-body p-3.5 d-flex flex-column flex-grow-1">
                                        <h5 class="clip-title fs-16 fw-bold mb-2">
                                            <a href="{{$clip->webUrl()}}" class="text-decoration-none text-dark hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{$clip->title}}
                                            </a>
                                        </h5>
                                        <p class="text-muted fs-14 mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            {{Str::limit(strip_tags($clip->body), 100)}}
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                        <a href="{{$clip->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1">
                                            <span>{{__("Watch video")}}</span>
                                            <i class="ri-play-line"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{$clips->appends(request()->query())->links()}}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function getTargetHash() {
                var hash = window.location.hash.replace('#', '').trim();
                if (hash === 'products' || hash === 'posts' || hash === 'clips') {
                    return hash;
                }
                return null;
            }

            function activateTab(tabTarget) {
                if (!tabTarget) {
                    return;
                }

                var btn = document.querySelector('[data-tab-target="' + tabTarget + '"]');
                if (!btn) return;

                document.querySelectorAll('.search-tab-btn').forEach(function(b) {
                    b.classList.remove('active', 'btn-primary', 'text-white');
                    b.classList.add('btn-light', 'text-dark', 'border');
                });
                btn.classList.add('active', 'btn-primary', 'text-white');
                btn.classList.remove('btn-light', 'text-dark', 'border');

                document.querySelectorAll('.search-tab-pane').forEach(function(pane) {
                    pane.classList.add('d-none');
                });
                var activePane = document.getElementById(tabTarget);
                if (activePane) {
                    activePane.classList.remove('d-none');
                }
            }

            document.querySelectorAll('.search-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var target = this.getAttribute('data-tab-target');
                    activateTab(target);
                    if (history.pushState) {
                        history.pushState(null, null, '#' + target);
                    } else {
                        window.location.hash = '#' + target;
                    }
                });
            });

            var hash = getTargetHash();
            if (hash) {
                activateTab(hash);
            }

            window.addEventListener('hashchange', function() {
                var newHash = getTargetHash();
                if (newHash) {
                    activateTab(newHash);
                }
            });
        });
    </script>
@endsection
