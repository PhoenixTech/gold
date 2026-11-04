<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How motorcycle delivery works') }}</h1>
    <p class="help-article-lead">
        {{ __('The courier hands over the gold only after the customer gives the 4-digit SMS code.') }}
    </p>
</header>

<h2>{{ __('Set up once') }}</h2>
<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Courier transport') }}</h2>
            <p>{{ __('In "Transports", open the courier method and tick "Needs delivery confirmation code".') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Courier user') }}</h2>
            <p>{{ __('In "Staff", create a user with the Courier role. Couriers only see their deliveries.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('SMS template') }}</h2>
            <p>{{ __('In Settings → "SMS messages", fill "Delivery confirmation". This SMS carries the code.') }}</p>
        </div>
    </li>
</ol>

<h2 class="mt-4">{{ __('For each order') }}</h2>
<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('You send it') }}</h2>
            <p>{{ __('On a paid invoice, choose a courier and press "Send for delivery". The code is sent to the customer, and for a gift also to the recipient.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('The courier accepts') }}</h2>
            <p>{{ __('The courier sees the address, items, phone and note, then accepts or rejects the job. For a gift they see the recipient and check their national ID.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('The courier delivers') }}</h2>
            <p>{{ __('At the door the courier types the code the customer reads out. If it is right, the order is completed.') }}</p>
        </div>
    </li>
</ol>

<h2 class="mt-4">{{ __('If something goes wrong') }}</h2>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle">
        <tbody>
            <tr>
                <th class="fw-semibold">{{ __('The courier rejects or cannot deliver') }}</th>
                <td>{{ __('The order returns to packing and the invoice shows the courier reason. Send it again.') }}</td>
            </tr>
            <tr>
                <th class="fw-semibold">{{ __('The customer did not get the code') }}</th>
                <td>{{ __('Press "Resend delivery code" on the invoice.') }}</td>
            </tr>
            <tr>
                <th class="fw-semibold">{{ __('Five wrong codes') }}</th>
                <td>{{ __('The job locks. Press "Resend delivery code" to unlock it with a new code.') }}</td>
            </tr>
            <tr>
                <th class="fw-semibold">{{ __('Another courier should take it') }}</th>
                <td>{{ __('Choose the new courier and press "Reassign". The old code stops working.') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="help-note">
    <strong>{{ __('Good to know:') }}</strong>
    {{ __('The code is never shown to the courier or in the admin panel.') }}
</p>
