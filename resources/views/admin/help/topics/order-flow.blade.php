@php
    $flow = [
        ['icon' => 'ri-bank-card-line', 'label' => __('Payment')],
        ['icon' => 'ri-file-list-3-line', 'label' => __('Receipt review')],
        ['icon' => 'ri-package-line', 'label' => __('Preparing order')],
        ['icon' => 'ri-motorbike-line', 'label' => __('Pickup or courier')],
        ['icon' => 'ri-checkbox-circle-line', 'label' => __('Completed')],
    ];

    $statuses = [
        ['badge' => 'bg-warning-subtle text-warning border border-warning-subtle', 'name' => __('WAITING_RECEIPT'), 'meaning' => __('The customer has not uploaded a receipt yet.'), 'action' => __('Nothing. Wait for the customer or cancel the order.')],
        ['badge' => 'bg-primary-subtle text-primary border border-primary-subtle', 'name' => __('WAITING_CONFIRMATION'), 'meaning' => __('A receipt is uploaded and waits for review.'), 'action' => __('Confirm the payment, ask for a clearer receipt, or decline.')],
        ['badge' => 'bg-success-subtle text-success border border-success-subtle', 'name' => __('PAID'), 'meaning' => __('The payment is confirmed.'), 'action' => __('Prepare the order, then mark it ready or send it with a courier.')],
        ['badge' => 'bg-info-subtle text-info border border-info-subtle', 'name' => __('PROCESSING'), 'meaning' => __('The order is being packed.'), 'action' => __('Mark it ready for pickup, or choose a courier and send it.')],
        ['badge' => 'bg-success-subtle text-success border border-success-subtle', 'name' => __('READY_FOR_PICKUP'), 'meaning' => __('Pickup order waiting in the store.'), 'action' => __('Press "Mark as collected" when the customer takes it.')],
        ['badge' => 'bg-warning-subtle text-warning border border-warning-subtle', 'name' => __('OUT_FOR_DELIVERY'), 'meaning' => __('A courier has the order and the customer has the 4-digit code.'), 'action' => __('Nothing, unless you need to resend the code or change the courier.')],
        ['badge' => 'bg-success text-white', 'name' => __('COMPLETED'), 'meaning' => __('The customer received the order.'), 'action' => __('Nothing.')],
        ['badge' => 'bg-danger-subtle text-danger border border-danger-subtle', 'name' => __('FAILED'), 'meaning' => __('No receipt arrived before the deadline.'), 'action' => __('Nothing. The stock was released automatically.')],
        ['badge' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'name' => __('CANCELED'), 'meaning' => __('An admin canceled or declined the order.'), 'action' => __('Nothing. The stock was released and paid orders were refunded.')],
    ];
@endphp

<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How an order moves from payment to delivery') }}</h1>
    <p class="help-article-lead">
        {{ __('Every order passes the same four steps. The invoice page always shows the current step and the one thing you need to do.') }}
    </p>
</header>

<ol class="d-flex flex-wrap align-items-center gap-2 list-unstyled mb-4" aria-label="{{ __('Order flow') }}">
    @foreach($flow as $i => $stage)
        <li class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 fw-semibold">
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
            <h2>{{ __('Payment: the customer pays') }}</h2>
            <p>{{ __('The customer checks out and gets bank card details and a countdown. They pay card-to-card and upload a receipt. Customer page: /card starts checkout, /invoice/{hash} shows the status.') }}</p>
            <p>{{ __('You do nothing here. If no receipt arrives before the deadline, the order fails and the stock is released automatically.') }}</p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Receipt review: you check the money') }}</h2>
            <p>{{ __('Open the invoice, look at the receipt, and compare the amount and the destination account. You have three choices:') }}</p>
            <ul>
                <li><strong>{{ __('Confirm payment') }}</strong>: {{ __('Choose the bank account and tick the checks. The order becomes PAID.') }}</li>
                <li><strong>{{ __('Request Receipt Re-upload') }}</strong>: {{ __('The receipt was unclear. The customer gets more time and uploads a new one. The stock stays held.') }}</li>
                <li><strong>{{ __('Decline and Cancel') }}</strong>: {{ __('The payment is wrong. The order is canceled and the stock is released.') }}</li>
            </ul>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Preparing: you pack the order') }}</h2>
            <p>{{ __('Pack the order. Then the next button depends on how the customer wants it:') }}</p>
            <ul>
                <li><strong>{{ __('Store pickup') }}</strong>: {{ __('Press "Mark ready for pickup".') }}</li>
                <li><strong>{{ __('Courier delivery') }}</strong>: {{ __('Choose a courier and press "Send for delivery". A 4-digit code is sent to the customer by SMS.') }}</li>
            </ul>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Handover: the customer gets the order') }}</h2>
            <ul>
                <li><strong>{{ __('Store pickup') }}</strong>: {{ __('When the customer takes the order, press "Mark as collected".') }}</li>
                <li><strong>{{ __('Courier delivery') }}</strong>: {{ __('The courier accepts the job and types the code the customer reads from the SMS. If it is right, the order is completed. You cannot complete it by hand.') }}</li>
            </ul>
            <p>{{ __('If the delivery goes wrong, you can resend the code, change the courier, or return the order to preparation.') }}</p>
        </div>
    </li>
</ol>

<h2 class="mt-4">{{ __('What each status means') }}</h2>
<div class="table-responsive mb-4">
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Meaning') }}</th>
                <th>{{ __('What you do') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statuses as $status)
                <tr>
                    <td><span class="badge {{ $status['badge'] }}">{{ $status['name'] }}</span></td>
                    <td>{{ $status['meaning'] }}</td>
                    <td>{{ $status['action'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('Cancel and refund') }}</h2>
<p>
    {{ __('Use "Cancel order" on the invoice page any time before the order is completed. You must write a reason. The stock is released and the customer gets an SMS.') }}
</p>
<ul>
    <li>{{ __('Before the payment is confirmed: nothing is refunded, because no money was received.') }}</li>
    <li>{{ __('After the payment is confirmed: the full invoice total is added to the customer credit. A second cancel cannot refund again.') }}</li>
    <li>{{ __('If a courier is on the way, the delivery is closed and the code stops working.') }}</li>
</ul>

<p class="help-note">
    <strong>{{ __('Good to know:') }}</strong>
    {{ __('A completed, failed or canceled order cannot be reopened. Customers cannot spend their credit at checkout yet.') }}
</p>
