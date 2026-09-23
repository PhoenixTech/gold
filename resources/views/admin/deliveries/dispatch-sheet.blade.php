<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Daily Courier Dispatch Sheet') }} - {{ \Carbon\Carbon::now()->format('Y-m-d') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
            color: #212529;
            padding: 24px;
        }
        .dispatch-sheet-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .table > tbody > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .dispatch-sheet-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .table-bordered th, .table-bordered td {
                border-color: #333 !important;
            }
        }
    </style>
</head>
<body>
    <div class="dispatch-sheet-container">
        <div class="no-print d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
            <a href="{{ route('admin.invoice.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ri-arrow-right-line me-1"></i> {{ __('Back to Invoices') }}
            </a>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="window.print()">
                    <i class="ri-printer-line me-1"></i> {{ __('Print Sheet') }}
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h4 class="fw-bold mb-1 text-dark">{{ __('Daily Courier Dispatch Sheet') }}</h4>
                <span class="text-muted fs-13">{{ config('app.name', 'گالری طلا') }} — {{ \App\Models\Invoice::formatPersianDateTime(now()) }}</span>
            </div>
            <div class="text-end">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-14">
                    {{ __('Total Active Deliveries') }}: {{ $deliveries->count() }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th style="width: 130px;">{{ __('Order Code') }}</th>
                        <th>{{ __('Recipient & Address') }}</th>
                        <th style="width: 140px;">{{ __('Mobile') }}</th>
                        <th style="width: 140px;">{{ __('Courier') }}</th>
                        <th class="text-center" style="width: 110px;">{{ __('Status') }}</th>
                        <th class="text-center" style="width: 150px;">{{ __('Signature') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $index => $delivery)
                        @php
                            $invoice = $delivery->invoice;
                            $recipientName = $invoice->is_third_party ? $invoice->recipient_name : ($invoice->customer->name ?? '-');
                            $recipientMobile = $invoice->is_third_party ? $invoice->recipient_mobile : ($invoice->customer->mobile ?? '-');
                            $addressText = $invoice->isPickup() ? __('In-person Gallery Pickup') : ($invoice->address?->address ?? '-');
                        @endphp
                        <tr>
                            <td class="text-center font-monospace">{{ $index + 1 }}</td>
                            <td>
                                <strong class="font-monospace text-dark">#{{ $invoice->hash }}</strong>
                            </td>
                            <td>
                                <strong class="d-block text-dark mb-1">{{ $recipientName }}</strong>
                                <small class="text-muted fs-12">{{ $addressText }}</small>
                            </td>
                            <td dir="ltr" class="font-monospace text-end">{{ $recipientMobile }}</td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $delivery->courier->name ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fs-12">
                                    {{ $delivery->status->label() }}
                                </span>
                            </td>
                            <td class="text-center text-muted fs-12" style="height: 60px;">
                                <span class="d-print-none text-muted">{{ __('Signature') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                {{ __('No active courier deliveries found for today.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
