@php
    $zarMenuItems = $zarMenuItems ?? collect(getMenuBySettingItems('index_ZarMenu_menu'));
    if ($zarMenuItems->isEmpty()) {
        $menu = \App\Models\Menu::first();
        $zarMenuItems = ($menu && $menu->items) ? collect($menu->items) : collect();
    }
    $goldPrice = $goldPrice ?? getSetting('gold');
    $socialsRaw = getSettingsGroup('social_');
    $socials = is_array($socialsRaw) ? $socialsRaw : [];
    $tel = getSetting('tel') ?: getSetting('phone');
@endphp

<header class="ZarMenu live-setting sticky-top">
    <div class="zar-top-bar">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{route('client.welcome')}}" class="zar-icon-btn" title="{{config('app.name')}}">
                <i class="ri-home-7-line"></i>
            </a>

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

    <div class="zar-sub-bar">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{route('client.profile')}}" class="d-inline-flex align-items-center gap-1.5 text-dark text-decoration-none">
                <i class="ri-account-circle-line fs-18"></i>
                <span class="fs-14 fw-medium">{{auth('customer')->check() ? auth('customer')->user()->name : __('Guest')}}</span>
            </a>

            <span class="d-inline-flex align-items-center gap-1.5 text-dark">
                <i class="ri-line-chart-line fs-18"></i>
                <span class="fs-14 fw-bold font-monospace">{{toPersianDigits(number_format((int) $goldPrice))}}</span>
            </span>
        </div>
    </div>
</header>

<div id="zar-menu" class="zar-drawer-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}">
    <div class="zar-drawer-panel">
        <div class="zar-drawer-header">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('upload/images/logo.svg') }}" onerror="this.src='{{ asset('assets/default/logo.png') }}'" alt="{{ config('app.name') }}" height="26">
                <div>
                    <div class="fw-bold fs-15 text-dark lh-1">{{ config('app.name') }}</div>
                    <div class="fs-11 text-muted">{{ __('Authentic Gold & Timeless Jewelry') }}</div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-light rounded-circle zar-close-btn" id="close-zar-btn" aria-label="{{ __('Close') }}">
                <i class="ri-close-line fs-18"></i>
            </button>
        </div>

        <div class="zar-drawer-body">
            @if(auth('customer')->check())
                <div class="zar-customer-card mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="zar-avatar">
                                <i class="ri-user-3-line"></i>
                            </span>
                            <div>
                                <div class="fw-bold fs-13 text-dark">{{ auth('customer')->user()->name ?: __('Customer') }}</div>
                                <div class="fs-11 text-muted font-monospace" dir="ltr">{{ auth('customer')->user()->mobile }}</div>
                            </div>
                        </div>
                        <a href="{{ route('client.profile') }}" class="btn btn-xs btn-outline-warning rounded-pill px-2.5 py-1 fs-11">
                            {{ __('Profile') }}
                        </a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle fs-12">
                        <span class="text-muted d-inline-flex align-items-center gap-1">
                            <i class="ri-trophy-line text-warning"></i>
                            <span>{{ __('Credit') }}:</span>
                        </span>
                        <span class="fw-bold text-dark font-monospace">{{ toPersianDigits(number_format((int) (auth('customer')->user()->credit ?? 0))) }} {{ config('app.currency.symbol') }}</span>
                    </div>
                </div>
            @else
                <div class="zar-guest-card mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="zar-avatar">
                                <i class="ri-user-line"></i>
                            </span>
                            <div>
                                <div class="fw-bold fs-13 text-dark">{{ __('Guest') }}</div>
                                <div class="fs-11 text-muted">{{ __('Welcome to our gallery') }}</div>
                            </div>
                        </div>
                        <a href="{{ route('client.sign-in') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fs-12 fw-medium">
                            {{ __('Sign in') }}
                        </a>
                    </div>
                </div>
            @endif

            @if(!empty($goldPrice) && (int) $goldPrice > 0)
                <div class="zar-gold-rate-card mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="d-inline-flex align-items-center gap-1.5 fs-12 fw-medium text-dark">
                            <i class="ri-line-chart-line text-warning fs-16"></i>
                            <span>{{ __('Live gold price') }}</span>
                        </span>
                        <span class="zar-gold-rate-val font-monospace fs-13 fw-bold" dir="ltr">
                            {{ toPersianDigits(number_format((int) $goldPrice)) }} <small class="fw-normal">{{ config('app.currency.symbol') }}</small>
                        </span>
                    </div>
                </div>
            @endif

            <div class="zar-search-box mb-3">
                <form action="{{ route('client.search') }}" method="GET" class="side-data m-0">
                    <div class="position-relative">
                        <input type="search" name="q" id="zar-search-input" class="form-control rounded-pill pe-5 fs-13 zar-search-field" placeholder="{{ __('Search') }}..." required>
                        <button type="submit" class="btn btn-link position-absolute top-50 start-0 translate-middle-y text-muted p-0 ps-3 zar-search-submit" aria-label="{{ __('Search') }}">
                            <i class="ri-search-2-line fs-16"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="zar-nav-section mb-3">
                <ul class="zar-nav-list list-unstyled m-0 p-0">
                    <li>
                        <a href="{{ route('client.welcome') }}" class="zar-nav-link">
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="ri-home-7-line zar-nav-icon"></i>
                                <span>{{ __('Home') }}</span>
                            </span>
                            <i class="ri-arrow-left-s-line zar-nav-arrow"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('client.card') }}" class="zar-nav-link">
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="ri-shopping-bag-line zar-nav-icon"></i>
                                <span>{{ __('Cart') }}</span>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                @if(cardCount() > 0)
                                    <span class="badge bg-danger rounded-pill fs-11">{{ cardCount() }}</span>
                                @endif
                                <i class="ri-arrow-left-s-line zar-nav-arrow"></i>
                            </div>
                        </a>
                    </li>
                    @if(isset($zarMenuItems) && $zarMenuItems->isNotEmpty())
                        @foreach($zarMenuItems as $item)
                            <li>
                                <a href="{{ $item->webUrl() }}" class="zar-nav-link">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <i class="ri-gem-line zar-nav-icon"></i>
                                        <span>{{ $item->title }}</span>
                                    </span>
                                    <i class="ri-arrow-left-s-line zar-nav-arrow"></i>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>

            @if(config('app.xlang.active'))
                <div class="zar-lang-row pt-2 pb-3 mb-2 border-top border-light-subtle d-flex align-items-center justify-content-center gap-2">
                    @foreach(\App\Models\XLang::all() as $lang)
                        @if($lang->tag != app()->getLocale())
                            <a href="/{{ $lang->tag }}" class="btn btn-sm btn-light rounded-pill px-2.5 py-1 fs-12">
                                {{ $lang->emoji }} {{ $lang->title ?? $lang->tag }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="zar-drawer-footer">
            @if(!empty($tel))
                <a href="tel:{{ $tel }}" class="btn btn-outline-warning w-100 rounded-pill py-2 mb-2 d-inline-flex align-items-center justify-content-center gap-2 fs-13 fw-bold zar-contact-btn" dir="ltr">
                    <i class="ri-phone-line fs-16"></i>
                    <span>{{ $tel }}</span>
                </a>
            @endif

            @if(!empty($socials))
                <div class="d-flex align-items-center justify-content-center gap-2">
                    @foreach($socials as $k => $social)
                        <a href="{{ $social }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light rounded-circle zar-social-icon d-flex align-items-center justify-content-center text-dark" aria-label="{{ $k }}">
                            <i class="ri-{{ $k }}-line fs-16"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
