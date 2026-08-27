<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How customer checkout works') }}</h1>
    <p class="help-article-lead">
        {{ __('The customer locks a gold price, pays card-to-card, then the shop confirms the receipt.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Add a piece to the cart') }}</h2>
            <p>
                {{ __('The customer chooses a specific stock piece. Prices follow the current gold rate.') }}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Sign in before paying') }}</h2>
            <p>
                {{ __('Checkout needs a name, mobile, and address. Guests are asked to sign in or sign up on the cart.') }}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Review the quote and shipping') }}</h2>
            <p>
                {!! __('Quoted prices stay valid for :duration. The customer picks an address and a shipping method, then creates the invoice.', [
                    'duration' => '<strong>'.e(__('Cart quote duration')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Pay to the active bank card') }}</h2>
            <p>
                {!! __('Payment is card-to-card to the one active :accounts account. The invoice stays :status and the piece is reserved.', [
                    'accounts' => '<strong>'.e(__('Bank accounts')).'</strong>',
                    'status' => '<strong>'.e(__('AWAITING_PAYMENT')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">5</span>
        <div>
            <h2>{{ __('Upload the receipt') }}</h2>
            <p>
                {!! __('The customer transfers the money, uploads the receipt, and waits for the shop to confirm. If payment is not completed within :deadline, the invoice is cancelled and the piece returns to stock.', [
                    'deadline' => '<strong>'.e(__('Offline payment deadline')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
</ol>

<p class="help-note">
    <strong>{{ __('Active card:') }}</strong>
    {{ __('If no bank account is active, the customer cannot create an invoice.') }}
</p>
