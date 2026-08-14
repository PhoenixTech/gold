<section id='AvisaCustomer' class=' live-setting' data-live="{{$data->area_name.'_'.$data->part}}" data-profile-incomplete="{{ (auth('customer')->user()->name == null || trim(auth('customer')->user()->name) == '' || auth('customer')->user()->email == null || trim(auth('customer')->user()->email) == '' || auth('customer')->user()->addresses()->count() == 0) ? 'true' : 'false' }}">
@php
    $awaitingReceiptInvoices = auth('customer')->user()
        ->invoices()
        ->where('status', \App\Models\Invoice::AWAITING_PAYMENT)
        ->whereHas('payments', function ($query) {
            $query->where('type', 'CARD')->where('status', \App\Models\Payment::PENDING);
        })
        ->withCount('paymentReceipts')
        ->orderByDesc('id')
        ->get();
    $needUploadInvoices = $awaitingReceiptInvoices->where('payment_receipts_count', 0);
    $waitingConfirmInvoices = $awaitingReceiptInvoices->where('payment_receipts_count', '>', 0);
    $activeBankAccount = \App\Models\BankAccount::activeAccount();
    $activeBank = \App\Http\Controllers\CardController::activeBankDisplay();
@endphp
<div class="{{gfx()['container']}}">
        <button class="avisa-menu-btn d-lg-none" id="avisa-menu-btn" type="button" aria-label="Menu">
            <i class="ri-menu-3-line"></i>
            {{__("User menu")}}
        </button>
        <div class="avisa-backdrop d-lg-none" id="avisa-backdrop"></div>
        <div class="row">
            <div class="col-lg-3">
                <div class="avisa-sidebar" id="avisa-sidebar">
                    <div class="avisa-user">
                        <img src="{{auth('customer')->user()->avatar()}}"  alt="[avatar]" class="avisa-avatar" onclick="document.querySelector('#avatar').click();">
                        <div class="avisa-user-meta">
                            <small>
                                {{__("Welcome back")}}
                            </small>
                            <strong>
                                {{auth('customer')->user()->name}}
                            </strong>
                        </div>
                        <button class="avisa-close-btn d-lg-none" id="avisa-close-btn" type="button" aria-label="Close">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                <ul class="tab-control" id="avisa-tabs">
                    <li>
                        <a href="#summary" class="active">
                            <span class="avisa-nav-icon"><i class="ri-home-2-line"></i></span>
                            <span class="avisa-nav-label">{{__("Summary")}}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#invoices">
                            <span class="avisa-nav-icon"><i class="ri-file-list-3-line"></i></span>
                            <span class="avisa-nav-label">{{__("Invoices")}}</span>
                            <span class="avisa-nav-count">{{number_format(auth('customer')->user()->invoices()->count())}}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#card-payment">
                            <span class="avisa-nav-icon"><i class="ri-bank-card-line"></i></span>
                            <span class="avisa-nav-label">{{__("Card payment")}}</span>
                            @if($needUploadInvoices->count() > 0)
                                <span class="avisa-nav-count">{{ number_format($needUploadInvoices->count()) }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="#profile">
                            <span class="avisa-nav-icon"><i class="ri-user-3-line"></i></span>
                            <span class="avisa-nav-label">{{__("Profile")}}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#credit">
                            <span class="avisa-nav-icon"><i class="ri-bank-card-2-line"></i></span>
                            <span class="avisa-nav-label">{{__("Credit")}}</span>
                            <span class="avisa-nav-count">{{number_format(auth('customer')->user()->credit)}}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#tickets">
                            <span class="avisa-nav-icon"><i class="ri-customer-service-fill"></i></span>
                            <span class="avisa-nav-label">{{__("Tickets")}}</span>
                            <span class="avisa-nav-count">{{number_format(auth('customer')->user()->tickets()->count())}}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#submit-ticket">
                            <span class="avisa-nav-icon"><i class="ri-mail-add-line"></i></span>
                            <span class="avisa-nav-label">{{__("Submit new ticket")}}</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{route('client.sign-out')}}">
                            <span class="avisa-nav-icon"><i class="ri-logout-box-line"></i></span>
                            <span class="avisa-nav-label">{{__("Sign-out")}}</span>
                        </a>
                    </li>
                </ul>
                </div>
            </div>
            <div class="col-lg-9" id="tabs-content">

                @include('components.err')
                @if($needUploadInvoices->count() > 0)
                    <div class="alert alert-warning mt-4 avisa-receipt-alert d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-upload-cloud-2-line fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('Payment receipt required') }}</h6>
                                <p class="mb-1">
                                    {{ __('You have :count offline invoice(s) waiting for a payment receipt upload.', ['count' => $needUploadInvoices->count()]) }}
                                </p>
                                <ul class="mb-0 ps-3">
                                    @foreach($needUploadInvoices->take(3) as $pendingInv)
                                        <li>
                                            #{{ $pendingInv->id }} —
                                            {{ number_format($pendingInv->total_price) }} {{ config('app.currency.symbol') }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($needUploadInvoices->take(2) as $pendingInv)
                                <button type="button"
                                        class="btn btn-sm btn-warning text-dark fw-bold"
                                        data-receipt-modal-open
                                        data-upload-url="{{ route('client.invoice.receipts.store', $pendingInv) }}"
                                        data-invoice-label="#{{ $pendingInv->id }} — {{ number_format($pendingInv->total_price) }} {{ config('app.currency.symbol') }}">
                                    <i class="ri-upload-2-line"></i>
                                    {{ __('Upload receipt') }}
                                </button>
                            @endforeach
                            <a href="#card-payment" class="btn btn-sm btn-outline-warning avisa-alert-action">
                                {{ __('How to pay') }}
                            </a>
                            <a href="#invoices" class="btn btn-sm btn-outline-warning avisa-alert-action">
                                {{ __('View invoices') }}
                            </a>
                        </div>
                    </div>
                @elseif($waitingConfirmInvoices->count() > 0)
                    <div class="alert alert-info mt-4 avisa-receipt-alert d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-time-line fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('Waiting for payment confirmation') }}</h6>
                                <p class="mb-0">
                                    {{ __('Your receipt was received. We are reviewing your offline payment.') }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('client.invoice', $waitingConfirmInvoices->first()) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('View invoice') }}
                        </a>
                    </div>
                @endif
                @if(cardCount() > 0)
                    <div class="alert alert-info mt-4">
                        <a href="{{ route('client.card') }}" class="btn btn-primary float-end">
                            {{__("Continue")}}
                        </a>
                        <h5 class="alert-heading">
                            {{__("System notification")}}
                        </h5>
                        {{__("You have some products in your shopping card.")}}
                        <br>
                    </div>
                @endif
                @php
                    $u = auth('customer')->user();
                    $missingFields = [];
                    if ($u->name == null || trim($u->name) == '') {
                        $missingFields[] = __('Name');
                    }
                    if ($u->email == null || trim($u->email) == '') {
                        $missingFields[] = __('Email');
                    }
                    if ($u->addresses()->count() == 0) {
                        $missingFields[] = __('Addresses');
                    }
                    $isProfileIncomplete = count($missingFields) > 0;
                @endphp
                @if($isProfileIncomplete)
                    <div id="avisa-alert-profile" class="alert alert-danger mt-4 d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-error-warning-line fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-0 font-weight-bold">
                                    {{__("System notification")}}
                                </h6>
                                <a href="#profile" class="text-decoration-none text-danger avisa-alert-action">
                                    <span class="fw-bold">{{__("Your profile is incomplete. Required fields:")}}</span>
                                    <span class="badge bg-danger text-white ms-1 fs-6 fw-normal">{{ implode('، ', $missingFields) }}</span>
                                </a>
                            </div>
                        </div>
                        <a href="#profile" class="btn btn-sm btn-danger px-3 py-2 text-white shadow-sm rounded-3 avisa-alert-action">
                            <i class="ri-user-edit-line me-1"></i>
                            {{__("Complete profile")}}
                        </a>
                    </div>
                @endif

                <div class="tab active" id="summary">
                    <!-- Welcome Hero Header -->
                    <div class="avisa-hero-card mb-4">
                        <div class="avisa-hero-body">
                            <div class="avisa-hero-user">
                                <img src="{{auth('customer')->user()->avatar()}}" alt="avatar" class="avisa-hero-avatar" onclick="document.querySelector('#avatar')?.click();">
                                <div>
                                    <h5 class="avisa-hero-title">
                                        {{__("Welcome back")}}, {{auth('customer')->user()->name ?: __('Customer')}}! 👋
                                    </h5>
                                    <p class="avisa-hero-sub text-muted mb-0">
                                        <i class="ri-phone-line me-1"></i> {{auth('customer')->user()->mobile}}
                                        @if(auth('customer')->user()->created_at)
                                            <span class="mx-2">•</span>
                                            <i class="ri-calendar-line me-1"></i> {{__("Member since")}}: {{auth('customer')->user()->created_at->ldate('Y-m-d')}}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="avisa-hero-actions">
                                <a href="#profile" class="btn btn-outline-primary btn-sm avisa-alert-action">
                                    <i class="ri-user-settings-line me-1"></i> {{__("Personal Information")}}
                                </a>
                                <a href="#submit-ticket" class="btn btn-primary btn-sm avisa-alert-action">
                                    <i class="ri-add-line me-1"></i> {{__("Submit new ticket")}}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Stat Summary Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="avisa-summary-stat-card card-invoice" onclick="document.querySelector('.tab-control a[href=\'#invoices\']')?.click();">
                                <div class="stat-icon-wrapper">
                                    <i class="ri-file-list-3-line"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-label">{{__("Invoices")}}</span>
                                    <h5 class="stat-value">{{number_format(auth('customer')->user()->invoices()->count())}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="avisa-summary-stat-card card-credit" onclick="document.querySelector('.tab-control a[href=\'#credit\']')?.click();">
                                <div class="stat-icon-wrapper">
                                    <i class="ri-wallet-3-line"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-label">{{__("Credits")}}</span>
                                    <h5 class="stat-value">{{number_format(auth('customer')->user()->credit)}} <small>{{config('app.currency.symbol')}}</small></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="avisa-summary-stat-card card-ticket" onclick="document.querySelector('.tab-control a[href=\'#tickets\']')?.click();">
                                <div class="stat-icon-wrapper">
                                    <i class="ri-customer-service-2-line"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-label">{{__("Tickets")}}</span>
                                    <h5 class="stat-value">{{number_format(auth('customer')->user()->tickets()->count())}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="avisa-summary-stat-card card-address" onclick="document.querySelector('.tab-control a[href=\'#addresses\']')?.click();">
                                <div class="stat-icon-wrapper">
                                    <i class="ri-map-pin-line"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-label">{{__("Addresses")}}</span>
                                    <h5 class="stat-value">{{number_format(auth('customer')->user()->addresses()->count())}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Widgets Row: Recent Invoices & Recent Tickets -->
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="avisa-summary-widget">
                                <div class="widget-header">
                                    <h4><i class="ri-file-list-3-line me-1"></i> {{__("Recent Invoices")}}</h4>
                                    <a href="#invoices" class="widget-link avisa-alert-action">{{__("View All")}} <i class="ri-arrow-left-s-line"></i></a>
                                </div>
                                <div class="widget-body p-0">
                                    @php
                                        $recentInvoices = auth('customer')->user()->invoices()->with(['payments', 'paymentReceipts'])->orderByDesc('id')->take(4)->get();
                                    @endphp
                                    @if($recentInvoices->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table avisa-summary-table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{__("Total price")}}</th>
                                                        <th>{{__("Status")}}</th>
                                                        <th class="text-end">{{__("Actions")}}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentInvoices as $inv)
                                                        <tr>
                                                            <td>#{{$inv->id}}</td>
                                                            <td><b>{{number_format($inv->total_price)}} {{config('app.currency.symbol')}}</b></td>
                                                            <td><span class="inv-badge inv-{{$inv->status}}">{{__($inv->status)}}</span></td>
                                                            <td class="text-end avisa-row-actions">
                                                                <a href="{{ route('client.invoice',$inv->hash) }}" class="avisa-icon-btn" title="{{__('View')}}">
                                                                    <i class="ri-eye-line"></i>
                                                                </a>
                                                                @if($inv->needsReceiptUpload())
                                                                    <button type="button"
                                                                            class="avisa-upload-receipt-btn"
                                                                            title="{{ __('Upload receipt') }}"
                                                                            data-receipt-modal-open
                                                                            data-upload-url="{{ route('client.invoice.receipts.store', $inv) }}"
                                                                            data-invoice-label="#{{ $inv->id }} — {{ number_format($inv->total_price) }} {{ config('app.currency.symbol') }}">
                                                                        <i class="ri-upload-2-line"></i>
                                                                        {{ __('Upload receipt') }}
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="p-4 text-center text-muted">
                                            <i class="ri-inbox-line fs-2 d-block mb-1"></i>
                                            <small>{{__("No invoices found")}}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="avisa-summary-widget">
                                <div class="widget-header">
                                    <h4><i class="ri-customer-service-2-line me-1"></i> {{__("Recent Tickets")}}</h4>
                                    <a href="#tickets" class="widget-link avisa-alert-action">{{__("View All")}} <i class="ri-arrow-left-s-line"></i></a>
                                </div>
                                <div class="widget-body">
                                    @php
                                        $recentTickets = auth('customer')->user()->main_tickets()->orderByDesc('id')->take(3)->get();
                                    @endphp
                                    @if($recentTickets->count() > 0)
                                        <div class="avisa-ticket-mini-list">
                                            @foreach($recentTickets as $ticket)
                                                <div class="ticket-mini-item">
                                                    <div class="ticket-mini-info">
                                                        <a href="{{ route('client.ticket.show',$ticket->id) }}" class="ticket-mini-title">{{$ticket->title}}</a>
                                                        <span class="ticket-mini-date"><i class="ri-time-line me-1"></i>{{$ticket->created_at->ldate('Y-m-d')}}</span>
                                                    </div>
                                                    <span class="inv-badge inv-{{$ticket->status}}">{{__($ticket->status)}}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-4 text-center text-muted">
                                            <i class="ri-question-answer-line fs-2 d-block mb-1"></i>
                                            <small>{{__("No tickets found")}}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab" id="invoices">
                    <div class="avisa-table-card">
                        <div class="avisa-table-head">
                            <h4>
                                <i class="ri-file-list-3-line"></i>
                                {{__("Invoices")}}
                            </h4>
                        </div>
                        <div class="avisa-table-wrap">
                            <table class="avisa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{__("Total price")}}</th>
                                        <th>{{__("Status")}}</th>
                                        <th class="text-end">{{__("Actions")}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(auth('customer')->user()->invoices()->with(['payments', 'paymentReceipts'])->orderByDesc('id')->get() as $i => $inv)
                                        <tr class="{{ $inv->needsReceiptUpload() && $inv->paymentReceipts->isEmpty() ? 'avisa-invoice-needs-receipt' : '' }}">
                                            <td data-label="#"> {{$i+1}} </td>
                                            <td data-label="{{__('Total price')}}">
                                                <b>{{number_format($inv->total_price)}} {{config('app.currency.symbol')}}</b>
                                            </td>
                                            <td data-label="{{__('Status')}}">
                                                <span class="inv-badge inv-{{$inv->status}}">{{__($inv->status)}}</span>
                                                @if($inv->needsReceiptUpload() && $inv->paymentReceipts->isEmpty())
                                                    <div class="avisa-receipt-hint">
                                                        <i class="ri-error-warning-line"></i>
                                                        {{ __('Receipt required') }}
                                                    </div>
                                                @elseif($inv->needsReceiptUpload())
                                                    <div class="avisa-receipt-hint is-waiting">
                                                        <i class="ri-time-line"></i>
                                                        {{ __('Under review') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td data-label="{{__('Actions')}}" class="avisa-row-actions">
                                                <a href="{{ route('client.invoice',$inv->hash) }}"
                                                   class="avisa-icon-btn" title="{{__('View')}}">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($inv->needsReceiptUpload())
                                                    <button type="button"
                                                            class="avisa-upload-receipt-btn"
                                                            data-receipt-modal-open
                                                            data-upload-url="{{ route('client.invoice.receipts.store', $inv) }}"
                                                            data-invoice-label="#{{ $inv->id }} — {{ number_format($inv->total_price) }} {{ config('app.currency.symbol') }}">
                                                        <i class="ri-upload-2-line"></i>
                                                        {{ __('Upload receipt') }}
                                                    </button>
                                                    <a href="{{ route('client.invoice', $inv->hash) }}#receipt-upload"
                                                       class="avisa-icon-btn"
                                                       title="{{ __('Open invoice upload') }}">
                                                        <i class="ri-external-link-line"></i>
                                                    </a>
                                                @elseif( in_array($inv->status, ['PENDING', 'CANCELED', 'FAILED'] ) && $inv->created_at->timestamp >  (time() - 3600) )
                                                    <a href="{{route('client.pay',$inv->hash)}}"
                                                       class="avisa-pay-btn">
                                                        <i class="ri-secure-payment-line"></i>
                                                        {{__("Pay now")}}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab" id="card-payment">
                    <div class="avisa-panel avisa-card-payment">
                        <div class="avisa-panel-head mb-3">
                            <h4>
                                <i class="ri-bank-card-line"></i>
                                {{ __('Card payment guide') }}
                            </h4>
                        </div>

                        <div class="avisa-card-payment__intro">
                            <i class="ri-information-line"></i>
                            <div>
                                <strong>{{ __('Offline card-to-card payment') }}</strong>
                                <p>{{ __('For offline invoices, transfer the amount to the active shop card below, then upload the payment receipt from your invoice.') }}</p>
                            </div>
                        </div>

                        <ol class="avisa-card-payment__steps">
                            <li>
                                <span>1</span>
                                <div>
                                    <strong>{{ __('Transfer the amount') }}</strong>
                                    <small>{{ __('Use the active bank account details below') }}</small>
                                </div>
                            </li>
                            <li>
                                <span>2</span>
                                <div>
                                    <strong>{{ __('Upload the receipt') }}</strong>
                                    <small>{{ __('Open the invoice and upload a photo or PDF of the transfer') }}</small>
                                </div>
                            </li>
                            <li>
                                <span>3</span>
                                <div>
                                    <strong>{{ __('Wait for confirmation') }}</strong>
                                    <small>{{ __('We will review and confirm your payment') }}</small>
                                </div>
                            </li>
                        </ol>

                        <div class="avisa-card-payment__bank">
                            <div class="avisa-card-payment__bank-head">
                                <i class="ri-bank-line"></i>
                                <strong>{{ __('Active shop card') }}</strong>
                                @if($activeBankAccount)
                                    <span class="avisa-card-payment__live">{{ __('Active') }}</span>
                                @endif
                            </div>

                            @if($activeBankAccount)
                                <dl class="avisa-card-payment__rows">
                                    @if($activeBank['bank_name'] ?? null)
                                        <div>
                                            <dt>{{ __('Bank name') }}</dt>
                                            <dd>{{ $activeBank['bank_name'] }}</dd>
                                        </div>
                                    @endif
                                    @if($activeBank['account_holder_name'] ?? null)
                                        <div>
                                            <dt>{{ __('Account holder name') }}</dt>
                                            <dd>{{ $activeBank['account_holder_name'] }}</dd>
                                        </div>
                                    @endif
                                    @if($activeBank['card_number'] ?? null)
                                        <div>
                                            <dt>{{ __('Card number') }}</dt>
                                            <dd dir="ltr">{{ $activeBank['card_number'] }}</dd>
                                        </div>
                                    @endif
                                    @if($activeBank['account_number'] ?? null)
                                        <div>
                                            <dt>{{ __('Account number') }}</dt>
                                            <dd dir="ltr">{{ $activeBank['account_number'] }}</dd>
                                        </div>
                                    @endif
                                    @if($activeBank['iban'] ?? null)
                                        <div>
                                            <dt>{{ __('IBAN') }}</dt>
                                            <dd dir="ltr">{{ $activeBank['iban'] }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            @else
                                <div class="avisa-card-payment__empty">
                                    <i class="ri-error-warning-line"></i>
                                    <p>{{ __('No active bank account is configured yet. Please contact support.') }}</p>
                                </div>
                            @endif
                        </div>

                        @if($awaitingReceiptInvoices->count() > 0)
                            <div class="avisa-card-payment__pending">
                                <div class="avisa-card-payment__pending-head">
                                    <strong>{{ __('Your offline invoices') }}</strong>
                                    <span>{{ number_format($awaitingReceiptInvoices->count()) }}</span>
                                </div>
                                <ul class="avisa-card-payment__pending-list">
                                    @foreach($awaitingReceiptInvoices as $pendingInv)
                                        <li>
                                            <div>
                                                <b>#{{ $pendingInv->id }}</b>
                                                <span>{{ number_format($pendingInv->total_price) }} {{ config('app.currency.symbol') }}</span>
                                                @if($pendingInv->payment_receipts_count > 0)
                                                    <small class="is-waiting">{{ __('Under review') }}</small>
                                                @else
                                                    <small>{{ __('Receipt required') }}</small>
                                                @endif
                                            </div>
                                            <div class="avisa-card-payment__pending-actions">
                                                @if($pendingInv->payment_receipts_count === 0)
                                                    <button type="button"
                                                            class="avisa-upload-receipt-btn"
                                                            data-receipt-modal-open
                                                            data-upload-url="{{ route('client.invoice.receipts.store', $pendingInv) }}"
                                                            data-invoice-label="#{{ $pendingInv->id }} — {{ number_format($pendingInv->total_price) }} {{ config('app.currency.symbol') }}">
                                                        <i class="ri-upload-2-line"></i>
                                                        {{ __('Upload receipt') }}
                                                    </button>
                                                @endif
                                                <a href="{{ route('client.invoice', $pendingInv) }}#receipt-upload" class="avisa-icon-btn" title="{{ __('View invoice') }}">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="avisa-hint mt-3 mb-0">
                                <i class="ri-checkbox-circle-line"></i>
                                {{ __('You have no offline invoices waiting for payment right now.') }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="tab" id="profile">
                    <div class="avisa-profile-head">
                        <img src="{{auth('customer')->user()->avatar()}}" alt="avatar"
                             class="avisa-profile-avatar"
                             onclick="document.querySelector('#avatar')?.click();">
                        <div class="avisa-profile-info">
                            <h4>{{auth('customer')->user()->name ?: __('Customer')}}</h4>
                            <span>{{auth('customer')->user()->mobile}}</span>
                        </div>
                        <label class="avisa-upload-btn" for="avatar">
                            <i class="ri-image-add-line"></i>
                            {{__("Change avatar")}}
                        </label>
                    </div>
                    <div class="avisa-hint">
                        <i class="ri-information-line"></i>
                        {{__("If you want to change the password, choose both the same. Otherwise, leave the password field blank.")}}
                    </div>
                    <div class="avisa-panel">
                        <div class="avisa-panel-head mb-3">
                            <h4>
                                <i class="ri-user-3-line"></i>
                                {{__("Personal Information")}}
                            </h4>
                        </div>
                        <form action="{{route('client.profile.save')}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mt-3">
                                    <div class="form-group">
                                        <label for="name">
                                            {{__('Name')}}
                                        </label>
                                        <input name="name" type="text"
                                               class="form-control @error('name') is-invalid @enderror"
                                               placeholder="{{__('Name')}}"
                                               value="{{old('name',auth('customer')->user()->name??null)}}"/>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-3">
                                    <div class="form-group">
                                        <label for="email">
                                            {{__('Email')}}
                                        </label>
                                        <input name="email" type="email"
                                               class="form-control @error('email') is-invalid @enderror"
                                               placeholder="{{__('Email')}}"
                                               value="{{old('email',auth('customer')->user()->email??null)}}"/>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-3">
                                    <div class="form-group">
                                        <label for="mobile">
                                            {{__('Mobile')}}
                                        </label>
                                        <input name="mobile" type="text" @if(config('app.sms.sign')) readonly
                                               @endif class="form-control @error('mobile') is-invalid @enderror"
                                               placeholder="{{__('Mobile')}}"
                                               value="{{old('mobile',auth('customer')->user()->mobile??null)}}"
                                               min-length="10"/>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <div class="form-group">
                                        <label for="password">
                                            {{__('Password')}}
                                        </label>
                                        <input name="password" type="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="{{__('Password')}}" value="{{old('password',''??null)}}"/>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <div class="form-group">
                                        <label for="password_confirmation">
                                            {{__('password repeat')}}
                                        </label>
                                        <input name="password_confirmation" type="password"
                                               class="form-control @error('password_confirmation') is-invalid @enderror"
                                               placeholder="{{__('password repeat')}}"
                                               value="{{old('password_confirmation',$item->password_confirmation??null)}}"/>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <input type="file" name="avatar" class="d-none" id="avatar" accept="image/jpeg">
                                    <button type="submit" class="avisa-pay-btn w-100 justify-content-center">
                                        <i class="ri-save-3-line"></i>
                                        {{__('Save')}}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="avisa-panel mt-4">
                        <div class="avisa-panel-head mb-3">
                            <h4>
                                <i class="ri-map-pin-user-line"></i>
                                {{__("Addresses")}}
                            </h4>
                        </div>
                        <address-input
                            list-link="{{route('client.addresses')}}"
                            add-link="{{route('client.address.store')}}"
                            update-link="{{route('client.address.update','')}}"
                            rem-link="{{route('client.address.destroy','')}}"
                            state-link="{{route('v1.state.index')}}"
                            cities-link="{{route('v1.state.show','')}}"
                            :dark-mode="false"
                            :translate='{{vueTranslate([
            'addr-editor' => __('Address editor'),
            'state' => __('State'),
            'city' => __('City'),
            'address' => __('Address'),
            'post-code' => __('Post code'),
            'add-address' => __('Add address'),
            'save' => __('Save'),
            ])}}'
                        ></address-input>
                    </div>
                </div>
                <div class="tab" id="credit">
                    <div class="avisa-grid">
                        <div class="grid-item">
                            <i class="ri-bank-card-2-line"></i>
                            <h5>
                                {{__("Credits")}}
                            </h5>
                            <h2>
                                {{number_format(auth('customer')->user()->credit)}}
                                {{config('app.currency.symbol')}}
                            </h2>

                        </div>
                    </div>
                    <h5 class="my-3">
                        {{__("Credit history")}}
                    </h5>
                    @foreach(auth('customer')->user()->credits as $cr)
                        <div class="avisa-credit-item">
                            <div class="avisa-credit-top">
                                <span class="avisa-credit-date">
                                    <i class="ri-time-line"></i>
                                    {{$cr->created_at->ldate('Y-m-d H:i')}}
                                </span>
                                @if($cr->invoice_id != null)
                                    <a href="{{ route('client.invoice',$cr->invoice()->hash) }}"
                                       class="avisa-icon-btn" title="{{__('View')}}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="avisa-credit-amount">
                                <i class="ri-bank-card-2-line"></i>
                                {{number_format($cr->amount)}} {{config('app.currency.symbol')}}
                            </div>
                            @php
                                $data = json_decode($cr->data);
                            @endphp
                            @if(isset($data->message))
                                <div class="avisa-credit-note">
                                    <i class="ri-chat-3-line"></i>
                                    {{$data->message}}
                                </div>
                            @endif
                        </div>
                    @endforeach
                    {{-- WIP add credit manual--}}

                </div>
                <div class="tab" id="tickets">
                    <div class="avisa-table-card">
                        <div class="avisa-table-head">
                            <h4>
                                <i class="ri-customer-service-2-line"></i>
                                {{__("Tickets")}}
                            </h4>
                        </div>
                        <div class="avisa-table-wrap">
                            <table class="avisa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{__("Title")}}</th>
                                        <th>{{__("Status")}}</th>
                                        <th class="text-end">{{__("Actions")}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(auth('customer')->user()->main_tickets()->orderByDesc('id')->get() as $i =>  $ticket)
                                        <tr>
                                            <td data-label="#"> {{$i+1}} </td>
                                            <td data-label="{{__('Title')}}">{{$ticket->title}}</td>
                                            <td data-label="{{__('Status')}}">
                                                <span class="inv-badge inv-{{$ticket->status}}">{{__($ticket->status)}}</span>
                                            </td>
                                            <td data-label="{{__('Actions')}}" class="avisa-row-actions">
                                                <a href="{{ route('client.ticket.show',$ticket->id) }}"
                                                   class="avisa-pay-btn">
                                                    <i class="ri-eye-line"></i>
                                                    {{__("View")}}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab" id="submit-ticket">
                    <div class="avisa-panel">
                    <form action="{{ route('client.ticket.submit') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="title">
                                {{__("Title")}}
                            </label>
                            <input type="text" id="title" name="title" value="{{old('title')}}"
                                   placeholder="{{__("Title")}}"
                                   class="form-control">
                        </div>
                        <div class="form-group mt-3">
                            <label for="body">
                                {{__("Description Text")}}
                            </label>
                            <textarea rows="7" name="body" class="form-control"
                                      placeholder="{{__("Your message ...")}}">{{old('body')}}</textarea>
                        </div>
                        <div class="mt-3">
                            <button class="avisa-pay-btn w-100 justify-content-center">
                                <i class="ri-send-plane-2-line"></i>
                                {{__("Send ticket")}}
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
                <div class="tab" id="addresses">
                    <div class="avisa-panel">
                        <div class="avisa-panel-head">
                            <h4>
                                <i class="ri-map-pin-user-line"></i>
                                {{__("Addresses")}}
                            </h4>
                        </div>
                        <address-input
                            list-link="{{route('client.addresses')}}"
                            add-link="{{route('client.address.store')}}"
                            update-link="{{route('client.address.update','')}}"
                            rem-link="{{route('client.address.destroy','')}}"
                            state-link="{{route('v1.state.index')}}"
                            cities-link="{{route('v1.state.show','')}}"
                            :dark-mode="false"
                            :translate='{{vueTranslate([
            'addr-editor' => __('Address editor'),
            'state' => __('State'),
            'city' => __('City'),
            'address' => __('Address'),
            'post-code' => __('Post code'),
            'add-address' => __('Add address'),
            'save' => __('Save'),
            ])}}'
                        ></address-input>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="avisa-receipt-modal" tabindex="-1" aria-labelledby="avisa-receipt-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content avisa-receipt-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="avisa-receipt-modal-label">{{ __('Upload payment receipt') }}</h5>
                        <small class="text-muted" data-receipt-modal-title></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-receipt-modal-close aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="avisa-receipt-modal__intro">
                        {{ __('This is an offline payment. After transferring the money, upload a clear receipt photo or PDF.') }}
                    </p>
                    @php
                        $modalInvoice = $needUploadInvoices->first()
                            ?? $awaitingReceiptInvoices->first()
                            ?? auth('customer')->user()->invoices()->latest('id')->first();
                    @endphp
                    @if($modalInvoice)
                        @include('components.payment-receipt-uploader', [
                            'invoice' => $modalInvoice,
                            'inputId' => 'avisa-modal-receipts',
                            'formId' => 'avisa-modal-receipt-form',
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
