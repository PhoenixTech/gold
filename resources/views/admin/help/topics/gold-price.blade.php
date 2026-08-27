<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How gold price is calculated') }}</h1>
    <p class="help-article-lead">
        {{ __('Each piece price is built from today’s metal rate, then wage, profit, tax, weight, and addon.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Start from the daily metal rate') }}</h2>
            <p>
                {!! __('Gold pieces use the :gold rate. Silver pieces use the :silver rate. :gold24 and :dollar are shown on the dashboard for reference and are updated automatically.', [
                    'gold' => '<strong>'.e(__('Gold 18K Price')).'</strong>',
                    'silver' => '<strong>'.e(__('Silver price')).'</strong>',
                    'gold24' => '<strong>'.e(__('Gold 24K Price')).'</strong>',
                    'dollar' => '<strong>'.e(__('Dollar Rate')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Apply the minimum percent') }}</h2>
            <p>
                {!! __('In :settings, :min multiplies the gram rate. 105 means five percent above the market rate.', [
                    'settings' => '<strong>'.e(__('Settings')).' ← '.e(__('Gold & silver shop')).'</strong>',
                    'min' => '<strong>'.e(__('Minimum percent')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Add wage, profit, and tax') }}</h2>
            <p>
                {!! __('On the product, set :wage, :profit, and :tax. Tax is applied to wage and profit, not to the gold itself. :addon is added at the end for stones or extra work.', [
                    'wage' => '<strong>'.e(__('Wage percent')).'</strong>',
                    'profit' => '<strong>'.e(__('Profit (%)')).'</strong>',
                    'tax' => '<strong>'.e(__('Tax (%)')).'</strong>',
                    'addon' => '<strong>'.e(__('Addon price')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Weight and rounding') }}</h2>
            <p>
                {{ __('Each stock piece is priced from its own weight in grams. The result is rounded down to the nearest 1,000, then the addon is added.') }}
            </p>
        </div>
    </li>
</ol>

<p class="help-note">
    <strong>{{ __('Cart quote:') }}</strong>
    {!! __('In the cart, that live price is locked for :duration so the customer can create the invoice at the quoted gold rate.', [
        'duration' => '<strong>'.e(__('Cart quote duration')).'</strong>',
    ]) !!}
</p>
