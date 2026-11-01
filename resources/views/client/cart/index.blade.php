@extends('website.inc.website-layout')

@section('title')
    {{__("Shopping cart")}} - {{config('app.name')}}
@endsection

@section('content')
@php
    $bank = \App\Http\Controllers\CardController::activeBankDisplay();
    $customer = auth('customer')->user();
    $isLoggedIn = auth('customer')->check();
    $profileComplete = $isLoggedIn && $customer->isCheckoutReady();
    $canPay = $isLoggedIn && ($customer->isCheckoutReady() || $customer->isCheckoutReady(true));
    $cartData = getCartData();
    $cartQuote = app(\App\Services\CartQuoteService::class);
    $cartItems = cardItems();
    $quote = $cartQuote->ensure();
    $nsCardPayload = [
        'items' => $cartItems,
        'qs' => $cartData['qs'],
        'addresses' => $isLoggedIn ? $customer->addresses()->with('state')->get() : [],
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
        'galleryAddress' => (string) getSetting('address'),
        'bankName' => $bank['bank_name'],
        'bankCardNumber' => $bank['card_number'],
        'bankAccountNumber' => $bank['account_number'],
        'bankSheba' => $bank['iban'],
        'bankAccountName' => $bank['account_holder_name'],
        'quoteExpiresAt' => $quote['expires_at'] ?? 0,
        'quoteRemaining' => $quote['remaining_seconds'] ?? 0,
        'quoteMinutes' => $quote['ttl_minutes'] ?? $cartQuote->ttlMinutes(),
        'offlinePaymentHours' => \App\Models\Invoice::offlinePaymentHours(),
        'cardLink' => route('client.product-card-toggle', '').'/',
        'discountLink' => route('client.card.discount', '').'/',
        'productLink' => route('client.product', '').'/',
        'productsUrl' => route('client.products'),
        'loginUrl' => route('client.sign-in', ['redirect' => route('client.card')]),
        'signupUrl' => route('client.sign-up', ['redirect' => route('client.card')]),
        'profileUrl' => route('client.profile'),
        'signInDoUrl' => route('client.sign-in-do'),
        'signUpNowUrl' => route('client.sign-up-now'),
        'sendSmsUrl' => route('client.send-sms'),
        'checkAuthUrl' => route('client.check-auth'),
        'completeProfileUrl' => route('client.card.complete-profile'),
        'stateLink' => route('v1.state.index'),
        'citiesLink' => route('v1.state.show', ''),
        'states' => \App\Http\Resources\StateCollection::collection(\App\Models\State::all())->resolve(),
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
            'cancel' => __('Cancel'),
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
            'have-discount' => __('Do you have a discount code?'),
            'apply-discount' => __('Apply code'),
            'remove-discount' => __('Remove discount'),
            'delivery-info' => __('Delivery details'),
            'order-notes' => __('Order notes'),
            'optional' => __('Optional'),
            'free' => __('Free'),
            'edit' => __('Edit'),
            'discount' => __('Discount'),
            'order-review' => __('Order review'),
            'enter-discount-code' => __('Enter discount code'),
            'order-notes-hint' => __('Optional notes for packaging or delivery...'),
            'direct-bank-transfer' => __('Direct bank transfer'),
            'bank-transfer-details' => __('Bank transfer details'),
            'continue-shopping' => __('Continue shopping'),
            'delivery-method' => __('Delivery method'),
            'gallery-pickup' => __('Gallery pickup'),
            'delivery-to-address' => __('Deliver to address'),
            'gallery-address' => __('Gallery address'),
            'gallery-pickup-desc' => __('In-person pickup at the gallery showroom'),
            'gallery-pickup-notice' => __('After order preparation, you can collect your purchase at the gallery showroom by presenting your national ID.'),
            'tehran-delivery-notice' => __('Your order will be shipped within 48 business hours.'),
            'province-restriction-notice' => __('Direct shipping to this province is currently unavailable. You can proceed only by selecting gallery pickup or entering an alternate Tehran delivery address.'),
            'third-party-recipient' => __('Send to another recipient'),
            'recipient-name' => __('Recipient full name'),
            'recipient-mobile' => __('Recipient mobile'),
            'recipient-national-id' => __('Recipient national ID'),
            'recipient-name-ph' => __('Full name of recipient'),
            'recipient-privacy-notice' => __('Recipient details are collected purely for delivery security and will not be permanently registered as account data.'),
            'recipient-name-required' => __('Please enter the recipient full name'),
            'national-id-invalid' => __('National ID format is invalid'),
            'buyer-details' => __('Buyer details'),
            'select-tehran-address-error' => __('Direct shipping to non-Tehran provinces is currently unavailable. Please select gallery pickup or a Tehran delivery address.'),
            'delivery-type' => __('Delivery type'),
            'delivery-type-title' => __('Select delivery method'),
            'delivery-details' => __('Delivery details'),
            'gallery-pickup-details' => __('Gallery pickup details'),
            'address-and-transport' => __('Address and shipping method'),
            'invoice-details' => __('Invoice details'),
            'invoice-details-title' => __('Order review and invoice'),
            'payment-details' => __('Payment details'),
            'continue-to-payment' => __('Continue to payment'),
            'selected-products' => __('Selected products'),
            'express-courier-tehran' => __('Express courier delivery in Tehran'),
            'gallery-pickup-direct' => __('Direct pickup from gallery showroom'),
            'cart-short' => __('Cart short'),
            'delivery-type-short' => __('Delivery type short'),
            'delivery-details-short' => __('Delivery details short'),
            'invoice-short' => __('Invoice short'),
            'payment-short' => __('Payment short'),
            'account-short' => __('Account short'),
            'state' => __('State'),
            'city' => __('City'),
            'select-state' => __('Select state'),
            'select-city' => __('Select city'),
            'post-code' => __('Post code'),
            'post-code-ph' => __('Postal code (optional)'),
            'state-required' => __('State is required.'),
            'city-required' => __('City is required.'),
            'address-required' => __('Address is required'),
            'name-required' => __('Please enter your name'),
            'name-ph' => __('Your name'),
            'loading' => __('Loading...'),
            'switch-to-pickup' => __('Switch to gallery pickup'),
            'add-tehran-address' => __('Register a new address in Tehran'),
            'verified' => __('Verified'),
        ],
    ];
@endphp
<section class='NsCard content py-4'>
    <div class="{{gfx()['container']}}">
        @include('components.err')
        @if(cardCount() == 0)
            @php
                $suggestedProducts = \App\Models\Product::where('status', 1)
                    ->latest()
                    ->take(4)
                    ->get();
            @endphp
            <div class="empty-cart-wrapper max-w-xl mx-auto text-center py-4">
                <div class="empty-cart-card card border-0 shadow-xs rounded-4 p-4 p-md-5 bg-white mb-5">
                    <div class="empty-cart-illustration mb-3">
                        <div class="empty-cart-icon-bg mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; background: var(--xshop-surface-100); color: var(--xshop-primary);">
                            <i class="ri-shopping-bag-3-line fs-28"></i>
                        </div>
                    </div>

                    <h2 class="empty-cart-title fw-bold text-main fs-18 mb-2">
                        {{ __("Your shopping cart is empty!") }}
                    </h2>

                    <p class="empty-cart-desc text-muted fs-14 mx-auto mb-4 max-w-md">
                        {{ __("You have not added any products to your cart yet. Explore our store to discover the latest items.") }}
                    </p>

                    <div class="d-grid d-sm-flex justify-content-sm-center gap-2">
                        <a href="{{ route('client.products') }}" class="btn btn-primary rounded-pill px-4 py-2.5 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold shadow-xs">
                            <i class="ri-shopping-basket-2-line"></i>
                            <span>{{ __("Explore Products") }}</span>
                        </a>
                        <a href="{{ route('client.welcome') }}" class="btn btn-light rounded-pill px-4 py-2.5 d-inline-flex align-items-center justify-content-center gap-2 text-muted">
                            <span>{{ __("Home Page") }}</span>
                        </a>
                    </div>

                    @if(!$isLoggedIn)
                        <div class="mt-4 pt-3 border-top border-light-subtle d-inline-flex align-items-center gap-1.5 text-muted fs-13">
                            <span>{{ __("Already have an account?") }}</span>
                            <a href="{{ route('client.sign-in', ['redirect' => route('client.card')]) }}" class="text-primary fw-semibold text-decoration-none hover-underline">
                                {{ __("Sign in to your account") }}
                            </a>
                        </div>
                    @endif
                </div>

                @if($suggestedProducts->isNotEmpty())
                    <div class="empty-cart-products text-start">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-light-subtle">
                            <h3 class="fw-bold fs-16 text-main mb-0 d-flex align-items-center gap-2">
                                <i class="ri-fire-fill text-primary"></i>
                                <span>{{ __("Suggested Products") }}</span>
                            </h3>
                            <a href="{{ route('client.products') }}" class="text-muted fs-13 text-decoration-none hover-primary">
                                {{ __("View all") }}
                            </a>
                        </div>
                        <div class="row g-3">
                            @foreach($suggestedProducts as $prod)
                                <div class="col-6 col-md-3">
                                    @include('client.partials.product-card', ['product' => $prod])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <form method="post" class="safe-form">
                <input type="hidden" class="safe-url" data-url="{{route('client.card.check')}}">
                @csrf
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

@endsection
