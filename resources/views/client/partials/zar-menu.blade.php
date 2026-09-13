@php
    $zarMenuItems = $zarMenuItems ?? collect(getMenuBySettingItems('index_ZarMenu_menu'));
    if ($zarMenuItems->isEmpty()) {
        $menu = \App\Models\Menu::first();
        $zarMenuItems = ($menu && $menu->items) ? collect($menu->items) : collect();
    }
    $goldPrice = $goldPrice ?? getSetting('gold');
@endphp

<!-- ZarMenu Header -->
<header class="ZarMenu live-setting sticky-top">
    <!-- Row 1: Action Icons (Home on right in RTL, Search/Cart/Menu on left) -->
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
                <a href="{{route('client.card')}}" class="zar-icon-btn position-relative" title="{{__('Cart')}}" aria-label="{{__('Cart')}}">
                    <i class="ri-shopping-bag-line"></i>
                    @if(cardCount() > 0)
                        <span class="badge bg-danger rounded-pill position-absolute top-0 start-0 translate-middle p-1 fs-11" style="transform: scale(0.75);">
                            {{cardCount()}}
                        </span>
                    @endif
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
                <form action="{{route('client.search')}}" method="GET" class="side-data">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" placeholder="{{__('Search')}}..." required>
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="ri-search-2-line"></i>
                        </button>
                    </div>
                </form>
            </li>
            <li class="py-1 border-bottom">
                <a href="{{route('client.profile')}}" class="d-flex align-items-center gap-2 py-1 text-secondary text-decoration-none">
                    <i class="ri-account-circle-line text-warning fs-18"></i>
                    <span>{{ auth('customer')->check() ? auth('customer')->user()->name : __('Guest') }}</span>
                </a>
            </li>
            <li class="py-1 border-bottom">
                <a href="{{route('client.profile')}}" class="d-flex align-items-center gap-2 py-1 text-secondary text-decoration-none">
                    <i class="ri-trophy-line text-warning fs-18"></i>
                    <span>{{__('Credit')}}</span>
                </a>
            </li>
            @if(isset($zarMenuItems) && $zarMenuItems->isNotEmpty())
                @foreach($zarMenuItems as $item)
                    <li>
                        <a href="{{$item->webUrl()}}" class="d-block py-1.5 text-dark text-decoration-none">
                            {{$item->title}}
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </nav>
</div>
