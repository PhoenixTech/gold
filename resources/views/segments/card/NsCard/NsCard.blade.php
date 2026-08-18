@php
    $bank = \App\Http\Controllers\CardController::activeBankDisplay();
    $customer = auth('customer')->user();
    $isLoggedIn = auth('customer')->check();
    $profileComplete = $isLoggedIn && $customer->isCheckoutReady();
    $canPay = $profileComplete;
    $cartData = getCartData();
    $cartQuote = app(\App\Services\CartQuoteService::class);
    $cartItems = cardItems();
    $quote = $cartQuote->current();
    $nsCardPayload = [
        'items' => $cartItems,
        'qs' => $cartData['qs'],
        'addresses' => $isLoggedIn ? $customer->addresses : [],
        'customer' => $isLoggedIn ? [
            'name' => $customer->name,
            'mobile' => $customer->mobile,
            'email' => $customer->email,
        ] : null,
        'transports' => json_decode(transports()->toJson(), true),
        'defTransport' => defTrannsport(),
        'smsSign' => (bool) config('app.sms.sign'),
        'isLoggedIn' => $isLoggedIn,
        'profileComplete' => $profileComplete,
        'canPay' => $canPay,
        'symbol' => config('app.currency.symbol'),
        'bankName' => $bank['bank_name'],
        'bankCardNumber' => $bank['card_number'],
        'bankAccountNumber' => $bank['account_number'],
        'bankSheba' => $bank['iban'],
        'bankAccountName' => $bank['account_holder_name'],
        'quoteExpiresAt' => $quote['expires_at'] ?? 0,
        'quoteMinutes' => $quote['ttl_minutes'] ?? $cartQuote->ttlMinutes(),
        'offlinePaymentHours' => \App\Models\Invoice::offlinePaymentHours(),
        'cardLink' => route('client.product-card-toggle', '').'/',
        'discountLink' => route('client.card.discount', '').'/',
        'productLink' => route('client.product', '').'/',
        'loginUrl' => route('client.sign-in', ['redirect' => route('client.card')]),
        'signupUrl' => route('client.sign-up', ['redirect' => route('client.card')]),
        'profileUrl' => route('client.profile'),
        'signInDoUrl' => route('client.sign-in-do'),
        'signUpNowUrl' => route('client.sign-up-now'),
        'sendSmsUrl' => route('client.send-sms'),
        'checkAuthUrl' => route('client.check-auth'),
        'completeProfileUrl' => route('client.card.complete-profile'),
        'translate' => [
            'cart' => __('Shopping card'),
            'account' => __('Account'),
            'invoice-summary' => __('Order summary'),
            'transport' => __('Delivery'),
            'payment' => __('Payment'),
            'total-price' => __('Total price'),
            'payable' => __('Payable'),
            'name' => __('Name'),
            'mobile' => __('Mobile'),
            'email' => __('Email'),
            'password' => __('Password'),
            'address' => __('Address'),
            'address-ph' => __('Full delivery address'),
            'login' => __('Sign-in'),
            'signup' => __('Sign-up'),
            'sms-hint' => __('Sign in with your mobile number'),
            'auth-code' => __('Auth code'),
            'send-code' => __('Send code'),
            'verify-code' => __('Verify and continue'),
            'save-continue' => __('Save and continue'),
            'weight' => __('Weight'),
            'code' => __('Code'),
            'piece-missing' => __('Stock piece not selected'),
            'choose-piece' => __('Choose piece'),
            'remove' => __('Remove'),
            'mobile-invalid' => __('Mobile number format is invalid'),
            'sent-to' => __('Sent to'),
            'check-dis' => __('Check discount'),
            'check' => __('Check'),
            'discount-code' => __('Discount code'),
            'extra-desc' => __('Extra description'),
            'your-msg' => __('Your message for this order...'),
            'pay-now' => __('Pay now'),
            'register-order' => __('Register order'),
            'plz' => __('Please, Login or complete information to pay'),
            'continue' => __('Continue'),
            'back' => __('Back'),
            'no-address' => __('No address registered.'),
            'add-address' => __('Add address'),
            'products-total' => __('Products total'),
            'pay-method' => __('Payment method'),
            'card-pay' => __('Card to card'),
            'card-pay-hint' => __('Transfer to shop card and upload the receipt within :hours hours', ['hours' => \App\Models\Invoice::offlinePaymentHours()]),
            'live-price-title' => __('Gold price is live'),
            'live-price-hint' => __('Piece prices are calculated from the current gold rate. You have :minutes minutes to create the invoice.', ['minutes' => $quote['ttl_minutes'] ?? $cartQuote->ttlMinutes()]),
            'quote-remaining' => __('Time remaining to create the invoice'),
            'quote-expired' => __('Prices expired. Refreshing…'),
            'quote-step' => __('Create invoice'),
            'pay-step' => __('Card-to-card and receipt'),
            'minutes-short' => __('minutes'),
            'hours-short' => __('hours'),
            'pieces' => __('pieces'),
            'live-piece-price' => __('Live price'),
            'copy' => __('Copy'),
            'copied' => __('Copied'),
            'copy-failed' => __('Could not copy'),
            'aside-note' => __('After the invoice, you have :hours hours to pay and upload the receipt.', ['hours' => \App\Models\Invoice::offlinePaymentHours()]),
            'bank-name' => __('Bank'),
            'bank-info' => __('Card-to-card details'),
            'account-name' => __('Account name'),
            'card-number' => __('Card number'),
            'sheba' => __('SHEBA'),
            'card-wait-hint' => __('After registering the order, you have :hours hours to transfer the amount and upload the receipt, otherwise the invoice will be failed.', ['hours' => \App\Models\Invoice::offlinePaymentHours()]),
            'complete-profile' => __('Please complete your name, mobile and address'),
            'select-address' => __('Please select an address'),
            'select-transport' => __('Please select a delivery method'),
        ],
    ];
@endphp
<section class='NsCard content live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        @include('components.err')
        @if(cardCount() == 0)
            @php
                $popularCategories = \App\Models\Category::where(function($q) {
                        $q->whereNull('parent_id')->orWhere('parent_id', 0);
                    })
                    ->take(6)
                    ->get();
                $suggestedProducts = \App\Models\Product::where('status', 1)
                    ->latest()
                    ->take(4)
                    ->get();
            @endphp
            <div class="empty-cart-wrapper">
                <!-- Main Empty State Hero Card -->
                <div class="empty-cart-card text-center">
                    <div class="empty-cart-illustration">
                        <div class="empty-cart-icon-bg">
                            <i class="ri-shopping-bag-3-line empty-main-icon"></i>
                            <span class="empty-icon-sparkle"><i class="ri-sparkling-2-fill"></i></span>
                        </div>
                    </div>

                    <h5 class="empty-cart-title fw-bold mt-4 mb-2">
                        {{ __("Your shopping cart is empty!") }}
                    </h5>

                    <p class="empty-cart-desc text-muted mx-auto mb-4">
                        {{ __("You have not added any products to your cart yet. Explore our store to discover the latest items.") }}
                    </p>

                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                        <a href="{{ route('client.products') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 d-inline-flex align-items-center gap-2 empty-cta-btn">
                            <i class="ri-shopping-basket-2-line"></i>
                            <span>{{ __("Explore Products") }}</span>
                            <i class="ri-arrow-left-line"></i>
                        </a>
                        <a href="{{ route('client.welcome') }}" class="btn btn-outline-secondary btn-lg rounded-pill px-4 py-2.5 d-inline-flex align-items-center gap-2">
                            <i class="ri-home-4-line"></i>
                            <span>{{ __("Home Page") }}</span>
                        </a>
                    </div>

                    @if(!$isLoggedIn)
                        <div class="mt-4 pt-3 border-top d-inline-flex align-items-center gap-2 text-muted fs-14">
                            <span>{{ __("Already have an account?") }}</span>
                            <a href="{{ route('client.sign-in', ['redirect' => route('client.card')]) }}" class="text-primary fw-bold text-decoration-none hover-underline">
                                {{ __("Sign in to your account") }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Popular Categories to Explore -->
                @if($popularCategories->isNotEmpty())
                    <div class="empty-cart-categories mt-5">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="ri-grid-fill text-primary"></i>
                                {{ __("Popular Categories") }}
                            </h5>
                            <a href="{{ route('client.products') }}" class="text-muted fs-14 text-decoration-none hover-primary">
                                {{ __("View all") }} <i class="ri-arrow-left-s-line"></i>
                            </a>
                        </div>
                        <div class="row g-3">
                            @foreach($popularCategories as $category)
                                <div class="col-6 col-md-4 col-lg-2">
                                    <a href="{{ $category->webUrl() }}" class="category-explore-card d-flex flex-column align-items-center justify-content-center p-3 text-center text-decoration-none">
                                        <div class="category-img-box mb-2">
                                            <img src="{{ $category->imgUrl() }}" alt="{{ $category->name }}" loading="lazy">
                                        </div>
                                        <span class="category-name fw-medium fs-14 text-dark">{{ $category->name }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Suggested Products -->
                @if($suggestedProducts->isNotEmpty())
                    <div class="empty-cart-products mt-5">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="ri-fire-fill text-danger"></i>
                                {{ __("Suggested Products") }}
                            </h5>
                            <a href="{{ route('client.products') }}" class="text-muted fs-14 text-decoration-none hover-primary">
                                {{ __("View all") }} <i class="ri-arrow-left-s-line"></i>
                            </a>
                        </div>
                        <div class="row g-3">
                            @foreach($suggestedProducts as $prod)
                                <div class="col-6 col-md-4 col-xl-3">
                                    @include('segments.product_grid.ShivaProductGrid.ShivaProductGrid', ['product' => $prod])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Trust Perks Footer -->
                <div class="empty-cart-perks mt-5 p-4 rounded-4 bg-light border">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="perk-item">
                                <i class="ri-truck-line perk-icon text-primary"></i>
                                <h6 class="fw-bold mt-2 mb-1">{{ __("Fast & Secure Delivery") }}</h6>
                                <p class="text-muted fs-12 mb-0">{{ __("Delivery to your doorstep") }}</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="perk-item">
                                <i class="ri-shield-check-line perk-icon text-success"></i>
                                <h6 class="fw-bold mt-2 mb-1">{{ __("Original Product Guarantee") }}</h6>
                                <p class="text-muted fs-12 mb-0">{{ __("100% genuine and verified") }}</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="perk-item">
                                <i class="ri-bank-card-line perk-icon text-warning"></i>
                                <h6 class="fw-bold mt-2 mb-1">{{ __('Card to card') }}</h6>
                                <p class="text-muted fs-12 mb-0">{{ __('Pay and upload your receipt within :hours hours.', ['hours' => \App\Models\Invoice::offlinePaymentHours()]) }}</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="perk-item">
                                <i class="ri-customer-service-2-line perk-icon text-info"></i>
                                <h6 class="fw-bold mt-2 mb-1">{{ __("24/7 Dedicated Support") }}</h6>
                                <p class="text-muted fs-12 mb-0">{{ __("We are here to help you") }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <form method="post" class="safe-form">
                <input type="hidden" class="safe-url" data-url="{{route('client.card.check')}}">
                @csrf
                {{-- base64 avoids HTML minify stripping quotes from JSON attributes --}}
                <ns-card payload-b64="{{ base64_encode(json_encode($nsCardPayload, JSON_UNESCAPED_UNICODE)) }}">
                    @if($isLoggedIn)
                        <p class="text-light small mb-2 mt-2">
                            {{__("Welcome back")}}@if($customer->name)<span>: <strong>{{ $customer->name }}</strong></span>@endif
                        </p>
                    @endif
                </ns-card>
            </form>
        @endif
    </div>
</section>
