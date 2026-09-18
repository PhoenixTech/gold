@php
    $bottomNavCustomer = auth('customer')->user();
    $bottomNavActiveCount = $activeOrdersCount ?? $bottomNavCustomer?->invoices()->whereIn('status', [
        \App\Models\Invoice::PENDING,
        \App\Models\Invoice::AWAITING_PAYMENT,
        \App\Models\Invoice::PROCESSING,
        \App\Models\Invoice::OUT_FOR_DELIVERY,
    ])->count() ?? 0;
    $bottomNavFavCount = $favoritesCount ?? $bottomNavCustomer?->favorites()->count() ?? 0;
    $isProfilePage = request()->routeIs('client.profile*') || request()->routeIs('profile*');
    $isInvoicePage = request()->routeIs('client.invoice*') || request()->routeIs('invoice*');
@endphp
<nav class="avisa-bottom-navbar no-print" id="avisa-bottom-navbar" aria-label="{{ __('Bottom Navigation') }}">
    <div class="avisa-bottom-nav-inner">
        <a href="{{ route('client.welcome') }}" class="avisa-bottom-nav-item">
            <i class="ri-home-line"></i>
            <span>{{ __('Home') }}</span>
        </a>
        <a href="{{ route('client.products') }}" class="avisa-bottom-nav-item">
            <i class="ri-grid-line"></i>
            <span>{{ __('Products') }}</span>
        </a>
        <a href="{{ $isProfilePage ? '#invoices' : route('client.profile').'#invoices' }}"
           class="avisa-bottom-nav-item {{ $isProfilePage ? 'avisa-tab-trigger' : '' }} {{ $isInvoicePage ? 'active' : '' }}"
           data-tab-target="#invoices">
            <div class="position-relative">
                <i class="ri-file-list-line"></i>
                @if($bottomNavActiveCount > 0)
                    <span class="avisa-nav-badge">{{ $bottomNavActiveCount }}</span>
                @endif
            </div>
            <span>{{ __('Orders') }}</span>
        </a>
        <a href="{{ $isProfilePage ? '#likes' : route('client.profile').'#likes' }}"
           class="avisa-bottom-nav-item {{ $isProfilePage ? 'avisa-tab-trigger' : '' }}"
           data-tab-target="#likes">
            <div class="position-relative">
                <i class="ri-heart-line"></i>
                @if($bottomNavFavCount > 0)
                    <span class="avisa-nav-badge">{{ $bottomNavFavCount }}</span>
                @endif
            </div>
            <span>{{ __('Favorites') }}</span>
        </a>
        <a href="{{ $isProfilePage ? '#summary' : route('client.profile').'#summary' }}"
           class="avisa-bottom-nav-item {{ $isProfilePage ? 'avisa-tab-trigger' : '' }} {{ ($isProfilePage && !$isInvoicePage) ? 'active' : '' }}"
           data-tab-target="#summary">
            <i class="ri-user-line"></i>
            <span>{{ __('Account') }}</span>
        </a>
    </div>
</nav>
