@php
    $quoteMinutes = app(\App\Services\CartQuoteService::class)->ttlMinutes();
    $paymentHours = \App\Models\Invoice::offlinePaymentHours();
@endphp

<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How customer checkout works') }}</h1>
    <p class="help-article-lead">
        {{ __('The customer goes through these steps in the cart. After the last step, the order appears in Website sales.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Cart') }}</h2>
            <p>{{ __('The customer picks the pieces. Gold prices are locked for :minutes minutes. If time runs out, the customer sees the new prices and confirms again.', ['minutes' => $quoteMinutes]) }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Login') }}</h2>
            <p>{{ __('Only if the customer is not signed in: they enter their mobile and the SMS code.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Delivery method') }}</h2>
            <ul>
                <li><strong>{{ __('Courier delivery') }}</strong>: {{ __('Only for addresses in Tehran.') }}</li>
                <li><strong>{{ __('Store pickup') }}</strong>: {{ __('The customer collects the order from the gallery.') }}</li>
            </ul>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Delivery details') }}</h2>
            <p>{{ __('For courier delivery the customer chooses the address. For a gift, they enter the recipient name, mobile and national ID; the courier then delivers to that person.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">5</span>
        <div>
            <h2>{{ __('Review') }}</h2>
            <p>{{ __('The customer checks the order and can enter a discount code.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">6</span>
        <div>
            <h2>{{ __('Payment') }}</h2>
            <p>{{ __('The customer sees the shop bank card and confirms. The order is created and the pieces are held for them. They have :hours hours to pay card-to-card and upload the receipt with its amount and photo.', ['hours' => $paymentHours]) }}</p>
        </div>
    </li>
</ol>

<h2 class="mt-4">{{ __('Good to know') }}</h2>
<ul>
    <li>{{ __('A piece in a cart is not held. It is held only after step 6.') }}</li>
    <li>{{ __('If no receipt arrives in time, the order fails and the pieces go back on sale.') }}</li>
    <li>{{ __('Payment is card-to-card only.') }}</li>
</ul>

<p class="help-note">
    <strong>{{ __('Next:') }}</strong>
    <a href="{{ route('admin.help', ['topic' => 'order-flow']) }}">{{ __('How an order moves from payment to delivery') }}</a>
</p>
