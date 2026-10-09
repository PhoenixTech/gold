<div class="item-list shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between p-3 p-md-3.5 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-bank-card-line text-primary fs-18"></i>
            <h5 class="mb-0 fw-bold fs-16 text-dark">{{ __('Payment') }}</h5>
        </div>
        @if($item->remainingReceiptBalance() > 0)
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
                    data-bs-toggle="collapse" data-bs-target="#add-payment-panel">
                <i class="ri-add-line"></i>
                <span>{{ __('Record payment') }}</span>
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

        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-md-3">
                <div class="p-2.5 rounded-3 bg-light border">
                    <div class="text-muted fs-11 fw-medium">{{ __('Amount to pay') }}</div>
                    <div class="font-fanum fw-bold text-dark fs-14 mt-0.5">
                        {{ number_format($item->total_price) }} <small class="text-muted fs-11 fw-normal">{{ __('Toman') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-2.5 rounded-3 bg-light border">
                    <div class="text-muted fs-11 fw-medium">{{ __('Received amount') }}</div>
                    <div class="font-fanum fw-bold text-success fs-14 mt-0.5">
                        {{ number_format($item->receivedAmount()) }} <small class="text-muted fs-11 fw-normal">{{ __('Toman') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-2.5 rounded-3 bg-light border">
                    <div class="text-muted fs-11 fw-medium">{{ __('Remaining balance') }}</div>
                    <div class="font-fanum fw-bold {{ $item->remainingReceiptBalance() > 0 ? 'text-danger' : 'text-success' }} fs-14 mt-0.5">
                        {{ number_format($item->remainingReceiptBalance()) }} <small class="text-muted fs-11 fw-normal">{{ __('Toman') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-2.5 rounded-3 bg-light border">
                    <div class="text-muted fs-11 fw-medium">{{ __('Receipts uploaded') }}</div>
                    <div class="font-fanum fw-bold text-dark fs-14 mt-0.5">
                        {{ number_format($item->paymentReceipts->count()) }}
                    </div>
                </div>
            </div>
        </div>

        @if($item->payments->isNotEmpty())
            <h6 class="fw-bold text-dark mb-2"><i class="ri-file-list-3-line me-1"></i> {{ __('Recorded payments') }}</h6>
            <div class="table-responsive border rounded-3 mb-3">
                <table class="table align-middle mb-0 fs-13">
                    <thead class="table-light text-muted fs-12 fw-semibold">
                        <tr>
                            <th style="width: 2.5rem" class="text-center">#</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Related supplier') }}</th>
                            <th>{{ __('Destination account') }}</th>
                            <th>{{ __('Tracking Number') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->payments as $index => $pay)
                            @php
                                $methodEnum = ! empty($pay->meta['method']) ? \App\Enums\ShopPaymentMethod::tryFrom($pay->meta['method']) : null;
                            @endphp
                            <tr>
                                <td class="text-center font-fanum text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ $methodEnum?->label() ?? ($pay->meta['method'] ?? $pay->type) }}
                                    </span>
                                </td>
                                <td class="font-fanum fw-bold text-success">{{ number_format($pay->amount) }} {{ __('Toman') }}</td>
                                <td>
                                    <span class="badge {{ $pay->status === \App\Models\Payment::SUCCESS ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                        {{ __($pay->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($pay->supplier)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <i class="ri-building-line me-0.5"></i>{{ $pay->supplier->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(! empty($pay->meta['bank_account_name']))
                                        <span class="font-fanum fw-semibold text-dark">{{ $pay->meta['bank_account_name'] }}</span>
                                        @if(! empty($pay->meta['card_number']))
                                            <small class="text-muted font-monospace d-block fs-11" dir="ltr">{{ $pay->meta['card_number'] }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="font-monospace text-dark">{{ $pay->reference_id ?: '—' }}</td>
                                <td class="font-fanum fs-12 text-muted">
                                    {{ $pay->meta['payment_date'] ?? $pay->created_at->jdate('Y/m/d') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($item->remainingReceiptBalance() > 0)
            <div class="collapse mb-3" id="add-payment-panel">
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

        <small class="text-muted d-block mt-3 fs-12">
            <i class="ri-information-line text-primary"></i>
            {{ __('If no receipt arrives before the deadline, the order fails automatically and the stock is released.') }}
        </small>
    </div>
</div>
