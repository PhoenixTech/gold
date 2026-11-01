@extends('website.inc.website-layout')

@section('title')
    {{ $title }} - {{ config('app.name') }}
@endsection

@php
    $defaultTab = 'products';
    if ($products->total() == 0 && $posts->total() > 0) {
        $defaultTab = 'posts';
    } elseif ($products->total() == 0 && $posts->total() == 0 && $clips->total() > 0) {
        $defaultTab = 'clips';
    }
@endphp

@section('content')
<section class="search-page-view py-4">
    <div class="{{ gfx()['container'] }}">
        <header class="search-header-wrapper mb-4 pb-3 border-bottom border-light-subtle">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb fs-13 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('client.welcome') }}" class="text-muted text-decoration-none hover-primary">{{ __("Home") }}</a></li>
                    <li class="breadcrumb-item active text-main fw-bold" aria-current="page">{{ __("Search") }}</li>
                </ol>
            </nav>
            <div class="row align-items-center g-3">
                <div class="col-lg-6">
                    <h1 class="fs-4 fw-bold text-main mb-0">
                        {{ $title }}
                    </h1>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('client.search') }}" method="GET" role="search">
                        <div class="input-group">
                            <input type="search" name="q" value="{{ $q }}" class="form-control rounded-2 border" placeholder="{{ __('Search products, articles, categories...') }}" required autocomplete="off" @if(empty($q)) autofocus @endif aria-label="{{ __('Search') }}">
                            <button class="btn btn-primary rounded-2 px-3.5 d-inline-flex align-items-center gap-1.5" type="submit" aria-label="{{ __('Search') }}">
                                <i class="ri-search-2-line" aria-hidden="true"></i>
                                <span>{{ __('Search') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </header>

        @if(mb_strlen($q) < 2)
            <div class="card border rounded-3 p-5 text-center bg-white shadow-xs my-4">
                <i class="ri-search-eye-line fs-1 text-muted mb-2" aria-hidden="true"></i>
                <h2 class="fw-bold fs-5 text-main mb-1">{{ __("Search products, articles, categories...") }}</h2>
                @if(!empty($q))
                    <p class="text-muted fs-14 mb-0">{{ __("Search word is too short") }}</p>
                @endif
            </div>
        @else
            <div class="search-tabs-nav mb-4" role="tablist" aria-label="{{ __('Search') }}">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button type="button" role="tab" id="tab-products" aria-controls="pane-products" aria-selected="{{ $defaultTab === 'products' ? 'true' : 'false' }}" tabindex="{{ $defaultTab === 'products' ? '0' : '-1' }}" class="search-tab-btn btn btn-sm rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 {{ $defaultTab === 'products' ? 'btn-primary' : 'btn-outline-secondary border' }}" data-tab-target="pane-products">
                        <i class="ri-shopping-bag-3-line" aria-hidden="true"></i>
                        <span>{{ __('Products') }}</span>
                        <span class="badge {{ $defaultTab === 'products' ? 'bg-white text-dark' : 'bg-light text-muted border' }} rounded-1 ms-1 fa-num">{{ $products->total() }}</span>
                    </button>

                    <button type="button" role="tab" id="tab-posts" aria-controls="pane-posts" aria-selected="{{ $defaultTab === 'posts' ? 'true' : 'false' }}" tabindex="{{ $defaultTab === 'posts' ? '0' : '-1' }}" class="search-tab-btn btn btn-sm rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 {{ $defaultTab === 'posts' ? 'btn-primary' : 'btn-outline-secondary border' }}" data-tab-target="pane-posts">
                        <i class="ri-article-line" aria-hidden="true"></i>
                        <span>{{ __('Posts') }}</span>
                        <span class="badge {{ $defaultTab === 'posts' ? 'bg-white text-dark' : 'bg-light text-muted border' }} rounded-1 ms-1 fa-num">{{ $posts->total() }}</span>
                    </button>

                    <button type="button" role="tab" id="tab-clips" aria-controls="pane-clips" aria-selected="{{ $defaultTab === 'clips' ? 'true' : 'false' }}" tabindex="{{ $defaultTab === 'clips' ? '0' : '-1' }}" class="search-tab-btn btn btn-sm rounded-2 px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 {{ $defaultTab === 'clips' ? 'btn-primary' : 'btn-outline-secondary border' }}" data-tab-target="pane-clips">
                        <i class="ri-video-line" aria-hidden="true"></i>
                        <span>{{ __('Video clips') }}</span>
                        <span class="badge {{ $defaultTab === 'clips' ? 'bg-white text-dark' : 'bg-light text-muted border' }} rounded-1 ms-1 fa-num">{{ $clips->total() }}</span>
                    </button>
                </div>
            </div>

            <div class="search-tab-contents">
                <div class="search-tab-pane {{ $defaultTab !== 'products' ? 'd-none' : '' }}" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
                    @if($products->total() == 0)
                        <div class="card border rounded-3 p-5 text-center bg-white shadow-xs">
                            <i class="ri-shopping-bag-3-line fs-1 text-muted mb-2" aria-hidden="true"></i>
                            <h2 class="fw-bold fs-5 text-main mb-1">{{ __("No products found matching your filters.") }}</h2>
                            <div>
                                <a href="{{ route('client.products') }}" class="btn btn-outline-primary btn-sm px-3 rounded-2 mt-2">
                                    {{ __("All products") }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="row g-3 g-md-4">
                            @foreach($products as $product)
                                <div class="col-6 col-md-4 col-lg-3">
                                    @include('client.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-2 d-flex justify-content-center">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>

                <div class="search-tab-pane {{ $defaultTab !== 'posts' ? 'd-none' : '' }}" id="pane-posts" role="tabpanel" aria-labelledby="tab-posts">
                    @if($posts->total() == 0)
                        <div class="card border rounded-3 p-5 text-center bg-white shadow-xs">
                            <i class="ri-article-line fs-1 text-muted mb-2" aria-hidden="true"></i>
                            <h2 class="fw-bold fs-5 text-main mb-1">{{ __("No articles found") }}</h2>
                            <div>
                                <a href="{{ route('client.posts') }}" class="btn btn-outline-primary btn-sm px-3 rounded-2 mt-2">
                                    {{ __("Posts") }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="row g-3 g-md-4">
                            @foreach($posts as $post)
                                <div class="col-12 col-md-6 col-lg-4">
                                    @include('client.partials.post-card', ['post' => $post])
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-2 d-flex justify-content-center">
                            {{ $posts->links() }}
                        </div>
                    @endif
                </div>

                <div class="search-tab-pane {{ $defaultTab !== 'clips' ? 'd-none' : '' }}" id="pane-clips" role="tabpanel" aria-labelledby="tab-clips">
                    @if($clips->total() == 0)
                        <div class="card border rounded-3 p-5 text-center bg-white shadow-xs">
                            <i class="ri-video-line fs-1 text-muted mb-2" aria-hidden="true"></i>
                            <h2 class="fw-bold fs-5 text-main mb-1">{{ __("No videos found.") }}</h2>
                            <div>
                                <a href="{{ route('client.welcome') }}" class="btn btn-outline-primary btn-sm px-3 rounded-2 mt-2">
                                    {{ __("Home") }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="row g-3 g-md-4">
                            @foreach($clips as $clip)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <article class="clip-card card h-100 border rounded-3 overflow-hidden d-flex flex-column bg-white shadow-xs">
                                        <div class="card-img-wrapper position-relative overflow-hidden bg-dark ratio ratio-16x9">
                                            <a href="{{ $clip->webUrl() }}" class="d-block h-100 w-100" aria-label="{{ $clip->title }}">
                                                <img src="{{ $clip->imgUrl() }}" alt="{{ $clip->title }}" class="card-img-top h-100 w-100 object-fit-cover opacity-85" loading="lazy" width="400" height="225">
                                                <div class="position-absolute top-50 start-50 translate-middle text-white fs-1">
                                                    <i class="ri-play-circle-fill"></i>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="card-body p-3 d-flex flex-column flex-grow-1">
                                            <h2 class="clip-title fs-15 fw-bold mb-2">
                                                <a href="{{ $clip->webUrl() }}" class="text-decoration-none text-main hover-primary line-clamp-2">
                                                    {{ $clip->title }}
                                                </a>
                                            </h2>
                                            <p class="text-muted fs-13 mb-3 line-clamp-2 leading-relaxed">
                                                {{ Str::limit(strip_tags($clip->body), 100) }}
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                            <a href="{{ $clip->webUrl() }}" class="btn btn-outline-primary btn-sm rounded-2 w-100 fw-medium py-1.5 d-flex align-items-center justify-content-center gap-1">
                                                <span>{{ __("Watch video") }}</span>
                                                <i class="ri-play-line" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-2 d-flex justify-content-center">
                            {{ $clips->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var buttons = Array.from(document.querySelectorAll('.search-tab-btn'));
        var panes = Array.from(document.querySelectorAll('.search-tab-pane'));
        if (buttons.length === 0) return;

        function getTargetPane(hash) {
            var raw = (hash || window.location.hash || '').replace('#', '').trim();
            if (raw === 'products' || raw === 'posts' || raw === 'clips') {
                return 'pane-' + raw;
            }
            if (raw === 'pane-products' || raw === 'pane-posts' || raw === 'pane-clips') {
                return raw;
            }
            return null;
        }

        function switchTab(btn, updateHash) {
            var targetId = btn.getAttribute('data-tab-target');
            var targetPane = document.getElementById(targetId);
            if (!targetPane) return;

            buttons.forEach(function (b) {
                var isActive = b === btn;
                b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                b.setAttribute('tabindex', isActive ? '0' : '-1');
                b.classList.toggle('btn-primary', isActive);
                b.classList.toggle('btn-outline-secondary', !isActive);
                b.classList.toggle('border', !isActive);

                var badge = b.querySelector('.badge');
                if (badge) {
                    badge.classList.toggle('bg-white', isActive);
                    badge.classList.toggle('text-dark', isActive);
                    badge.classList.toggle('bg-light', !isActive);
                    badge.classList.toggle('text-muted', !isActive);
                    badge.classList.toggle('border', !isActive);
                }
            });

            panes.forEach(function (p) {
                p.classList.toggle('d-none', p !== targetPane);
            });

            if (updateHash) {
                var cleanHash = targetId.replace('pane-', '');
                if (history.replaceState) {
                    history.replaceState(null, null, '#' + cleanHash);
                } else {
                    window.location.hash = '#' + cleanHash;
                }
            }
        }

        buttons.forEach(function (btn, idx) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                switchTab(btn, true);
            });

            btn.addEventListener('keydown', function (e) {
                var nextIdx = -1;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    nextIdx = (idx + 1) % buttons.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    nextIdx = (idx - 1 + buttons.length) % buttons.length;
                } else if (e.key === 'Home') {
                    nextIdx = 0;
                } else if (e.key === 'End') {
                    nextIdx = buttons.length - 1;
                }

                if (nextIdx !== -1) {
                    e.preventDefault();
                    buttons[nextIdx].focus();
                    switchTab(buttons[nextIdx], true);
                }
            });
        });

        var initialPane = getTargetPane();
        if (initialPane) {
            var matchingBtn = document.querySelector('[data-tab-target="' + initialPane + '"]');
            if (matchingBtn) {
                switchTab(matchingBtn, false);
            }
        }

        window.addEventListener('hashchange', function () {
            var changedPane = getTargetPane();
            if (changedPane) {
                var btn = document.querySelector('[data-tab-target="' + changedPane + '"]');
                if (btn) switchTab(btn, false);
            }
        });
    });
</script>
@endsection
