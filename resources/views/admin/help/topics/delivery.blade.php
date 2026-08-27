<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How motorcycle delivery works') }}</h1>
    <p class="help-article-lead">
        {{ __('Gold is handed over only after the customer recites the 4-digit SMS code.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Enable the courier transport') }}</h2>
            <p>
                {!! __('In :transports, turn on :flag for the courier method. Methods whose title includes پیک or motorcycle are already checked.', [
                    'transports' => '<strong>'.e(__('Transports')).'</strong>',
                    'flag' => '<strong>'.e(__('Needs delivery confirmation code')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Create a courier user') }}</h2>
            <p>
                {!! __('From :staff, create a user with the :role role. That user only sees the delivery dashboard.', [
                    'staff' => '<strong>'.e(__('Staff')).'</strong>',
                    'role' => '<strong>'.e(__('Courier')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Hand a paid order to a courier') }}</h2>
            <p>
                {!! __('On a paid invoice, set the status to :status, pick a courier, and save. A 4-digit code is SMS’d to the customer (the :template template in :settings).', [
                    'status' => '<strong>'.e(__('OUT_FOR_DELIVERY')).'</strong>',
                    'template' => '<strong>'.e(__('Delivery confirmation')).'</strong>',
                    'settings' => e(__('Settings')).' ← '.e(__('SMS messages')),
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('The courier completes the delivery') }}</h2>
            <p>
                {!! __('The courier signs in and only sees :deliveries: address, items, customer mobile, map, and call. They can accept or reject. At the door they type the code the customer reads from the SMS. If it is correct, the invoice becomes :completed.', [
                    'deliveries' => '<strong>'.e(__('Deliveries')).'</strong>',
                    'completed' => '<strong>'.e(__('COMPLETED')).'</strong>',
                ]) !!}
            </p>
        </div>
    </li>
</ol>

<p class="help-note">
    <strong>{{ __('Safety lock:') }}</strong>
    {{ __('The code is not shown on the courier dashboard. Five wrong attempts lock the job until an admin sends a new code.') }}
</p>
