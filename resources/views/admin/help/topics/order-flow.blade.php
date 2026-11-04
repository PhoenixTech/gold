@php
    // `you` marks the stages where the admin has to act.
    $flow = [
        ['icon' => 'ri-bank-card-line', 'label' => __('Customer pays'), 'you' => false],
        ['icon' => 'ri-file-search-line', 'label' => __('Review the receipt'), 'you' => true],
        ['icon' => 'ri-package-line', 'label' => __('Pack and send'), 'you' => true],
        ['icon' => 'ri-motorbike-line', 'label' => __('Pickup or courier'), 'you' => false],
        ['icon' => 'ri-checkbox-circle-line', 'label' => __('Completed'), 'you' => false],
    ];

    $statuses = [
        ['badge' => 'bg-warning-subtle text-warning border border-warning-subtle', 'name' => __('WAITING_RECEIPT'), 'action' => __('Nothing. Wait for the customer.')],
        ['badge' => 'bg-primary-subtle text-primary border border-primary-subtle', 'name' => __('WAITING_CONFIRMATION'), 'action' => __('Your turn: review the receipt.')],
        ['badge' => 'bg-success-subtle text-success border border-success-subtle', 'name' => __('PAID'), 'action' => __('Your turn: pack and send.')],
        ['badge' => 'bg-info-subtle text-info border border-info-subtle', 'name' => __('PROCESSING'), 'action' => __('Your turn: pack and send.')],
        ['badge' => 'bg-success-subtle text-success border border-success-subtle', 'name' => __('READY_FOR_PICKUP'), 'action' => __('Press "Mark as collected" when the customer takes it.')],
        ['badge' => 'bg-warning-subtle text-warning border border-warning-subtle', 'name' => __('OUT_FOR_DELIVERY'), 'action' => __('Nothing. The courier finishes it with the customer code.')],
        ['badge' => 'bg-success text-white', 'name' => __('COMPLETED'), 'action' => __('Nothing.')],
        ['badge' => 'bg-danger-subtle text-danger border border-danger-subtle', 'name' => __('FAILED'), 'action' => __('Nothing. The stock was released automatically.')],
        ['badge' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'name' => __('CANCELED'), 'action' => __('Nothing. The stock was released and received money went to the customer credit.')],
    ];

    $problems = [
        [__('No receipt before the deadline'), __('The order fails by itself and the stock goes back on sale.')],
        [__('The courier rejects or cannot deliver'), __('The order returns to packing. Send it again.')],
        [__('The customer did not get the code'), __('Press "Resend delivery code" on the invoice.')],
        [__('The order must stop'), __('Press "Cancel order" and write a reason. Any money received goes to the customer credit.')],
    ];
@endphp

<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How an order moves from payment to delivery') }}</h1>
    <p class="help-article-lead">
        {{ __('You only act at the highlighted steps. The invoice page always tells you what to do now.') }}
    </p>
</header>

<ol class="d-flex flex-wrap align-items-center gap-2 list-unstyled mb-4" aria-label="{{ __('Order flow') }}">
    @foreach($flow as $stage)
        <li class="d-flex align-items-center gap-2">
            <span class="badge fs-6 fw-semibold {{ $stage['you'] ? 'bg-primary text-white' : 'bg-light text-secondary border' }}">
                <i class="{{ $stage['icon'] }}"></i> {{ $stage['label'] }}
            </span>
            @unless($loop->last)
                <i class="ri-arrow-left-line text-muted" aria-hidden="true"></i>
            @endunless
        </li>
    @endforeach
</ol>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Review the receipt') }}</h2>
            <p>{{ __('Compare the receipt amount and destination account with your bank, then choose one:') }}</p>
            <ul>
                <li><strong>{{ __('Approve Payment') }}</strong>: {{ __('The order is paid. Any extra money goes to the customer credit.') }}</li>
                <li><strong>{{ __('Request Re-upload') }}</strong>: {{ __('The customer gets 3 more hours for a new receipt. The old one stays under "Rejected receipts".') }}</li>
                <li><strong>{{ __('Decline and cancel invoice') }}</strong>: {{ __('The order is canceled and the stock goes back on sale.') }}</li>
            </ul>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Pack and send') }}</h2>
            <ul>
                <li><strong>{{ __('Store pickup') }}</strong>: {{ __('Press "Mark ready for pickup", then "Mark as collected" when the customer takes it.') }}</li>
                <li><strong>{{ __('Courier delivery') }}</strong>: {{ __('Choose a courier and press "Send for delivery". The customer gets a 4-digit code by SMS and the courier enters it to finish.') }}</li>
            </ul>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Money paid in the shop') }}</h2>
            <p>{{ __('If the customer pays part or all of it in the shop, press "Record payment". If it covers the whole total, the order is paid. If not, the customer uploads a receipt for the rest.') }}</p>
        </div>
    </li>
</ol>

<h2 class="mt-4">{{ __('If something goes wrong') }}</h2>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle">
        <tbody>
            @foreach($problems as [$situation, $result])
                <tr>
                    <th class="fw-semibold text-nowrap">{{ $situation }}</th>
                    <td>{{ $result }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('What each status means') }}</h2>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle">
        <tbody>
            @foreach($statuses as $status)
                <tr>
                    <td class="text-nowrap"><span class="badge {{ $status['badge'] }}">{{ $status['name'] }}</span></td>
                    <td>{{ $status['action'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="help-note">
    <strong>{{ __('Good to know:') }}</strong>
    {{ __('A completed, failed or canceled order cannot be reopened.') }}
</p>
