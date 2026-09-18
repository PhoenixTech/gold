@extends('website.inc.website-layout')

@section('title')
    {{config('app.name')}} - {{getSetting('subtitle') ?: __('Online Gold & Jewelry Store')}}
@endsection

@section('hide-header', true)
@section('hide-footer', true)

@section('custom-head')
<style>
/* Base Reset & Variables */
body {
    background-color: #ffffff;
    color: #111111;
    padding-bottom: 6rem;
}

/* Old Design Top Archived Notice */
.old-home-notice {
    background: #1e293b;
    color: #f8fafc;
    font-size: 12px;
}

/* ZarMenu Header (Row 1 & Row 2) */
.ZarMenu {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}
.zar-top-bar {
    padding: 0.6rem 1rem;
}
.zar-sub-bar {
    padding: 0.35rem 1rem 0.6rem 1rem;
}
.zar-icon-btn {
    background: none;
    border: none;
    padding: 0;
    color: #222222;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 24px;
}
.zar-icon-btn:hover {
    color: var(--xshop-primary, #db9a00);
}

/* WTF Category Tabs (Row 3) */
.wtf-tabs {
    direction: ltr;
    display: flex;
    width: 100%;
    margin: 0;
    padding: 0;
}
.wtf-tab-btn {
    flex: 1;
    border: none;
    padding: 0.8rem 0.5rem;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    line-height: 1.2;
    transition: opacity 0.2s ease;
}
.wtf-tab-btn:hover {
    opacity: 0.9;
}

/* WTF Category Grid */
.WTFIndex {
    background: #ffffff;
}
.cat-item-link {
    text-decoration: none;
    color: #111111;
    display: block;
}
.cat-item-link:hover .cat-thumb-img {
    transform: scale(1.03);
}
.cat-img-box {
    width: 100%;
    aspect-ratio: 1 / 1.22;
    overflow: hidden;
    background-color: #f3f4f6;
}
.cat-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 0;
    transition: transform 0.25s ease;
}
.cat-item-title {
    font-size: 13px;
    font-weight: 500;
    color: #000000;
    text-align: center;
    margin-top: 0.5rem;
    margin-bottom: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}
@media (max-width: 575.98px) {
    .cat-item-title {
        font-size: 11.5px;
    }
}

/* Natalia2Categories (Model & text section) */
.Natalia2Categories {
    background: #ffffff;
}
.natalia-text-content ol,
.natalia-text-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.natalia-text-content li {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 0.6rem;
    color: #111111;
}
.natalia-text-content a {
    color: #111111;
    text-decoration: none;
}
.natalia-woman-img {
    max-height: 280px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
}

/* WTFFooter (Fixed Floating Bottom Bar) */
.WTFFooter {
    position: fixed;
    bottom: 1.25rem;
    right: 1.25rem;
    left: 1.25rem;
    max-width: 480px;
    margin-inline: auto;
    border: 2px solid #b5b5b5;
    background: #ffffff;
    border-radius: 14px;
    padding: 0.45rem 0.6rem;
    display: flex;
    align-items: center;
    justify-content: space-evenly;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
    z-index: 1040;
}
.wtfooter-btn {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #111111;
    position: relative;
    min-width: 55px;
}
.wtfooter-btn .cat-icon {
    max-height: 28px;
    width: auto;
    max-width: 34px;
    margin-bottom: 3px;
    object-fit: contain;
}
.wtfooter-btn .cat-name {
    font-size: 11.5px;
    font-weight: 500;
    color: #111111;
    white-space: nowrap;
}
.wtfooter-btn #ballon {
    width: 32px;
    position: absolute;
    height: auto;
    inset-inline-end: 2px;
    top: -18px;
    pointer-events: none;
    z-index: 2;
}

/* Slide-out Zar Drawer Menu */
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
</style>
@endsection

@section('content')
<div class="old-homepage-wrapper position-relative">

    <!-- Top Notice Banner for Archived Design Preview -->
    <div class="old-home-notice py-1.5 px-3 border-bottom border-warning border-2">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
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

    <!-- ZarMenu (Old Homepage Header matching zhonella-core.jpg) -->
    <header class="ZarMenu live-setting sticky-top">
        <!-- Row 1: Action Icons (Home on right, Search/Cart/Menu on left) -->
        <div class="zar-top-bar">
            <div class="container d-flex align-items-center justify-content-between">
                <!-- Start in RTL (Right): Home Icon -->
                <a href="{{route('client.welcome')}}" class="zar-icon-btn" title="{{config('app.name')}}">
                    <i class="ri-home-7-line"></i>
                </a>

                <!-- End in RTL (Left): Search, Cart, Menu Icons -->
                <div class="d-flex align-items-center gap-4">
                    <button type="button" class="zar-icon-btn" id="open-zar-2" title="{{__('Search')}}" aria-label="{{__('Search')}}">
                        <i class="ri-search-line"></i>
                    </button>
                    <a href="{{route('client.card')}}" class="zar-icon-btn" title="{{__('Cart')}}" aria-label="{{__('Cart')}}">
                        <i class="ri-shopping-bag-line"></i>
                    </a>
                    <button type="button" class="zar-icon-btn" id="open-zar-1" title="{{__('Menu')}}" aria-label="{{__('Menu')}}">
                        <i class="ri-menu-line"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Row 2: Info & Status Bar (Profile, Credit, Gold Price) -->
        <div class="zar-sub-bar">
            <div class="container d-flex align-items-center justify-content-between">
                <!-- Right in RTL: User Profile / Guest -->
                <a href="{{route('client.profile')}}" class="d-inline-flex align-items-center gap-1.5 text-dark text-decoration-none">
                    <i class="ri-account-circle-line fs-18"></i>
                    <span class="fs-14 fw-medium">{{auth('customer')->check() ? auth('customer')->user()->name : __('Guest')}}</span>
                </a>

                <!-- Middle in RTL: Credit -->
                <a href="{{route('client.profile')}}" class="d-inline-flex align-items-center gap-1.5 text-dark text-decoration-none">
                    <i class="ri-trophy-line fs-18"></i>
                    <span class="fs-14 fw-medium">{{__('Credit')}}</span>
                </a>

                <!-- Left in RTL: Live Gold Price -->
                <span class="d-inline-flex align-items-center gap-1.5 text-dark">
                    <i class="ri-line-chart-line fs-18"></i>
                    <span class="fs-14 fw-bold font-monospace">{{number_format((int) $goldPrice)}}</span>
                    <span class="fs-12 text-muted">{{config('app.currency.symbol') ?: 'تومان'}}</span>
                </span>
            </div>
        </div>

        <!-- Row 3: Category Tabs (طلا on left, نقره on right) -->
        @if(isset($mainCategories) && $mainCategories->isNotEmpty())
            <div id="wtf-main-btns" class="wtf-tabs">
                @foreach($mainCategories as $k => $mainCategory)
                    @php
                        $tabName = explode(' ', $mainCategory->name)[0];
                        $defaultBg = ($k == 0) ? '#caa867' : '#cccccc';
                        $bgColor = $mainCategory->bg_color ?: $defaultBg;
                        $textColor = $mainCategory->color ?: '#111111';
                    @endphp
                    <button type="button" 
                            class="wtf-tab-btn @if($k == 0) active @endif" 
                            style="background-color: {{$bgColor}}; color: {{$textColor}};"
                            data-id="#wtf-{{$mainCategory->id}}">
                        {{$tabName}}
                    </button>
                @endforeach
            </div>
        @endif
    </header>

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
                        <span>{{__('Credit')}}</span>
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

    <!-- WTFIndex (Category Grid: 4 columns x 3 rows with clean square thumbs & titles) -->
    @if(isset($mainCategories) && $mainCategories->isNotEmpty())
        <section class="WTFIndex live-setting pt-4 pb-2">
            @foreach($mainCategories as $k => $mainCategory)
                @php($words = explode(' ', $mainCategory->name))
                @php($metalParam = $mainCategory->metal ?? ($k == 1 ? 'silver' : 'gold'))
                @php($childCats = is_iterable($mainCategory->children) ? $mainCategory->children : ($mainCategory->relationLoaded('children') ? $mainCategory->children->where('hide', 0) : $mainCategory->children()->where('hide', 0)->get()))
                <div class="wtf-section container px-2 px-sm-3" id="wtf-{{$mainCategory->id}}" @if($k == 0) style="display: block" @else style="display: none" @endif>
                    <div class="row g-2 g-sm-3" dir="rtl">
                        @foreach($childCats as $childCategory)
                            <div class="col-3 text-center mb-3">
                                <a href="{{ route('client.category', ['category' => $childCategory->slug, 'metal' => $metalParam]) }}" class="d-block text-decoration-none text-dark cat-item-link">
                                    <div class="cat-img-box d-flex align-items-center justify-content-center">
                                        @php($hasMetalImg = ($metalParam === 'silver' ? !empty($childCategory->silver_image) : !empty($childCategory->image)))
                                        @if($hasMetalImg)
                                            <img src="{{$childCategory->imgForMetal($metalParam)}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalForMetal($metalParam)}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @elseif(!empty($childCategory->image))
                                            <img src="{{$childCategory->imgUrl()}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalUrl()}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @elseif(!empty($childCategory->icon))
                                            <div class="d-flex flex-column align-items-center justify-content-center w-100 h-100 p-2 text-center" style="background: linear-gradient(135deg, #fafafa 0%, #f1f3f5 100%);">
                                                <i class="{{$childCategory->icon}} fs-1 {{ $metalParam === 'silver' ? 'text-secondary' : 'text-warning' }} opacity-85"></i>
                                            </div>
                                        @else
                                            <img src="{{$childCategory->imgUrl()}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalUrl()}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @endif
                                    </div>
                                    <h5 class="cat-item-title">
                                        {{implode(' ', array_diff(explode(' ', $childCategory->name), $words)) ?: $childCategory->name}}
                                    </h5>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    <!-- Natalia2Categories (Collection Banner with Model on right) -->
    @if(!empty($nataliaText))
        <section class="Natalia2Categories live-setting position-relative py-4 bg-white overflow-hidden">
            <div class="container px-3">
                <div class="row align-items-center g-3" style="direction: ltr;">
                    <!-- Left: Styled text links -->
                    <div class="col-7 col-sm-8 text-start">
                        <div class="natalia-text-content ps-2" dir="rtl">
                            {!! $nataliaText !!}
                        </div>
                    </div>
                    <!-- Right: Model Image -->
                    <div class="col-5 col-sm-4 text-end">
                        <img src="{{asset('upload/images/index.Natalia2Categories.webp')}}" 
                             onerror="this.onerror=null;this.src='{{asset('assets/default/logo.png')}}';" 
                             alt="{{config('app.name')}}" 
                             class="natalia-woman-img img-fluid" 
                             loading="lazy">
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- WTFFooter (Fixed Floating Bottom Bar matching zhonella-core.jpg) -->
    @if(isset($footerCategories) && $footerCategories->isNotEmpty())
        <nav class="WTFFooter fixed-bottom-categories" aria-label="Footer Categories">
            @foreach($footerCategories as $k => $footerCat)
                <a class="wtfooter-btn" href="{{$footerCat->webUrl()}}">
                    @if($k == 3 && file_exists(public_path('assets/default/ballon.webp')))
                        <img id="ballon" src="{{asset('assets/default/ballon.webp')}}" alt="ballon" loading="lazy">
                    @endif
                    @if($footerCat->id == 61)
                        <img class="cat-icon" src="{{Storage::url('categories/1741370193-هدیه طلا.jpg')}}" alt="{{$footerCat->name}}">
                    @else
                        <img class="cat-icon" src="{{$footerCat->svgUrl()}}" alt="{{$footerCat->name}}">
                    @endif
                    <span class="cat-name">{{$footerCat->name}}</span>
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
    const tabBtns = document.querySelectorAll('#wtf-main-btns .wtf-tab-btn');
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
