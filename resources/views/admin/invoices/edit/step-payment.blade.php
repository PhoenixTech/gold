@php
    $recordedPayments = $item->payments->where('status', \App\Models\Payment::SUCCESS);
    $canRecordPayment = $item->remainingReceiptBalance() > 0;
@endphp
<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between p-3 p-md-3.5 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-bank-card-line text-primary fs-18"></i>
            <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Payment') }}</h5>
        </div>
        @if($canRecordPayment)
            <button type="button" class="btn btn-sm {{ $isShopSale ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }} d-inline-flex align-items-center gap-1"
                    data-bs-toggle="collapse" data-bs-target="#add-payment-panel" aria-expanded="{{ $isShopSale ? 'true' : 'false' }}">
                <i class="ri-add-line"></i>
                <span>{{ $isShopSale ? __('Record payment') : __('Customer paid in the shop') }}</span>
            </button>
        @endif
    </div>

    <div class="p-3 p-md-3.5">
        @if($reuploadReason)
            <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-3 rounded-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-refresh-line text-warning fs-3"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('A clearer receipt was requested from the customer.') }}</strong>
                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $reuploadReason }}</span>
                        @if($item->reuploadRequestedAt())
                            <small class="text-muted fs-12 d-block">
                                <i class="ri-time-line"></i>{{ $item->reuploadRequestedAt()->jdate('Y/m/d H:i') }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($declinedReason)
            <div class="alert alert-danger border border-danger-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-3 rounded-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-close-circle-line text-danger fs-3"></i>
                    <div>
                        <strong class="d-block text-dark">{{ __('A receipt was declined and needs to be uploaded again.') }}</strong>
                        <span class="text-muted fs-13">{{ __('Reason:') }} {{ $declinedReason }}</span>
                    </div>
                </div>
            </div>
        @endif

        @include('admin.invoices.edit.rejected-receipts')

        @include('admin.invoices.edit.rejected-receipts')

        @if($canRecordPayment)
            <div class="collapse mb-3 {{ $isShopSale ? 'show' : '' }}" id="add-payment-panel">
                <div class="card card-body border bg-light p-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="ri-add-circle-line me-1"></i> {{ __('Record new payment') }}</h6>
                    <form method="POST" action="{{ route('admin.invoice.add-payment', $item) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Payment method') }} <span class="text-danger">*</span></label>
                                <select name="method" class="form-select form-select-sm" required>
                                    @foreach(\App\Enums\ShopPaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Amount') }} ({{ __('Toman') }}) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control form-control-sm font-fanum"
                                       value="{{ $item->remainingReceiptBalance() }}" max="{{ $item->remainingReceiptBalance() }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Related supplier') }}</label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">{{ __('— None (Gallery sale) —') }}</option>
                                    @foreach($suppliers ?? [] as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Destination account') }}</label>
                                <select name="bank_account_id" class="form-select form-select-sm">
                                    <option value="">{{ __('— Select bank account —') }}</option>
                                    @foreach($bankAccounts ?? [] as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Tracking Number') }}</label>
                                <input type="text" name="tracking_number" class="form-control form-control-sm font-monospace" placeholder="12345678">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Payment Date') }}</label>
                                <input type="text" name="payment_date" class="form-control form-control-sm font-fanum" value="{{ now()->jdate('Y/m/d', 'en') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">{{ __('Receipt Image / Slip') }}</label>
                                <input type="file" name="slip" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-sm btn-primary px-3">
                                    <i class="ri-check-line me-1"></i> {{ __('Record payment') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($recordedPayments->isNotEmpty())
            <h6 class="fw-bold text-dark mb-2">{{ __('Recorded payments') }}</h6>
            <div class="table-responsive border rounded-3 mb-3">
                <table class="table align-middle mb-0 fs-13">
                    <thead class="table-light text-muted fs-12 fw-semibold">
                        <tr>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Destination account') }}</th>
                            <th>{{ __('Tracking Number') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recordedPayments as $pay)
                            <tr>
                                <td>{{ $pay->methodLabel() }}@if($pay->supplier) <small class="text-muted d-block">{{ $pay->supplier->name }}</small>@endif</td>
                                <td class="font-fanum fw-bold text-success">{{ number_format($pay->amount) }} <small class="text-muted fw-normal">{{ __('Toman') }}</small></td>
                                <td>{{ $pay->meta['bank_account_name'] ?? '—' }}</td>
                                <td class="font-monospace">{{ $pay->reference_id ?: '—' }}</td>
                                <td class="font-fanum fs-12 text-muted">{{ $pay->meta['payment_date'] ?? $pay->created_at->jdate('Y/m/d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(! $isShopSale)
            <p class="text-muted fs-13 mb-0">{{ __('No money has arrived yet.') }}</p>
        @endif

        @if($hasPendingCardPayment)
            <small class="text-muted d-block mt-3 fs-12">
                <i class="ri-information-line"></i>
                {{ __('If no receipt arrives before the deadline, the order fails automatically and the stock is released.') }}
            </small>
        @endif
    </div>
</div>
