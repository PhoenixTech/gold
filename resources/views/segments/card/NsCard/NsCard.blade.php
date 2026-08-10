@php
    $bank = \App\Http\Controllers\CardController::ensureBankSettings();
    $customer = auth('customer')->user();
    $isLoggedIn = auth('customer')->check();
    $profileComplete = $isLoggedIn && $customer->isCheckoutReady();
    $canPay = $profileComplete;
    $nsCardPayload = [
        'items' => cardItems(),
        'qs' => json_decode(\Cookie::get('q') ?: '[]', true) ?: [],
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
        'bankCardNumber' => $bank['bank_card_number'],
        'bankSheba' => $bank['bank_sheba'],
        'bankAccountName' => $bank['bank_account_name'],
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
            'online-pay' => __('Online payment'),
            'online-pay-hint' => __('Secure payment via bank gateway'),
            'card-pay' => __('Card to card'),
            'card-pay-hint' => __('Transfer to shop card and wait for confirmation'),
            'bank-info' => __('Card-to-card details'),
            'account-name' => __('Account name'),
            'card-number' => __('Card number'),
            'sheba' => __('SHEBA'),
            'card-wait-hint' => __('After registering the order, transfer the amount and wait for confirmation.'),
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
            <div class="alert alert-info">
                {{__("There is nothing added to card!")}}
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
