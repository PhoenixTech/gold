<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Shipping Label') }} - {{ $invoice->hash }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
            color: #212529;
            margin: 0;
            padding: 20px;
        }
        .shipping-label-card {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #212529;
            border-radius: 8px;
            padding: 24px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .shipping-label-card {
                border: 2px solid #000;
                border-radius: 0;
                box-shadow: none;
                max-width: 100%;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center mb-3">
        <button type="button" class="btn btn-primary px-4 fw-bold" onclick="window.print()">
            <i class="ri-printer-line me-1"></i> {{ __('Print') }}
        </button>
        <button type="button" class="btn btn-outline-secondary px-3 ms-2" onclick="window.close()">
            <i class="ri-close-line me-1"></i> {{ __('Close') }}
        </button>
    </div>

    <div class="shipping-label-card">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">{{ config('app.name', 'گالری طلا') }}</h5>
                <small class="text-muted">{{ __('Package Shipping Label') }}</small>
            </div>
            <div class="text-end">
                <span class="badge bg-dark fs-6 font-monospace px-3 py-2">#{{ $invoice->hash }}</span>
            </div>
        </div>

        <div class="mb-3">
            <span class="text-muted fs-13 d-block mb-1">{{ __('Recipient Name') }}:</span>
            <h5 class="fw-bold text-dark mb-0">
                {{ $invoice->is_third_party ? $invoice->recipient_name : ($invoice->customer->name ?? '-') }}
                @if($invoice->is_third_party)
                    <span class="badge bg-info-subtle text-info border border-info-subtle fs-12 ms-1">
                        {{ __('Third Party Recipient') }}
                    </span>
                @endif
            </h5>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <span class="text-muted fs-13 d-block mb-1">{{ __('Recipient Mobile') }}:</span>
                <strong class="font-monospace fs-15 text-dark" dir="ltr">
                    {{ $invoice->is_third_party ? $invoice->recipient_mobile : ($invoice->customer->mobile ?? '-') }}
                </strong>
            </div>
            @if($invoice->is_third_party && $invoice->recipient_national_id)
                <div class="col-6">
                    <span class="text-muted fs-13 d-block mb-1">{{ __('National ID') }}:</span>
                    <strong class="font-monospace fs-15 text-dark" dir="ltr">
                        {{ $invoice->recipient_national_id }}
                    </strong>
                </div>
            @endif
        </div>

        <div class="border-top pt-3 mb-3">
            <span class="text-muted fs-13 d-block mb-1">{{ __('Delivery Address') }}:</span>
            <p class="fs-14 fw-medium text-dark mb-0 line-height-base">
                @if($invoice->delivery_type === 'gallery_pickup')
                    {{ __('In-person Gallery Pickup') }}
                @else
                    {{ $invoice->address?->address ?? ($invoice->address_title ?? '-') }}
                @endif
            </p>
        </div>

        <div class="border-top pt-3 d-flex align-items-center justify-content-between text-muted fs-12">
            <span>{{ __('Shipping Method') }}: <strong>{{ $invoice->transport?->title ?? __('Standard') }}</strong></span>
            <span>{{ __('Date') }}: <strong class="font-monospace">{{ \App\Models\Invoice::formatPersianDateTime($invoice->created_at) }}</strong></span>
        </div>
    </div>
</body>
</html>
