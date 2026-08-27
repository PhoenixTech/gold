<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('Gold, checkout, and bank card options') }}</h1>
    <p class="help-article-lead">
        {{ __('These are the switches that change pricing, payment time, and the card customers pay to.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Gold and silver shop settings') }}</h2>
            <p>
                {!! __('Open :settings. There you set :min, :quote, and :deadline.', [
                    'settings' => '<strong>'.e(__('Settings')).' ← '.e(__('Gold & silver shop')).'</strong>',
                    'min' => '<strong>'.e(__('Minimum percent')).'</strong>',
                    'quote' => '<strong>'.e(__('Cart quote duration')).'</strong>',
                    'deadline' => '<strong>'.e(__('Offline payment deadline')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Market rates update by themselves') }}</h2>
            <p>
                {!! __(':gold, :gold24, :silver, and :dollar are fetched automatically. They appear on the dashboard and are not edited on the settings page.', [
                    'gold' => '<strong>'.e(__('Gold 18K Price')).'</strong>',
                    'gold24' => '<strong>'.e(__('Gold 24K Price')).'</strong>',
                    'silver' => '<strong>'.e(__('Silver price')).'</strong>',
                    'dollar' => '<strong>'.e(__('Dollar Rate')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('One active bank card') }}</h2>
            <p>
                {!! __('In :accounts, add the shop cards. Only one account can be active. That card number is shown at checkout. Activating another card turns the previous one off.', [
                    'accounts' => '<strong>'.e(__('Bank accounts')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Keep one card active before going live') }}</h2>
            <p>
                {{ __('If no bank account is active, the customer cannot create an invoice.') }}
            </p>
        </div>
    </li>
</ol>

<p class="help-note">
    <strong>{{ __('What each option does:') }}</strong>
    {!! __(':min raises the gram rate used in product prices. :quote is how long cart prices stay frozen. :deadline is how many hours the customer has to pay and upload a receipt.', [
        'min' => '<strong>'.e(__('Minimum percent')).'</strong>',
        'quote' => '<strong>'.e(__('Cart quote duration')).'</strong>',
        'deadline' => '<strong>'.e(__('Offline payment deadline')).'</strong>',
    ]) !!}
</p>
