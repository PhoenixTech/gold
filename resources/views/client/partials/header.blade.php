@php
    $menu = \App\Models\Menu::first();
    $menuItems = ($menu && $menu->items) ? $menu->items : [];
    $goldPrice = getSetting('gold');
    $socialsRaw = getSettingsGroup('social_');
    $socials = is_array($socialsRaw) ? $socialsRaw : [];
    $tel = getSetting('tel') ?: getSetting('phone');
@endphp

<nav id="AplMenu" class="site-header navbar-sticky">
    <div class="{{gfx()['container']}} h-100">
        <div class="header-navbar-grid d-flex align-items-center justify-content-between h-100 py-1">

            <!-- Section 1: Logo + Brand Text -->
            <div class="nav-section-brand d-flex align-items-center flex-shrink-0">
                <a href="{{url('/')}}" class="d-flex align-items-center gap-2 text-decoration-none" title="{{config('app.name')}}">
                    <img src="{{asset('upload/images/logo.svg')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}" height="32" class="header-logo">
                    <span class="fw-bold text-white fs-16 brand-title">{{config('app.name')}}</span>
                </a>
            </div>

            <!-- Section 2: Menus (Desktop Navigation) -->
            <div class="nav-section-menu d-none d-lg-flex align-items-center justify-content-center flex-grow-1 mx-3">
                <ul class="nav-menu-list d-flex align-items-center gap-1 list-unstyled m-0 p-0">
                    @foreach($menuItems as $item)
                        @php
                            $destModel = $item->dest;
                            if (!$destModel && $item->menuable_id) {
                                if ($item->menuable_type === \App\Models\Category::class || in_array(class_basename($item->menuable_type), ['Category', 'category'], true)) {
                                    $destModel = \App\Models\Category::find($item->menuable_id);
                                } elseif ($item->menuable_type === \App\Models\Group::class || in_array(class_basename($item->menuable_type), ['Group', 'group'], true)) {
                                    $destModel = \App\Models\Group::find($item->menuable_id);
                                }
                            }
                            $hasSubMenu = $destModel && ($destModel instanceof \App\Models\Category || $destModel instanceof \App\Models\Group);
                            $itemLink = $destModel ? $destModel->webUrl() : $item->webUrl();
                        @endphp
                        <li class="nav-item position-relative">
                            <a href="{{$itemLink}}" class="nav-link-custom">
                                <span>{{$item->title}}</span>
                                @if($hasSubMenu)
                                    <i class="ri-arrow-down-s-line fs-14 ms-0.5 opacity-75"></i>
                                @endif
                            </a>
                            @if($hasSubMenu)
                                <div class="sub-menu">
                                    <div class="{{gfx()['container']}}">
                                        <div>
                                            <h4>
                                                <i class="ri-grid-fill text-warning me-1.5 fs-15"></i>
                                                {{$destModel->name}}
                                            </h4>
                                            <ul>
                                                @if($destModel->children()->count() == 0)
                                                    @foreach($destModel->published(5,'view') as $itm)
                                                        <li>
                                                            <a href="{{$itm->webUrl()}}">
                                                                <i class="ri-arrow-left-s-line text-warning-subtle me-1 fs-12"></i>
                                                                {{\Illuminate\Support\Str::limit($itm->title ?? $itm->name, 28)}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    @foreach($destModel->children()->where('hide', false)->get() as $itm)
                                                        <li>
                                                            <a href="{{$itm->webUrl()}}">
                                                                <i class="ri-arrow-left-s-line text-warning-subtle me-1 fs-12"></i>
                                                                {{$itm->name}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                        </div>
                                        <div>
                                            <h4>
                                                <i class="ri-sparkling-fill text-warning me-1.5 fs-15"></i>
                                                {{__("Latest ")}} {{$destModel->name}}
                                            </h4>
                                            <ul>
                                                @php
                                                    $latestItems = $destModel->published(5);
                                                @endphp
                                                @if($latestItems->isNotEmpty())
                                                    @foreach($latestItems as $itm)
                                                        <li>
                                                            <a href="{{$itm->webUrl()}}">
                                                                <i class="ri-arrow-left-s-line text-warning-subtle me-1 fs-12"></i>
                                                                {{\Illuminate\Support\Str::limit($itm->title ?? $itm->name, 28)}}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <li class="text-muted fs-12 py-1">
                                                        {{ __('No items registered yet.') }}
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div>
                                            <h4>
                                                <i class="ri-information-fill text-warning me-1.5 fs-15"></i>
                                                {{$destModel->subtitle ?: $destModel->name}}
                                            </h4>
                                            <p>{{$destModel->description ?: __('Explore our exclusive collection and certified fine gold pieces crafted to perfection.')}}</p>
                                            <a href="{{$destModel->webUrl()}}" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 mt-2 fs-12 text-white d-inline-flex align-items-center gap-1">
                                                <span>{{ __('View all') }}</span>
                                                <i class="ri-arrow-left-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Section 3: Gold Price & Actions/Icons -->
            <div class="nav-section-actions d-flex align-items-center gap-1.5 flex-shrink-0">
                <!-- Live Gold Price Badge (Always visible on desktop and mobile!) -->
                @if(!empty($goldPrice) && (int)$goldPrice > 0)
                    <div class="gold-rate-badge d-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill" title="{{__('Live gold price per gram')}}">
                        <i class="ri-line-chart-line text-warning fs-14"></i>
                        <span class="d-none d-md-inline fs-12 text-white-85">{{__("Gold price")}}:</span>
                        <strong class="text-warning fs-12 fw-bold" dir="ltr">{{number_format((int)$goldPrice)}}</strong>
                        <span class="fs-11 text-white-75">{{config('app.currency.symbol')}}</span>
                    </div>
                @endif

                <!-- Search Action -->
                <a data-bs-toggle="modal" data-bs-target="#apl-search" role="button" class="nav-action-btn" title="{{__('Search')}}">
                    <i class="ri-search-line"></i>
                </a>

                <!-- Cart Action -->
                <a href="{{route('client.card')}}" class="nav-action-btn position-relative" title="{{__('Cart')}}">
                    <i class="ri-shopping-bag-2-line"></i>
                    @if(cardCount() > 0)
                        <span class="badge bg-danger rounded-pill cart-badge-count">
                            {{cardCount()}}
                        </span>
                    @endif
                </a>

                <!-- User Profile / Login Action -->
                @if(auth('customer')->check())
                    <a href="{{route('client.profile')}}" class="nav-action-btn" title="{{auth('customer')->user()->name ?: __('Profile')}}">
                        <i class="ri-user-line"></i>
                    </a>
                @else
                    <a href="{{route('client.sign-in')}}" class="nav-action-btn" title="{{__('Sign in')}}">
                        <i class="ri-user-line"></i>
                    </a>
                @endif

                <!-- Mobile Hamburger Menu Button -->
                <a href="#mobileNavDrawer" data-bs-toggle="offcanvas" role="button" class="nav-action-btn d-lg-none" aria-controls="mobileNavDrawer" aria-label="{{__('Menu')}}">
                    <i class="ri-menu-line fs-20"></i>
                </a>
            </div>

        </div>
    </div>
</nav>

<!-- Mobile Navigation Offcanvas Drawer -->
<div class="offcanvas offcanvas-start mobile-nav-offcanvas" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
    <div class="offcanvas-header border-bottom bg-dark text-white p-3">
        <div class="d-flex align-items-center gap-2" id="mobileNavDrawerLabel">
            <img src="{{asset('upload/images/logo.svg')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}" height="28" style="filter: brightness(0) invert(1);">
            <h6 class="fw-bold text-white mb-0 fs-15">{{config('app.name')}}</h6>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center p-0" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}" style="width: 32px; height: 32px;">
            <i class="ri-close-line fs-18"></i>
        </button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column justify-content-between">
        <div class="p-3">
            @if(!empty($goldPrice) && (int)$goldPrice > 0)
                <div class="bg-warning-subtle text-dark border border-warning-subtle rounded-3 p-2.5 mb-3 d-flex align-items-center justify-content-between fs-13">
                    <span class="d-flex align-items-center gap-1.5 fw-semibold">
                        <i class="ri-line-chart-line text-warning"></i>
                        <span>{{__("Gold price")}}:</span>
                    </span>
                    <strong class="text-dark" dir="ltr">{{number_format((int)$goldPrice)}} {{config('app.currency.symbol')}}</strong>
                </div>
            @endif

            <!-- Mobile Menu Links -->
            <ul class="list-unstyled mobile-nav-links d-flex flex-column gap-1 mb-3">
                @foreach($menuItems as $item)
                    <li class="mobile-nav-item">
                        <a href="{{$item->webUrl()}}" class="d-flex align-items-center justify-content-between px-3 py-2.5 rounded-3 text-dark text-decoration-none hover-bg-light fw-semibold fs-14">
                            <span>{{$item->title}}</span>
                            <i class="ri-arrow-left-s-line text-muted fs-16"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Mobile Drawer Footer with Contact & Socials -->
        <div class="p-3 border-top bg-light-subtle">
            @if(!empty($tel))
                <div class="mb-3">
                    <a href="tel:{{$tel}}" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-2" dir="ltr">
                        <i class="ri-phone-line"></i>
                        <span>{{$tel}}</span>
                    </a>
                </div>
            @endif

            @if(!empty($socials))
                <div class="d-flex align-items-center justify-content-center gap-2">
                    @foreach($socials as $k => $social)
                        <a href="{{$social}}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center shadow-xs text-dark hover-primary" style="width: 36px; height: 36px;" aria-label="{{$k}}">
                            <i class="ri-{{$k}}-line fs-16"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="apl-search" tabindex="-1" aria-labelledby="apl-search-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fs-5 fw-bold d-flex align-items-center gap-2" id="apl-search-label">
                    <i class="ri-search-line text-primary"></i>
                    <span>{{__("Search")}}</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light rounded-circle d-flex align-items-center justify-content-center p-0" data-bs-dismiss="modal" aria-label="{{__('Close')}}" style="width: 32px; height: 32px;">
                    <i class="ri-close-line fs-18"></i>
                </button>
            </div>
            <div class="modal-body p-4">
                <form action="{{route('client.search')}}" method="GET" class="side-data">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control form-control-lg rounded-start-pill ps-4" placeholder="{{__('Search products, articles, categories...')}}" required autofocus>
                        <button class="btn btn-primary rounded-end-pill px-4" type="submit">
                            <i class="ri-search-2-line"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
