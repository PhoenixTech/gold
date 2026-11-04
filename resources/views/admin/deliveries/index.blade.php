@extends('layouts.app')

@section('title')
    {{ __('Deliveries') }} -
@endsection

@section('content')
    @php
        $formatAddress = function ($invoice) {
            $address = $invoice->address;
            $parts = array_filter([
                $address?->state?->name,
                $address?->city?->name,
                $address?->address,
                $address?->zip ? __('Postal code').': '.$address->zip : null,
            ], fn ($part) => $part !== null && trim((string) $part) !== '');

            return $parts ? implode('، ', $parts) : ($invoice->address_alt ?: __('No address registered.'));
        };
    @endphp

    <div class="row">
        <div class="col-12 col-xl-10 col-xxl-9 mx-auto">
            <div class="item-list mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3">
                    <div>
                        <h1 class="h4 mb-1"><i class="ri-motorbike-line me-1"></i>{{ __('Deliveries') }}</h1>
                        <p class="text-muted mb-0 fs-13">
                            {{ __('Ask the customer for the SMS code before handing over the gold. The code is not shown here.') }}
                        </p>
                    </div>
                    @if($deliveries->isNotEmpty())
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            {{ number_format($deliveries->count()) }} {{ __('Open deliveries') }}
                        </span>
                    @endif
                </div>
            </div>

            @include('components.err')

            @if($deliveries->isEmpty())
                <div class="alert alert-info border border-info-subtle shadow-sm d-flex align-items-center gap-2 mb-3 rounded-3">
                    <i class="ri-information-line fs-4"></i>
                    <span>{{ __('No deliveries waiting for you right now.') }}</span>
                </div>
            @endif

            @foreach($deliveries as $delivery)
                @php
                    $invoice = $delivery->invoice;
                    $address = $invoice->address;
                    $fullAddress = $formatAddress($invoice);
                    $hasLocation = $address?->lat && $address?->lng;
                    $isGift = (bool) $invoice->is_third_party;
                    $contactName = $isGift ? $invoice->recipient_name : $invoice->customer?->name;
                    $contactMobile = $isGift ? $invoice->recipient_mobile : $invoice->customer?->mobile;
                    $isThisCard = (int) old('delivery_id') === $delivery->id;
                @endphp
                <div class="item-list mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 p-3 border-bottom">
                        <div>
                            <h2 class="h5 mb-1">{{ __('Invoice') }} #{{ $invoice->hash }}</h2>
                            <div class="text-muted fs-13">{{ $invoice->transport?->title }}</div>
                        </div>
                        <span class="{{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                    </div>

                    <div class="p-3">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="text-muted fs-13">{{ $isGift ? __('Recipient (gift order)') : __('Customer') }}</div>
                                <div class="fw-semibold">{{ $contactName ?: __('Guest') }}</div>
                                @if($isGift)
                                    <small class="text-muted">{{ __('Buyer') }}: {{ $invoice->customer?->name }}</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-13">{{ __('Mobile') }}</div>
                                @if($contactMobile)
                                    <a href="tel:{{ $contactMobile }}" dir="ltr" class="fw-semibold">{{ $contactMobile }}</a>
                                @else
                                    <span class="fw-semibold">---</span>
                                @endif
                            </div>
                            @if($isGift && $invoice->recipient_national_id)
                                <div class="col-12">
                                    <div class="alert alert-warning border border-warning-subtle d-flex align-items-center gap-2 p-2 mb-0 rounded-3 fs-13">
                                        <i class="ri-profile-line fs-5"></i>
                                        <span>{{ __('Check the national ID card') }}: <strong class="font-monospace" dir="ltr">{{ $invoice->recipient_national_id }}</strong></span>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="text-muted fs-13">{{ __('Address') }}</div>
                                <div class="fw-semibold">{{ $fullAddress }}</div>
                            </div>
                            @if(trim((string) $invoice->desc) !== '')
                                <div class="col-12">
                                    <div class="text-muted fs-13">{{ __('Customer note') }}</div>
                                    <div>{{ $invoice->desc }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th class="text-end">{{ __('Count') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->orders as $order)
                                        <tr>
                                            <td>{{ $order->product?->name ?? __('Product removed') }}</td>
                                            <td class="text-end">
                                                × {{ number_format($order->count) }}
                                                @if($order->quantity?->weight)
                                                    — {{ $order->quantity->weight }}g
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">{{ __('No items') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @if($hasLocation)
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="https://maps.google.com/?q={{ $address->lat }},{{ $address->lng }}"
                                   target="_blank" rel="noopener">
                                    <i class="ri-map-pin-line"></i> {{ __('Open map') }}
                                </a>
                            @endif
                            @if($contactMobile)
                                <a class="btn btn-outline-secondary btn-sm" href="tel:{{ $contactMobile }}">
                                    <i class="ri-phone-line"></i> {{ $isGift ? __('Call recipient') : __('Call customer') }}
                                </a>
                            @endif
                            @if($delivery->sms_sent_at)
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle align-self-center">
                                    <i class="ri-message-2-line me-1"></i>{{ __('SMS sent') }} {{ \App\Models\Invoice::formatPersianDateTime($delivery->sms_sent_at) }}
                                </span>
                            @endif
                            @if($delivery->failed_attempts)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle align-self-center">
                                    {{ __('Failed attempts') }}: {{ $delivery->failed_attempts }}
                                </span>
                            @endif
                        </div>

                        @if($delivery->isPending())
                            <div class="border border-primary-subtle rounded-3 p-3 bg-primary-subtle bg-opacity-10 mb-0">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="ri-motorbike-line fs-4 text-primary"></i>
                                    <div>
                                        <strong class="d-block">{{ __('Accept this delivery') }}</strong>
                                        <span class="text-muted fs-13">{{ __('Accept to claim the job. The code entry appears after you accept.') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="post" action="{{ route('admin.delivery.accept', $delivery) }}">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">
                                            <i class="ri-check-line"></i> {{ __('Accept delivery') }}
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.delivery.reject', $delivery) }}" class="flex-grow-1">
                                        @csrf
                                        <div class="input-group">
                                            <input name="reason" class="form-control" required minlength="3" placeholder="{{ __('Why are you rejecting this delivery?') }}">
                                            <button class="btn btn-outline-danger" type="submit">{{ __('Reject') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if($delivery->isAccepted())
                            <div class="border border-success-subtle rounded-3 p-3 bg-success-subtle bg-opacity-10 mb-0">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="ri-lock-password-line fs-4 text-success"></i>
                                    <div>
                                        <strong class="d-block">{{ __('Customer delivery code') }}</strong>
                                        <span class="text-muted fs-13">{{ __('Ask the customer for the 4-digit SMS code and enter it here.') }}</span>
                                    </div>
                                </div>

                                @if($delivery->isLocked())
                                    <div class="alert alert-danger border border-danger-subtle mb-3 d-flex align-items-center gap-2 rounded-3">
                                        <i class="ri-error-warning-line fs-4"></i>
                                        <span>{{ __('Too many incorrect attempts. Ask the admin to send a new code.') }}</span>
                                    </div>
                                @else
                                    <form method="post" action="{{ route('admin.delivery.confirm', $delivery) }}" class="row g-2 align-items-center mb-3">
                                        @csrf
                                        <input type="hidden" name="delivery_id" value="{{ $delivery->id }}">
                                        <div class="col-6 col-sm-4 col-md-3">
                                            <input id="code-{{ $delivery->id }}" name="code"
                                                   class="form-control form-control-lg text-center font-monospace {{ $isThisCard && $errors->has('code') ? 'is-invalid' : '' }}"
                                                   inputmode="numeric" autocomplete="one-time-code" maxlength="4" required
                                                   placeholder="••••" value="{{ $isThisCard ? old('code') : '' }}">
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-success" type="submit">
                                                <i class="ri-check-double-line"></i> {{ __('Confirm handover') }}
                                            </button>
                                        </div>
                                    </form>
                                @endif

                                <form method="post" action="{{ route('admin.delivery.fail', $delivery) }}">
                                    @csrf
                                    <div class="input-group">
                                        <input name="reason" class="form-control" placeholder="{{ __('Customer was not available') }}">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="ri-close-circle-line"></i> {{ __('Could not deliver') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($history->isNotEmpty())
                <div class="item-list mb-3">
                    <h2 class="h5 p-3 border-bottom mb-0">{{ __('Recent deliveries') }}</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $delivery)
                                    <tr>
                                        <td class="font-monospace">{{ $delivery->invoice?->hash }}</td>
                                        <td>{{ $delivery->invoice?->customer?->name }}</td>
                                        <td class="font-fanum text-muted fs-13">{{ \App\Models\Invoice::formatPersianDateTime($delivery->delivered_at ?? $delivery->failed_at ?? $delivery->rejected_at ?? $delivery->updated_at) }}</td>
                                        <td class="text-end">
                                            <span class="{{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
