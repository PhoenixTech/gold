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

    <style ignore--minify>
        .courier-board { max-width: 820px; margin: 0 auto 3rem; }
        .courier-board__head { margin-bottom: 1.25rem; }
        .courier-board__head h1 { font-size: 1.35rem; font-weight: 700; margin: 0 0 .35rem; }
        .courier-board__head p { color: #5c5c5c; margin: 0; font-size: .9rem; }
        .courier-card {
            background: #fff;
            border: 1px solid #e6d7b0;
            box-shadow: 0 10px 30px rgba(20, 20, 20, .04);
            margin-bottom: 1rem;
        }
        .courier-card__top {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            padding: 1rem 1.15rem .75rem;
            border-bottom: 1px solid #f0e6cc;
        }
        .courier-card__top h2 { font-size: 1.05rem; margin: 0 0 .2rem; font-weight: 700; }
        .courier-card__body { padding: 1rem 1.15rem 1.15rem; }
        .courier-meta { display: grid; gap: .55rem; margin: 0 0 1rem; }
        .courier-meta div { display: flex; justify-content: space-between; gap: 1rem; font-size: .9rem; }
        .courier-meta span { color: #6b6b6b; }
        .courier-items { list-style: none; padding: 0; margin: 0 0 1rem; }
        .courier-items li {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .45rem 0;
            border-bottom: 1px dashed #efe6d2;
            font-size: .9rem;
        }
        .courier-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .courier-pin {
            display: flex;
            gap: .5rem;
            align-items: center;
            margin-bottom: .75rem;
        }
        .courier-pin input {
            max-width: 8rem;
            letter-spacing: .35em;
            text-align: center;
            font-weight: 700;
            font-size: 1.15rem;
        }
        .courier-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: #6b6b6b;
            border: 1px dashed #e6d7b0;
            background: #fff;
        }
    </style>

    <section class="courier-board">
        <div class="courier-board__head">
            <h1>{{ __('Deliveries') }}</h1>
            <p>{{ __('Ask the customer for the SMS code before handing over the gold. The code is not shown here.') }}</p>
        </div>

        @include('components.err')

        @if($deliveries->isEmpty())
            <div class="courier-empty">
                {{ __('No deliveries waiting for you right now.') }}
            </div>
        @endif

        @foreach($deliveries as $delivery)
            @php
                $invoice = $delivery->invoice;
                $address = $invoice->address;
                $fullAddress = $formatAddress($invoice);
                $mapQuery = ($address?->lat && $address?->lng)
                    ? $address->lat.','.$address->lng
                    : $fullAddress;
            @endphp
            <article class="courier-card">
                <div class="courier-card__top">
                    <div>
                        <h2>{{ __('Invoice') }} #{{ $invoice->hash }}</h2>
                        <div class="text-muted fs-xs">{{ $invoice->transport?->title }}</div>
                    </div>
                    <span class="{{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                </div>
                <div class="courier-card__body">
                    <div class="courier-meta">
                        <div>
                            <span>{{ __('Customer') }}</span>
                            <b>{{ $invoice->customer?->name ?? __('Guest') }}</b>
                        </div>
                        <div>
                            <span>{{ __('Mobile') }}</span>
                            @if($invoice->customer?->mobile)
                                <a href="tel:{{ $invoice->customer->mobile }}" dir="ltr">{{ $invoice->customer->mobile }}</a>
                            @else
                                <b>---</b>
                            @endif
                        </div>
                        <div>
                            <span>{{ __('Address') }}</span>
                            <b class="text-end">{{ $fullAddress }}</b>
                        </div>
                    </div>

                    <ul class="courier-items">
                        @forelse($invoice->orders as $order)
                            <li>
                                <span>{{ $order->product?->name ?? __('Product removed') }}</span>
                                <span>
                                    × {{ number_format($order->count) }}
                                    @if($order->quantity?->weight)
                                        — {{ $order->quantity->weight }}g
                                    @endif
                                </span>
                            </li>
                        @empty
                            <li><span>{{ __('No items') }}</span></li>
                        @endforelse
                    </ul>

                    <div class="courier-actions mb-3">
                        <a class="btn btn-outline-secondary btn-sm" href="https://maps.google.com/?q={{ urlencode($mapQuery) }}" target="_blank" rel="noopener">
                            <i class="ri-map-pin-line"></i>
                            {{ __('Open map') }}
                        </a>
                        @if($invoice->customer?->mobile)
                            <a class="btn btn-outline-secondary btn-sm" href="tel:{{ $invoice->customer->mobile }}">
                                <i class="ri-phone-line"></i>
                                {{ __('Call customer') }}
                            </a>
                        @endif
                    </div>

                    @if($delivery->isPending())
                        <div class="courier-actions">
                            <form method="post" action="{{ route('admin.delivery.accept', $delivery) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">{{ __('Accept delivery') }}</button>
                            </form>
                            <form method="post" action="{{ route('admin.delivery.reject', $delivery) }}" class="flex-grow-1">
                                @csrf
                                <div class="input-group">
                                    <input name="reason" class="form-control" required minlength="3" placeholder="{{ __('Why are you rejecting this delivery?') }}">
                                    <button class="btn btn-outline-danger" type="submit">{{ __('Reject') }}</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($delivery->isAccepted())
                        <form method="post" action="{{ route('admin.delivery.confirm', $delivery) }}" class="mb-3">
                            @csrf
                            <label class="form-label fw-semibold" for="code-{{ $delivery->id }}">{{ __('Customer delivery code') }}</label>
                            <div class="courier-pin">
                                <input id="code-{{ $delivery->id }}" name="code" class="form-control @error('code') is-invalid @enderror"
                                       inputmode="numeric" autocomplete="one-time-code" maxlength="4" required
                                       placeholder="••••">
                                <button class="btn btn-success" type="submit">{{ __('Confirm handover') }}</button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('admin.delivery.fail', $delivery) }}">
                            @csrf
                            <div class="input-group">
                                <input name="reason" class="form-control" placeholder="{{ __('Customer was not available') }}">
                                <button class="btn btn-outline-secondary" type="submit">{{ __('Could not deliver') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach

        @if($history->isNotEmpty())
            <h2 class="h5 mt-4 mb-3">{{ __('Recent deliveries') }}</h2>
            @foreach($history as $delivery)
                <article class="courier-card">
                    <div class="courier-card__top">
                        <div>
                            <h2>{{ __('Invoice') }} #{{ $delivery->invoice?->hash }}</h2>
                            <div class="text-muted fs-xs">{{ $delivery->invoice?->customer?->name }}</div>
                        </div>
                        <span class="{{ $delivery->status->badgeClass() }}">{{ $delivery->status->label() }}</span>
                    </div>
                </article>
            @endforeach
        @endif
    </section>
@endsection
