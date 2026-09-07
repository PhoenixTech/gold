@extends('website.inc.website-layout')

@section('title')
    {{config('app.name')}} - {{getSetting('subtitle') ?: __('Online Gold & Jewelry Store')}}
@endsection

@section('hide-header', true)
@section('hide-footer', true)

@section('custom-head')
<style>
.ZarMenu {
    background: #ffffff;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.white-space-nowrap {
    white-space: nowrap !important;
}
.hover-bg-warning-subtle:hover {
    background-color: rgba(219, 154, 0, 0.12) !important;
    color: var(--xshop-primary, #db9a00) !important;
}
.hover-text-primary:hover {
    color: var(--xshop-primary, #db9a00) !important;
}
#zar-menu {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(6px);
    z-index: 9999;
}
#zar-menu nav {
    height: 100%;
}
#zar-menu ul {
    background: var(--xshop-background, #ffffff);
    position: absolute;
    inset-inline-end: 0;
    top: 0;
    bottom: 0;
    width: 300px;
    max-width: 85vw;
    overflow-y: auto;
    list-style: none;
    padding: 1.25rem 1rem;
    margin: 0;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
}
#zar-menu ul li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
#zar-menu ul li a {
    display: block;
    color: var(--xshop-text, #111111);
    text-decoration: none;
    font-weight: 500;
}
.BottomBar {
    margin-bottom: 5.5rem;
}
.BottomBar ul li a {
    font-size: 34px;
    color: var(--xshop-primary, #db9a00);
    opacity: 0.8;
    transition: all 0.2s ease;
}
.BottomBar ul li a:hover {
    opacity: 1;
    transform: scale(1.12);
}
.WTFFooter {
    max-width: 640px;
    margin-inline: auto;
}
.natalia-woman-full-body {
    max-height: 540px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 14px 28px rgba(0, 0, 0, 0.12));
    transition: transform 0.3s ease;
}
.natalia-woman-full-body:hover {
    transform: scale(1.02);
}
@media (max-width: 767.98px) {
    .natalia-woman-full-body {
        max-height: 420px;
    }
}
</style>
@endsection

@section('content')
<div class="old-homepage-wrapper position-relative">

    <!-- Top Notice Banner for Archived Design Preview -->
    <div class="old-home-notice bg-dark text-white py-1.5 px-3 fs-12 border-bottom border-warning border-2">
        <div class="{{gfx()['container']}} d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="d-flex align-items-center gap-2">
                <i class="ri-history-line text-warning fs-15"></i>
                <span class="fw-semibold">{{__('Old Homepage (Archived Design)')}}</span>
            </span>
            <a href="{{route('client.welcome')}}" class="badge bg-warning text-dark text-decoration-none py-1 px-2.5 rounded-pill fw-bold fs-11 d-inline-flex align-items-center gap-1">
                <span>{{__('View new homepage')}}</span>
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
    </div>

    <!-- ZarMenu (Old Homepage Header: clean horizontal single row) -->
    <nav class="ZarMenu live-setting sticky-top py-2.5">
        <div class="{{gfx()['container']}}">
            <div class="d-flex align-items-center justify-content-between flex-row flex-nowrap w-100 gap-2">
                <!-- Home / Brand Icon (Start) -->
                <div class="d-flex align-items-center flex-shrink-0">
                    <a href="{{route('client.welcome')}}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark p-1 rounded" title="{{config('app.name')}}">
                        <i class="ri-home-7-line text-warning fs-3 lh-1"></i>
                        <span class="fw-bold fs-15 d-none d-sm-inline">{{config('app.name')}}</span>
                    </a>
                </div>

                <!-- Info & Status Items (Desktop: Gold Price, Credit, Profile) -->
                <div class="d-none d-lg-flex align-items-center justify-content-center gap-4 flex-grow-1 mx-3 text-secondary fs-13">
                    <span class="d-inline-flex align-items-center gap-1.5 white-space-nowrap">
                        <i class="ri-line-chart-line text-warning fs-17"></i>
                        <span>{{__("Gold price")}}:</span>
                        <strong class="text-dark font-monospace">{{number_format((int) $goldPrice)}} {{config('app.currency.symbol')}}</strong>
                    </span>

                    <a href="{{route('client.profile')}}" class="d-inline-flex align-items-center gap-1.5 text-secondary text-decoration-none hover-text-primary white-space-nowrap">
                        <i class="ri-trophy-line text-warning fs-17"></i>
                        <span>{{__("Credit")}}</span>
                    </a>

                    <a href="{{route('client.profile')}}" class="d-inline-flex align-items-center gap-1.5 text-secondary text-decoration-none hover-text-primary white-space-nowrap">
                        <i class="ri-account-circle-line text-warning fs-17"></i>
                        <span>
                            @if(auth('customer')->check())
                                {{auth('customer')->user()->name}}
                            @else
                                {{__("Guest")}}
                            @endif
                        </span>
                    </a>
                </div>

                <!-- Mobile Compact Gold Price Badge -->
                <div class="d-flex d-lg-none align-items-center flex-shrink-0">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fs-12">
                        <i class="ri-line-chart-line text-warning"></i>
                        <span class="font-monospace fw-bold">{{number_format((int) $goldPrice)}}</span>
                        <span class="fs-10 text-muted">{{config('app.currency.symbol')}}</span>
                    </span>
                </div>

                <!-- Action Buttons (Search, Cart, Profile on Mobile, Menu Toggle) (End) -->
                <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
                    <a href="{{route('client.profile')}}" class="btn btn-sm btn-light border-0 rounded-circle d-inline-flex d-lg-none align-items-center justify-content-center p-2 text-secondary hover-bg-warning-subtle" title="{{auth('customer')->check() ? auth('customer')->user()->name : __('Guest')}}" style="width: 38px; height: 38px;">
                        <i class="ri-account-circle-line fs-18"></i>
                    </a>

                    <button type="button" class="btn btn-sm btn-light border-0 rounded-circle d-inline-flex align-items-center justify-content-center p-2 text-secondary hover-bg-warning-subtle" id="open-zar-2" title="{{__('Search')}}" aria-label="{{__('Search')}}" style="width: 38px; height: 38px;">
                        <i class="ri-search-line fs-18"></i>
                    </button>

                    <a href="{{route('client.card')}}" class="btn btn-sm btn-light border-0 rounded-circle d-inline-flex align-items-center justify-content-center p-2 text-secondary hover-bg-warning-subtle position-relative" title="{{__('Cart')}}" aria-label="{{__('Cart')}}" style="width: 38px; height: 38px;">
                        <i class="ri-shopping-bag-4-line fs-18"></i>
                    </a>

                    <button type="button" class="btn btn-sm btn-light border-0 rounded-circle d-inline-flex align-items-center justify-content-center p-2 text-secondary hover-bg-warning-subtle" id="open-zar-1" title="{{__('Menu')}}" aria-label="{{__('Menu')}}" style="width: 38px; height: 38px;">
                        <i class="ri-menu-line fs-18"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Zar Drawer Menu -->
    <div id="zar-menu">
        <nav>
            <ul>
                <li class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                    <span class="fw-bold fs-15 text-dark">{{config('app.name')}}</span>
                    <button type="button" class="btn btn-sm btn-light rounded-circle p-1" id="close-zar-btn" aria-label="{{__('Close')}}">
                        <i class="ri-close-line fs-18"></i>
                    </button>
                </li>
                @if(config('app.xlang.active'))
                    <li class="py-2">
                        @foreach(\App\Models\XLang::all() as $lang)
                            @if($lang->tag != app()->getLocale())
                                <a href="/{{$lang->tag}}" class="d-inline-block px-1">
                                    {{$lang->emoji}}
                                </a>
                            @endif
                        @endforeach
                    </li>
                @endif
                <li class="py-2">
                    <form action="{{route('client.search')}}" class="side-data">
                        <div class="input-group">
                            <input type="search" name="q" class="form-control" placeholder="{{__('Search')}}...">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="ri-search-2-line"></i>
                            </button>
                        </div>
                    </form>
                </li>
                <li class="d-lg-none py-1 border-bottom">
                    <a href="{{route('client.profile')}}" class="d-flex align-items-center gap-2 py-1 text-secondary text-decoration-none">
                        <i class="ri-account-circle-line text-warning fs-18"></i>
                        <span>{{ auth('customer')->check() ? auth('customer')->user()->name : __('Guest') }}</span>
                    </a>
                </li>
                <li class="d-lg-none py-1 border-bottom">
                    <a href="{{route('client.profile')}}" class="d-flex align-items-center gap-2 py-1 text-secondary text-decoration-none">
                        <i class="ri-trophy-line text-warning fs-18"></i>
                        <span>{{__("Credit")}}</span>
                    </a>
                </li>
                @foreach($zarMenuItems as $item)
                    <li>
                        <a href="{{$item->webUrl()}}" class="d-block py-1.5 text-dark text-decoration-none">
                            {{$item->title}}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    <!-- WTFIndex (Category Tabs Explorer) -->
    @if(isset($mainCategories) && $mainCategories->isNotEmpty())
        <section class="WTFIndex live-setting my-4">
            <!-- Category Tabs Bar -->
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

            <!-- Category Content Panels -->
            <div class="py-2">
                @foreach($mainCategories as $k => $mainCategory)
                    @php($words = explode(' ', $mainCategory->name))
                    <div class="{{gfx()['container']}} wtf-section" id="wtf-{{$mainCategory->id}}" @if($k == 0) style="display: block" @endif>
                        <div class="row g-3 g-md-4">
                            @foreach($mainCategory->children()->where('hide', 0)->orderBy('sort')->get() as $childCategory)
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

    <!-- Natalia2Categories (About / Intro Banner) -->
    @if(!empty($nataliaText))
        <section class="Natalia2Categories live-setting position-relative py-5 bg-light-subtle border-top border-bottom overflow-hidden">
            <div class="{{gfx()['container']}}">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-md-7 col-lg-8">
                        <div class="main-dir card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white position-relative z-2">
                            {!! $nataliaText !!}
                        </div>
                    </div>
                    <div class="col-12 col-md-5 col-lg-4 text-center d-flex align-items-center justify-content-center">
                        <img src="{{asset('upload/images/index.Natalia2Categories.webp')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}" class="natalia-woman-full-body img-fluid" loading="lazy">
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- NeginNews (News & Education Banner) -->
    @if(!empty($neginTitle) || !empty($neginText))
        <section class="NeginNews live-setting py-5 bg-white border-bottom">
            <div class="{{gfx()['container']}}">
                <div class="row align-items-center g-4">
                    @if(!empty($neginTitle))
                        <div class="col-12 col-md-7 col-lg-8 main-dir">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle">
                                {!! $neginTitle !!}
                            </div>
                        </div>
                    @endif
                    <div class="col-12 col-md-5 col-lg-4 text-center">
                        <div class="position-relative overflow-hidden rounded-4 shadow-sm bg-light p-2">
                            <img src="{{asset('upload/images/index.NeginNews.webp')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}" class="img-fluid rounded-4 object-fit-cover w-100" style="max-height: 280px;">
                        </div>
                    </div>
                    @if(!empty($neginText))
                        <div class="col-12 btm mt-4">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                {!! $neginText !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <!-- BottomBar (Social Icons) -->
    @if(!empty($socials))
        <section class="BottomBar live-setting py-4">
            <div class="{{gfx()['container']}} text-center">
                <ul class="d-flex align-items-center justify-content-center gap-4 list-unstyled m-0 p-0">
                    @foreach($socials as $k => $social)
                        <li>
                            <a href="{{$social}}" target="_blank" rel="noopener noreferrer" class="d-inline-block text-decoration-none">
                                <i class="ri-{{$k}}-line"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <!-- WTFFooter (Fixed Bottom Category Navigation) -->
    @if(isset($footerCategories) && $footerCategories->isNotEmpty())
        <nav class="WTFFooter fixed-bottom-categories" aria-label="Footer Categories">
            @foreach($footerCategories as $k => $mainCategory)
                <a class="wtfooter-btn" href="{{$mainCategory->webUrl()}}">
                    @if($k == 3)
                        <img id="ballon" src="{{asset('assets/default/ballon.webp')}}" alt="">
                    @endif
                    <img class="cat-icon" src="{{$mainCategory->svgUrl()}}" alt="{{$mainCategory->name}}">
                    <span>{{$mainCategory->name}}</span>
                </a>
            @endforeach
        </nav>
    @endif

</div>

<!-- Old Homepage Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Zar Menu Toggle
    const zarMenu = document.getElementById('zar-menu');
    const openZar1 = document.getElementById('open-zar-1');
    const openZar2 = document.getElementById('open-zar-2');
    const closeZar = document.getElementById('close-zar-btn');

    function openZar() {
        if (zarMenu) {
            zarMenu.style.display = 'block';
            setTimeout(function () {
                document.addEventListener('click', handleZarOutsideClick);
            }, 50);
        }
    }

    function closeZarMenu() {
        if (zarMenu) {
            zarMenu.style.display = 'none';
            document.removeEventListener('click', handleZarOutsideClick);
        }
    }

    function handleZarOutsideClick(e) {
        const zarUl = document.querySelector('#zar-menu ul');
        if (zarUl && !zarUl.contains(e.target) && !e.target.closest('#open-zar-1') && !e.target.closest('#open-zar-2')) {
            closeZarMenu();
        }
    }

    if (openZar1) openZar1.addEventListener('click', openZar);
    if (openZar2) openZar2.addEventListener('click', openZar);
    if (closeZar) closeZar.addEventListener('click', closeZarMenu);

    // WTF Category Tabs
    const tabBtns = document.querySelectorAll('#wtf-main-btns .main-dir');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');

            const sections = document.querySelectorAll('.wtf-section');
            sections.forEach(function (sec) { sec.style.display = 'none'; });

            const targetId = this.getAttribute('data-id');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    target.style.display = 'block';
                }
            }
        });
    });
});
</script>
@endsection
