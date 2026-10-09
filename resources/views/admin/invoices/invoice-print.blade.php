@php
    use App\Models\BankAccount;
    use App\Models\Payment;
    use App\Support\PersianNumberToWords;

    $currency = config('app.currency.symbol') ?: __('Toman');
    $isPickup = $invoice->isPickup();
    $cardPayment = $invoice->cardPayment();
    $isOfflineCard = $invoice->isOfflineCardPayment();
    $shopMethod = $invoice->inStorePaymentMethod();

    $receipts = $invoice->paymentReceipts ?? collect();
    $receiptsTotal = $invoice->receivedAmount();
    $remainingBalance = $invoice->remainingReceiptBalance();
    $invoiceTotal = (int) $invoice->total_price;

    $confirmedPayment = null;
    if ($isOfflineCard && $cardPayment !== null && $cardPayment->status === Payment::SUCCESS) {
        // For an offline invoice the card payment is the authoritative
        // confirmation, even if an earlier gateway attempt also succeeded.
        $confirmedPayment = $cardPayment;
    } else {
        $confirmedPayment = $invoice->payments->firstWhere('status', Payment::SUCCESS)
            ?? $invoice->payments->firstWhere('status', 'COMPLETED');
    }
    $confirmMeta = $confirmedPayment?->meta ?? [];
    $confirmedBankName = $confirmMeta['bank_account_name'] ?? null;
    if ($confirmedBankName === null && ! empty($confirmMeta['bank_account_id'])) {
        $confirmedBankName = BankAccount::find($confirmMeta['bank_account_id'])?->bank_name;
    }
    $confirmedAt = $confirmMeta['confirmed_at'] ?? null;
    $confirmedByName = $confirmMeta['confirmed_by_name'] ?? null;
    $referenceId = $confirmedPayment?->reference_id ?? $cardPayment?->reference_id;

    $address = $invoice->address;
    $addressParts = array_filter([
        $address?->state?->name,
        $address?->city?->name,
        $address?->address,
    ], fn ($part) => $part !== null && trim((string) $part) !== '');
    $fullAddress = $isPickup
        ? ((string) getSetting('address') !== '' ? (string) getSetting('address') : __('Gallery address is not configured.'))
        : ($addressParts ? implode('، ', $addressParts) : ($invoice->address_alt ?: __('No address registered.')));

    // Gift orders are shipped to someone other than the buyer, so the printed
    // legal document has to name the actual receiver.
    $receiverName = $invoice->is_third_party ? ($invoice->recipient_name ?: '—') : ($invoice->customer?->name ?: __('Guest'));
    $receiverMobile = $invoice->is_third_party ? ($invoice->recipient_mobile ?: '—') : ($invoice->customer?->mobile ?: '—');

    $subtotal = $invoice->orders->sum('price_total');
    $logoUrl = getSetting('logo_png') ? asset(getSetting('logo_png')) : (getSetting('logo_svg') ? asset(getSetting('logo_svg')) : asset('upload/images/logo.png'));
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Official Sales Invoice') }} #{{ $invoice->hash }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 8mm 8mm 10mm; }

        body {
            background: #eceef1;
            color: #1a1a1a;
            font-family: 'Yekan Bakh VF', system-ui, -apple-system, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 16px;
        }

        .paper {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 10mm 8mm;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        .fs-xxs { font-size: 7pt; }
        .fs-xs { font-size: 8pt; }
        .fs-sm { font-size: 9pt; }
        .fa-num { font-family: 'Yekan Bakh FaNum VF', 'Yekan Bakh VF', sans-serif; }

        .block {
            border: 1px solid #d8dade;
            border-radius: 6px;
            padding: 6px 8px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .block-title {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
            font-size: 8.5pt;
            padding-bottom: 4px;
            margin-bottom: 5px;
            border-bottom: 1px solid #e6e6ea;
        }

        .kv {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 1px 0;
        }
        .kv > span:first-child { color: #6c6f76; }
        .kv > b, .kv > code { text-align: end; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td {
            border: 1px solid #d8dade;
            padding: 3px 5px;
            font-size: 8pt;
            vertical-align: middle;
        }
        table.items th { background: #f4f5f7; font-weight: 700; }
        table.items tbody tr { break-inside: avoid; }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 2px 0;
        }
        .grand-total {
            margin-top: 4px;
            padding-top: 5px;
            border-top: 1.5px solid #1a1a1a;
            font-size: 12pt;
            font-weight: 700;
        }
        .amount-in-words {
            margin-top: 4px;
            padding: 4px 7px;
            background: #f6f6f8;
            border: 1px dashed #c9c9d1;
            border-radius: 5px;
            font-size: 8.5pt;
        }
        .signature-box {
            min-height: 62px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #d8dade;
            border-radius: 6px;
            padding: 6px 8px;
            background: #fafafb;
        }
        .qr { width: 76px; height: 76px; }

        @media print {
            body { background: #fff; padding: 0; font-size: 9pt; }
            .paper {
                max-width: none;
                padding: 0;
                box-shadow: none;
            }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body>

<div class="no-print mb-3 d-flex align-items-center justify-content-center gap-2">
    <button type="button" class="btn btn-primary px-4 fw-bold" id="print-now">
        {{ __('Print') }}
    </button>
    <a href="{{ route('admin.invoice.show', $invoice->hash) }}" class="btn btn-outline-secondary px-3">
        {{ __('Back to invoice') }}
    </a>
    <span class="text-muted fs-13">{{ __('Use your browser dialog to save as PDF if needed.') }}</span>
</div>

<div class="paper">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between gap-3 pb-2 mb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="max-height: 42px;" onerror="this.style.display='none'">
            <div>
                <h5 class="fw-bold mb-0" style="font-size: 13pt;">{{ config('app.name') }}</h5>
                <div class="text-muted fs-xxs">{{ getSetting('subtitle') ?: __('Official Online Store') }}</div>
                @if(getSetting('tel'))
                    <div class="text-muted fs-xxs" dir="ltr">{{ getSetting('tel') }}</div>
                @endif
            </div>
        </div>

        <div class="text-center">
            <div class="fw-bold" style="font-size: 13pt;">{{ __('Official Sales Invoice') }}</div>
            <div class="fs-xxs text-muted">{{ __('Invoice number') }} #<span class="fa-num" dir="ltr">{{ $invoice->hash }}</span></div>
        </div>

        <div class="d-flex align-items-start gap-2">
            <div class="text-end fs-xxs">
                <div><span class="text-muted">{{ __('Order number') }}:</span> <b class="fa-num">{{ $invoice->id }}</b></div>
                <div><span class="text-muted">{{ __('Issue date') }}:</span> <b class="fa-num">{{ \App\Models\Invoice::formatPersianDateTime($invoice->created_at) }}</b></div>
                <div class="mt-1">
                    <span class="badge {{ $invoice->statusBadgeClass() }} fs-xxs">{{ $invoice->statusLabel() }}</span>
                </div>
            </div>
            @if(isset($qr))
                <img src="{{ $qr->render(route('client.invoice', $invoice->hash)) }}" alt="{{ __('QR Code') }}" class="qr">
            @endif
        </div>
    </div>

    {{-- Parties --}}
    <div class="row g-2 mb-2">
        <div class="col-6">
            <div class="block h-100">
                <div class="block-title">{{ __('Seller information') }}</div>
                <div class="kv"><span>{{ __('Name') }}</span><b>{{ config('app.name') }}</b></div>
                @if(getSetting('tel'))
                    <div class="kv"><span>{{ __('Tel') }}</span><b dir="ltr">{{ getSetting('tel') }}</b></div>
                @endif
                <div class="kv"><span>{{ __('Website') }}</span><b dir="ltr">{{ url('/') }}</b></div>
            </div>
        </div>
        <div class="col-6">
            <div class="block h-100">
                <div class="block-title">{{ __('Buyer information') }}</div>
                <div class="kv"><span>{{ __('Name') }}</span><b>{{ $invoice->customer?->name ?: __('Guest') }}</b></div>
                <div class="kv"><span>{{ __('Mobile') }}</span><b class="fa-num" dir="ltr">{{ $invoice->customer?->mobile ?: '—' }}</b></div>
                <div class="kv">
                    <span>{{ __('Address') }}</span>
                    <b style="max-width: 72%;">{{ $fullAddress }}</b>
                </div>
                @if($address?->zip)
                    <div class="kv"><span>{{ __('Postal code') }}</span><b class="fa-num" dir="ltr">{{ $address->zip }}</b></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Third-party receiver: the printed document has to name whoever
         actually receives the parcel, not just the buyer. --}}
    @if($invoice->is_third_party)
        <div class="block mb-2" style="border-color:#b8d4ff;background:#f5f9ff;">
            <div class="block-title">{{ __('Recipient (gift order)') }}</div>
            <div class="row g-2">
                <div class="col-4">
                    <div class="kv"><span>{{ __('Name') }}</span><b>{{ $receiverName }}</b></div>
                </div>
                <div class="col-4">
                    <div class="kv"><span>{{ __('Mobile') }}</span><b class="fa-num" dir="ltr">{{ $receiverMobile }}</b></div>
                </div>
                <div class="col-4">
                    <div class="kv"><span>{{ __('National ID') }}</span><b class="fa-num" dir="ltr">{{ $invoice->recipient_national_id ?: '—' }}</b></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Fulfillment --}}
    <div class="block mb-2">
        <div class="block-title">{{ __('Fulfillment') }}</div>
        <div class="row g-2">
            <div class="col-6">
                <div class="kv">
                    <span>{{ __('Fulfillment method:') }}</span>
                    <b>
                        @if($isPickup)
                            {{ __('Store pickup') }}
                        @elseif($invoice->requiresDeliveryCode())
                            {{ __('Motorcycle courier') }}
                        @else
                            {{ $invoice->transport?->title ?? __('Standard Transport') }}
                        @endif
                    </b>
                </div>
                @unless($isPickup)
                    <div class="kv">
                        <span>{{ __('Shipping method') }}</span>
                        <b>{{ $invoice->transport?->title ?? __('Standard Transport') }}</b>
                    </div>
                    <div class="kv">
                        <span>{{ __('Tracking code') }}</span>
                        @if($invoice->tracking_code)
                            <code dir="ltr" class="fa-num">{{ $invoice->tracking_code }}</code>
                        @else
                            <b>{{ __('Pending shipment') }}</b>
                        @endif
                    </div>
                    @if($invoice->activeDelivery)
                        <div class="kv">
                            <span>{{ __('Courier') }}</span>
                            <b>{{ $invoice->activeDelivery->courier?->name ?? '—' }}</b>
                        </div>
                        <div class="kv">
                            <span>{{ __('Delivery status') }}</span>
                            <span class="{{ $invoice->activeDelivery->status->badgeClass() }}">{{ $invoice->activeDelivery->status->label() }}</span>
                        </div>
                    @endif
                @endunless
            </div>
            <div class="col-6">
                <div class="kv">
                    <span>{{ $isPickup ? __('Pickup location') : __('Shipping address') }}</span>
                    <b style="max-width: 70%;">{{ $fullAddress }}</b>
                </div>
                @if(! $isPickup)
                    <div class="kv">
                        <span>{{ __('Receiver') }}</span>
                        <b>{{ $receiverName }} — <span class="fa-num" dir="ltr">{{ $receiverMobile }}</span></b>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div class="mb-2">
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 32%;">{{ __('Product') }}</th>
                    <th style="width: 34%;">{{ __('Specifications') }}</th>
                    <th style="width: 8%;">{{ __('Count') }}</th>
                    <th style="width: 11%;">{{ __('Unit price') }}</th>
                    <th style="width: 11%;">{{ __('Total price') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->orders as $k => $order)
                    @php
                        $unitPrice = $order->count > 0 ? (int) ($order->price_total / $order->count) : $order->price_total;
                        $quantity = $order->quantity;
                    @endphp
                    <tr>
                        <td class="text-center text-muted fa-num">{{ $k + 1 }}</td>
                        <td>
                            <b>{{ $order->product?->name ?? __('Product removed') }}</b>
                            @if($order->product?->sku)
                                <div class="text-muted fs-xxs" dir="ltr">SKU: {{ $order->product->sku }}</div>
                            @endif
                        </td>
                        <td>
                            @if($quantity)
                                @if($quantity->weight !== null)
                                    <span class="me-2">{{ __('Weight') }}: <b class="fa-num">{{ number_format((float) $quantity->weight, 3) }} {{ __('g') }}</b></span>
                                @endif
                                @if($quantity->code)
                                    <span class="me-2">{{ __('Code') }}: <code dir="ltr">{{ $quantity->code }}</code></span>
                                @endif
                                @foreach(($quantity->meta ?? []) as $m)
                                    <span class="me-2">{{ $m['label'] ?? '' }}: <b>{!! $m['human_value'] ?? '-' !!}</b></span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center fa-num fw-bold">{{ number_format($order->count) }}</td>
                        <td class="fa-num">{{ number_format($unitPrice) }}</td>
                        <td class="fa-num fw-bold">{{ number_format($order->price_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">{{ __('There is nothing to show!') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Payment evidence + totals --}}
    <div class="row g-2 mb-2">
        <div class="col-7">
            <div class="block h-100">
                <div class="block-title">{{ __('Payment details') }}</div>

                <div class="kv">
                    <span>{{ __('Payment method') }}</span>
                    <b>{{ $shopMethod ? __('In-store payment').': '.$shopMethod->label() : ($isOfflineCard ? __('Card to card') : __('Online Gateway')) }}</b>
                </div>
                <div class="kv">
                    <span>{{ __('Status') }}</span>
                    <b>{{ $remainingBalance === 0 ? __('Paid in full') : __('Partially paid') }}</b>
                </div>
                @if($referenceId)
                    <div class="kv">
                        <span>{{ __('Transaction reference') }}</span>
                        <code dir="ltr" class="fa-num">{{ $referenceId }}</code>
                    </div>
                @endif

                {{-- Offline card-to-card invoices had no proof of transfer on the
                     "official" printout at all. --}}
                @if($isOfflineCard || $shopMethod)
                    @if($isOfflineCard)
                        <div class="kv">
                            <span>{{ __('Receipts uploaded') }}</span>
                            <b class="fa-num">{{ $receipts->count() }}</b>
                        </div>
                    @endif
                    <div class="kv">
                        <span>{{ __('Invoice total') }}</span>
                        <b class="fa-num">{{ number_format($invoiceTotal) }} {{ $currency }}</b>
                    </div>
                    <div class="kv">
                        <span>{{ $shopMethod ? __('Received amount') : __('Received via receipts') }}</span>
                        <b class="fa-num">{{ number_format($receiptsTotal) }} {{ $currency }}</b>
                    </div>
                    <div class="kv">
                        <span>{{ __('Remaining balance') }}</span>
                        <b class="fa-num">{{ number_format($remainingBalance) }} {{ $currency }}</b>
                    </div>
                    @if($confirmedBankName)
                        <div class="kv">
                            <span>{{ __('Destination account') }}</span>
                            <b>{{ $confirmedBankName }}</b>
                        </div>
                    @endif
                    @if($confirmedAt)
                        <div class="kv">
                            <span>{{ __('Confirmed at') }}</span>
                            <b class="fa-num">{{ \App\Models\Invoice::formatPersianDateTime(\Illuminate\Support\Carbon::parse($confirmedAt)) }}</b>
                        </div>
                    @endif
                    @if($confirmedByName)
                        <div class="kv">
                            <span>{{ __('Confirmed by') }}</span>
                            <b>{{ $confirmedByName }}</b>
                        </div>
                    @endif
                    @if($receipts->isNotEmpty())
                        <div class="kv">
                            <span>{{ __('Latest receipt') }}</span>
                            <b class="fa-num">{{ $receipts->sortByDesc('id')->first()?->created_at?->jdate('Y/m/d H:i') }}</b>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-5">
            <div class="block h-100">
                <div class="block-title">{{ __('Totals') }}</div>
                <div class="total-row">
                    <span class="text-muted">{{ __('Subtotal') }}</span>
                    <b class="fa-num">{{ number_format($subtotal) }} {{ $currency }}</b>
                </div>
                <div class="total-row">
                    <span class="text-muted">{{ $isPickup ? __('Pickup cost') : __('Shipping cost') }}</span>
                    <b class="fa-num">{{ number_format($invoice->transport_price) }} {{ $currency }}</b>
                </div>
                @if($invoice->credit_price > 0)
                    {{-- credit_price is a customer credit balance, not a discount. --}}
                    <div class="total-row">
                        <span class="text-muted">{{ __('Customer credit used') }}</span>
                        <b class="fa-num">-{{ number_format($invoice->credit_price) }} {{ $currency }}</b>
                    </div>
                @endif
                @if($invoice->discount_id)
                    <div class="total-row">
                        <span class="text-muted">{{ __('Discount code') }}</span>
                        <b class="fa-num">{{ $invoice->discount_id }}</b>
                    </div>
                @endif
                <div class="total-row grand-total">
                    <span>{{ __('Final payable amount') }}</span>
                    <span class="fa-num">{{ number_format($invoiceTotal) }} {{ $currency }}</span>
                </div>
                <div class="amount-in-words">
                    {{ PersianNumberToWords::toWords($invoiceTotal) }} {{ $currency }}
                </div>
            </div>
        </div>
    </div>

    @if($invoice->desc || getSetting('guarantee'))
        <div class="block mb-2">
            <div class="block-title">{{ __('Notes') }}</div>
            @if($invoice->desc)
                <div class="fs-xs mb-1"><b>{{ __('Description') }}:</b> {{ $invoice->desc }}</div>
            @endif
            @if(getSetting('guarantee'))
                <div class="fs-xs"><b>{{ __('Guarantee and return policy') }}:</b> {{ getSetting('guarantee') }}</div>
            @endif
        </div>
    @endif

    {{-- Signatures --}}
    <div class="row g-2 mt-1">
        <div class="col-6">
            <div class="signature-box">
                <b class="fs-xs">{{ __('Seller Signature & Stamp') }}</b>
                <span class="text-muted fs-xxs">{{ config('app.name') }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="signature-box">
                <b class="fs-xs">{{ __('Buyer Signature') }}</b>
                <span class="text-muted fs-xxs">{{ $invoice->customer?->name ?? __('Buyer') }}</span>
            </div>
        </div>
    </div>

    <p class="no-print text-center text-muted fs-xxs mt-3 mb-0">
        {{ __('This document was generated from the order board on :date.', ['date' => \App\Models\Invoice::formatPersianDateTime(now())]) }}
    </p>
</div>

<script>
    document.getElementById('print-now')?.addEventListener('click', function () { window.print(); });

    @if($autoPrint)
        // Wait for fonts and images (logo, QR, product thumbnails) to finish
        // painting before printing -- a fixed setTimeout produced truncated and
        // faded printouts.
        (function () {
            function ready() {
                Promise.all(Array.prototype.map.call(document.images, function (img) {
                    if (img.complete) return Promise.resolve();
                    if (typeof img.decode === 'function') return img.decode().catch(function () {});
                    return new Promise(function (resolve) {
                        img.addEventListener('load', resolve);
                        img.addEventListener('error', resolve);
                    });
                })).then(function () {
                    if (document.fonts && document.fonts.ready) return document.fonts.ready;
                }).then(function () {
                    window.print();
                });
            }

            if (document.readyState === 'complete') {
                ready();
            } else {
                window.addEventListener('load', ready);
            }
        })();
    @endif
</script>
</body>
</html>