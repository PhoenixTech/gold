<nav class="navbar navbar-expand-md shadow-sm" id="panel-top-navbar">
    <div class="container-fluid px-3 px-md-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}" target="_blank" title="{{__('View Website')}}">
            <span class="brand-icon"><i class="ri-store-3-line"></i></span>
            <span class="brand-title">{{ config('app.name', 'ژونلا') }}</span>
            <span class="badge bg-gold-subtle rounded-pill px-2 py-1 ms-1 d-none d-sm-inline-flex align-items-center gap-1">
                <i class="ri-external-link-line"></i>{{__('Site')}}
            </span>
        </a>

        <button class="navbar-toggler border-0 p-2 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <i class="ri-menu-3-line fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto align-items-center gap-2 gap-md-3">
                <li class="nav-item d-none d-md-block">
                    <div class="gold-nav-prices">
                        <span class="gold-nav-price" title="{{__('Gold 18K Price')}}">
                            <span class="gold-nav-label"><i class="ri-coins-line me-1"></i>18K</span>
                            <span class="gold-nav-val">{{number_format((int)getSetting('gold'))}}</span>
                            <span class="gold-nav-unit">{{config('app.currency.symbol')}}</span>
                        </span>
                        <span class="gold-nav-price" title="{{__('Gold 24K Price')}}">
                            <span class="gold-nav-label"><i class="ri-vip-crown-2-line me-1"></i>24K</span>
                            <span class="gold-nav-val">{{number_format((int)getSetting('gold24'))}}</span>
                            <span class="gold-nav-unit">{{config('app.currency.symbol')}}</span>
                        </span>
                        <span class="gold-nav-price" title="{{__('Dollar Rate')}}">
                            <span class="gold-nav-label"><i class="ri-money-dollar-circle-line me-1"></i>$</span>
                            <span class="gold-nav-val">{{number_format((int)getSetting('dollar'))}}</span>
                            <span class="gold-nav-unit">{{config('app.currency.symbol')}}</span>
                        </span>
                    </div>
                </li>

                <!-- Authentication Links -->
                @guest
                    @if (Route::has('login'))
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary btn-sm px-3" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>
                    @endif

                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary btn-sm px-3 text-white" href="{{ route('register') }}">{{ __('Register') }}</a>
                    </li>
                    @endif
                @else
                <li class="nav-item dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle user-nav-pill" href="#" role="button"
                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        <span class="user-avatar">
                            <i class="ri-user-3-fill"></i>
                        </span>
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        @if(Auth::user()->role)
                            <span class="user-role-badge">{{ Auth::user()->role }}</span>
                        @endif
                        <i class="ri-chevron-down-s-line dropdown-arrow ms-1"></i>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2 py-2" aria-labelledby="navbarDropdown">
                        <div class="dropdown-header d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                            <div class="user-avatar lg">
                                <i class="ri-user-3-fill"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark text-truncate fs-6">{{ Auth::user()->name }}</div>
                                <div class="text-muted text-truncate fs-xs">{{ Auth::user()->email }}</div>
                            </div>
                        </div>

                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3" href="{{ url('/') }}" target="_blank">
                            <i class="ri-global-line text-primary"></i>
                            {{ __('View Website') }}
                        </a>

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-danger" href="{{ route('admin.logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ri-logout-box-r-line"></i>
                            {{ __('Logout') }}
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
