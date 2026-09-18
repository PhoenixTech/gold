@extends('website.inc.website-layout')

@section('hide-header', true)
@section('hide-footer', true)

@section('title')
    {{__("Account")}} - {{config('app.name')}}
@endsection

@section('content')
@php
    $customer = auth('customer')->user();
    $missingFields = [];
    if (empty(trim((string) $customer->name))) {
        $missingFields[] = __('Name');
    }
    if (empty(trim((string) $customer->email))) {
        $missingFields[] = __('Email');
    }
    if ($customer->addresses()->count() === 0) {
        $missingFields[] = __('Addresses');
    }
    $isProfileIncomplete = count($missingFields) > 0;

    $invoicesCount = $customer->invoices()->count();
    $favoritesCount = $customer->favorites()->count();
    $bookmarksCount = $customer->bookmarks()->count();
    $addressesCount = $customer->addresses()->count();
    $ticketsCount = $customer->tickets()->count();

    $allInvoices = $customer->invoices()->with(['payments', 'paymentReceipts', 'orders.product', 'orders.quantity'])->orderByDesc('id')->get();
    $activeInvoices = $allInvoices->filter(function ($inv) {
        return in_array($inv->status, \App\Models\Invoice::activeStatuses(), true);
    });
    $activeOrdersCount = $activeInvoices->count();

    $awaitingReceiptInvoices = $customer
        ->invoices()
        ->where('status', \App\Models\Invoice::AWAITING_PAYMENT)
        ->whereHas('payments', function ($query) {
            $query->where('type', 'CARD')->where('status', \App\Models\Payment::PENDING);
        })
        ->withCount('paymentReceipts')
        ->orderByDesc('id')
        ->get();
    $needUploadInvoices = $awaitingReceiptInvoices
        ->where('payment_receipts_count', 0)
        ->filter(fn ($inv) => ! $inv->isOfflinePaymentExpired());
    $waitingConfirmInvoices = $awaitingReceiptInvoices->where('payment_receipts_count', '>', 0);
    $activeBankAccount = \App\Models\BankAccount::activeAccount();
    $activeBank = \App\Http\Controllers\CardController::activeBankDisplay();

    // Split name for wish-profile-edit
    $nameParts = explode(' ', trim((string) $customer->name), 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    // Birth date parts
    $currentDobYear = $customer->dob ? (int) $customer->dob->jdate('Y') : null;
    $currentDobMonth = $customer->dob ? (int) $customer->dob->jdate('n') : null;
    $currentDobDay = $customer->dob ? (int) $customer->dob->jdate('j') : null;

    $persianMonths = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];
@endphp

<section id="AvisaCustomer" data-profile-incomplete="{{ $isProfileIncomplete ? 'true' : 'false' }}">
    <div class="{{ gfx()['container'] ?? 'container' }}">
        <div class="avisa-container-mobile">

            {{-- Hidden Avatar Form for quick upload --}}
            <form id="avisa-avatar-form" action="{{ route('client.profile.save') }}" method="post" enctype="multipart/form-data" class="d-none">
                @csrf
                <input type="hidden" name="_tab_redirect" value="#summary">
                <input type="file" name="avatar" id="avisa-avatar-input" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('avisa-avatar-form').submit();">
            </form>

            {{-- Notice Banners --}}
            @include('components.err')

            @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-3 d-flex align-items-center gap-2 shadow-xs" role="alert">
                    <i class="ri-checkbox-circle-line fs-5"></i>
                    <div>{{ session('message') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($needUploadInvoices->count() > 0)
                <div class="alert alert-warning avisa-receipt-alert d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-4 mb-3 shadow-xs">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ri-upload-cloud-2-line fs-4 text-warning-emphasis"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">{{ __('Payment receipt required') }}</h6>
                            <p class="mb-1 fs-13">
                                {{ __('You have :count offline invoice(s) waiting for a payment receipt upload.', ['count' => $needUploadInvoices->count()]) }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($needUploadInvoices->take(1) as $pendingInv)
                            <button type="button"
                                    class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3"
                                    data-receipt-modal-open
                                    data-upload-url="{{ route('client.invoice.receipts.store', $pendingInv) }}"
                                    data-invoice-label="#{{ $pendingInv->id }} — {{ number_format($pendingInv->total_price) }} {{ config('app.currency.symbol') }}">
                                <i class="ri-upload-2-line me-1"></i>
                                {{ __('Upload receipt') }}
                            </button>
                        @endforeach
                        <a href="#invoices" class="btn btn-sm btn-outline-warning rounded-pill px-3 avisa-alert-action">
                            {{ __('View invoices') }}
                        </a>
                    </div>
                </div>
            @elseif($waitingConfirmInvoices->count() > 0)
                <div class="alert alert-info avisa-receipt-alert d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-4 mb-3 shadow-xs">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ri-time-line fs-4 text-info-emphasis"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">{{ __('Waiting for payment confirmation') }}</h6>
                            <p class="mb-0 fs-13">
                                {{ __('Your receipt was received. We are reviewing your offline payment.') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('client.invoice', $waitingConfirmInvoices->first()) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        {{ __('View invoice') }}
                    </a>
                </div>
            @endif

            @if(cardCount() > 0)
                <div class="alert alert-warning border border-warning-subtle rounded-4 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-shopping-cart-2-line fs-4 text-warning"></i>
                        <div>
                            <strong class="d-block text-dark fs-14">{{ __('You have some products in your shopping card.') }}</strong>
                        </div>
                    </div>
                    <a href="{{ route('client.card') }}" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3">
                        {{ __('Continue') }}
                    </a>
                </div>
            @endif

            @if($isProfileIncomplete)
                <div id="avisa-alert-profile" class="alert alert-danger border border-danger-subtle rounded-4 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-error-warning-line fs-4 text-danger"></i>
                        <div>
                            <span class="fw-bold fs-14 text-danger">{{ __('Your profile is incomplete. Required fields:') }}</span>
                            <span class="badge bg-danger text-white ms-1 fs-12 fw-normal">{{ implode('، ', $missingFields) }}</span>
                        </div>
                    </div>
                    <a href="#profile-edit" class="btn btn-sm btn-danger rounded-pill px-3 text-white avisa-alert-action">
                        {{ __('Complete profile') }}
                    </a>
                </div>
            @endif

            <div id="tabs-content">

                {{-- ============================================================ --}}
                {{-- TAB 1: MAIN ACCOUNT SUMMARY (wish1.png Reference Layout)      --}}
                {{-- ============================================================ --}}
                <div class="tab active" id="summary">

                    {{-- Page Title (wish1.png) --}}
                    <div class="avisa-page-title mb-2">
                        <h3 class="fw-bold mb-0 text-dark fs-18">{{ __('Account') }}</h3>
                    </div>

                    {{-- 1. Top Header Card (Avatar, Name, Mobile, Edit Info Link) --}}
                    <div class="card avisa-card-ref mb-3">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avisa-ref-avatar-wrapper" onclick="document.getElementById('avisa-avatar-input')?.click();" title="{{ __('Change avatar') }}">
                                    <img src="{{ $customer->avatar() }}" alt="avatar" class="avisa-ref-avatar">
                                    <span class="avisa-avatar-badge"><i class="ri-camera-line"></i></span>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0.5 text-dark fs-14">{{ $customer->name ?: __('Customer') }}</h5>
                                    <span class="text-muted fs-12 font-fanum" dir="ltr">{{ $customer->mobile }}</span>
                                </div>
                            </div>
                            <div>
                                <a href="#profile" class="avisa-ref-edit-link avisa-tab-trigger">
                                    <span>{{ __('Edit info') }}</span>
                                    <i class="ri-arrow-left-s-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Wallet Balance Card (wish1.png: Plain value + My Credit Link) --}}
                    <div class="card avisa-card-ref mb-3">
                        <div class="card-body p-0">
                            <div class="p-3 d-flex align-items-center justify-content-between">
                                <span class="text-dark fw-semibold fs-13">{{ __('Account balance') }}</span>
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="fw-bold fs-14 text-success font-fanum">{{ number_format($customer->credit) }}</span>
                                    <span class="text-muted fs-12">{{ config('app.currency.symbol', 'تومان') }}</span>
                                </div>
                            </div>
                            <hr class="my-0 avisa-card-divider">
                            <a href="#credit" class="p-3 d-flex align-items-center justify-content-between text-decoration-none avisa-card-interactive-row avisa-tab-trigger">
                                <span class="text-dark fs-13 fw-normal">{{ __('My balance and credit') }}</span>
                                <i class="ri-copper-coin-line fs-5 text-muted"></i>
                            </a>
                        </div>
                    </div>

                    {{-- 3. Vertical Menu List (matching wish1.png) --}}
                    <div class="card avisa-card-ref mb-4 overflow-hidden">
                        <div class="list-group list-group-flush avisa-menu-list">
                            {{-- 1. Active orders --}}
                            <a href="#active-orders" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between avisa-tab-trigger">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ri-time-line avisa-menu-icon"></i>
                                    <span class="avisa-menu-text">{{ __('Active orders') }}</span>
                                </div>
                                @if($activeOrdersCount > 0)
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-0.5 fs-12 font-fanum fw-semibold">{{ number_format($activeOrdersCount) }}</span>
                                @endif
                            </a>

                            {{-- 2. Addresses --}}
                            <a href="#addresses" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between avisa-tab-trigger">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ri-map-pin-2-line avisa-menu-icon"></i>
                                    <span class="avisa-menu-text">{{ __('Addresses') }}</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-0.5 fs-12 font-fanum fw-semibold">{{ number_format($addressesCount) }}</span>
                            </a>

                            {{-- 3. Personal info / account details (opens edit details view) --}}
                            <a href="#profile" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between avisa-tab-trigger">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ri-user-3-line avisa-menu-icon"></i>
                                    <span class="avisa-menu-text">{{ __('Personal info / account details') }}</span>
                                </div>
                            </a>

                            {{-- 4. Support --}}
                            <a href="#support" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between avisa-tab-trigger">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ri-customer-service-2-line avisa-menu-icon"></i>
                                    <span class="avisa-menu-text">{{ __('Support') }}</span>
                                </div>
                            </a>

                            {{-- 5. Logout --}}
                            <a href="{{ route('client.sign-out') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between text-danger border-0">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ri-logout-box-r-line avisa-menu-icon text-danger"></i>
                                    <span class="avisa-menu-text text-danger fw-semibold">{{ __('Sign-out') }}</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    {{-- Version number (wish1.png bottom) --}}
                    <div class="text-center py-2 mb-4">
                        <span class="text-secondary font-fanum fs-12">نسخه {{ config('app.version', '۱.۰.۰') }}</span>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- ============================================================ --}}
                {{-- TAB 2: ACCOUNT DETAILS (wish-profile.png Reference Layout)   --}}
                {{-- ============================================================ --}}
                <div class="tab" id="profile">
                    {{-- Sub-header with back arrow pointing right in RTL (wish-profile.png) --}}
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Account details') }}</h4>
                    </div>

                    <div class="card avisa-card-ref overflow-hidden mb-4">
                        <div class="list-group list-group-flush avisa-details-list">
                            {{-- Row 1: Personal info (Name, Birthdate, Gender) -> Opens #profile-edit --}}
                            <a href="#profile-edit" class="list-group-item list-group-item-action p-3 d-flex align-items-center justify-content-between avisa-tab-trigger">
                                <div>
                                    <span class="text-muted fs-12 d-block mb-1">{{ __('Personal Information') }}</span>
                                    <span class="fw-bold text-dark fs-14 font-fanum">
                                        {{ $customer->name ?: '-' }}{{ $customer->dob ? '، ' . $customer->dob->jdate('j F Y') : '' }}{{ $customer->sex ? '، ' . ($customer->sex === 'MALE' ? 'مرد' : 'زن') : '' }}
                                    </span>
                                </div>
                                <span class="avisa-pencil-btn"><i class="ri-pencil-line"></i></span>
                            </a>

                            {{-- Row 2: Mobile number with Verified Badge --}}
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted fs-12 d-block mb-1">{{ __('Mobile') }}</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark fs-14 font-fanum" dir="ltr">{{ $customer->mobile }}</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5 fs-11 fw-semibold">
                                            <i class="ri-checkbox-circle-fill me-1"></i>{{ __('Verified') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="avisa-pencil-btn text-muted"><i class="ri-pencil-line"></i></span>
                            </div>

                            {{-- Row 3: Email Address --}}
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#collapseEmailEdit" role="button">
                                    <div>
                                        <span class="text-muted fs-12 d-block mb-1">{{ __('Email') }}</span>
                                        <span class="fw-bold text-dark fs-14">{{ $customer->email ?: __('Not set') }}</span>
                                    </div>
                                    <span class="avisa-pencil-btn"><i class="ri-pencil-line"></i></span>
                                </div>
                                <div class="collapse mt-3" id="collapseEmailEdit">
                                    <form action="{{ route('client.profile.save') }}" method="post" class="p-3 bg-light rounded-3 border">
                                        @csrf
                                        <input type="hidden" name="_tab_redirect" value="#profile">
                                        <div class="form-group mb-2">
                                            <label class="fs-12 text-muted mb-1">{{ __('Email') }}</label>
                                            <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">{{ __('Save') }}</button>
                                    </form>
                                </div>
                            </div>

                            {{-- Row 4: Change Password --}}
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#collapsePasswordEdit" role="button">
                                    <div>
                                        <span class="fw-bold text-dark fs-14">{{ __('Change password') }}</span>
                                    </div>
                                    <i class="ri-arrow-left-s-line text-dark fs-5"></i>
                                </div>
                                <div class="collapse mt-3" id="collapsePasswordEdit">
                                    <form action="{{ route('client.profile.save') }}" method="post" class="p-3 bg-light rounded-3 border">
                                        @csrf
                                        <input type="hidden" name="_tab_redirect" value="#profile">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <label class="fs-12 text-muted mb-1">{{ __('Password') }}</label>
                                                <input type="password" name="password" class="form-control" placeholder="{{ __('Password') }}" minlength="8" required>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="fs-12 text-muted mb-1">{{ __('password repeat') }}</label>
                                                <input type="password" name="password_confirmation" class="form-control" placeholder="{{ __('password repeat') }}" minlength="8" required>
                                            </div>
                                            <div class="col-12 mt-2">
                                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">{{ __('Save') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Row 5: Bank Account Info (For refund deposits) --}}
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#collapseBankEdit" role="button">
                                    <div>
                                        <span class="text-muted fs-12 d-block mb-1">{{ __('Bank account info') }}</span>
                                        <span class="fw-bold text-dark fs-14 d-block font-fanum">
                                            @if($customer->bank_sheba)
                                                <span dir="ltr">{{ $customer->bank_sheba }}</span>
                                            @elseif($customer->bank_card)
                                                <span dir="ltr">{{ $customer->bank_card }}</span>
                                            @else
                                                <span class="text-muted fw-normal fs-13">{{ __('For refund deposits') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <span class="avisa-pencil-btn"><i class="ri-pencil-line"></i></span>
                                </div>
                                <div class="collapse mt-3" id="collapseBankEdit">
                                    <form action="{{ route('client.profile.save') }}" method="post" class="p-3 bg-light rounded-3 border">
                                        @csrf
                                        <input type="hidden" name="_tab_redirect" value="#profile">
                                        <div class="form-group mb-2">
                                            <label class="fs-12 text-muted mb-1">{{ __('Card number') }}</label>
                                            <input type="text" name="bank_card" class="form-control font-fanum" placeholder="---- ---- ---- ----" value="{{ old('bank_card', $customer->bank_card) }}" dir="ltr">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="fs-12 text-muted mb-1">{{ __('IBAN') }}</label>
                                            <input type="text" name="bank_sheba" class="form-control font-fanum" placeholder="IR------------------------" value="{{ old('bank_sheba', $customer->bank_sheba) }}" dir="ltr">
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">{{ __('Save') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 3: PERSONAL INFO FORM (wish-profile-edit.png Reference)    --}}
                {{-- ============================================================ --}}
                <div class="tab" id="profile-edit">
                    {{-- Sub-header with back arrow to #profile (wish-profile-edit.png) --}}
                    <div class="avisa-subnav-head mb-3">
                        <a href="#profile" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Personal Information') }}</h4>
                    </div>

                    <div class="card avisa-card-ref p-3 mb-4">
                        <form action="{{ route('client.profile.save') }}" method="post">
                            @csrf
                            <input type="hidden" name="_tab_redirect" value="#profile">

                            {{-- First name --}}
                            <div class="avisa-field-outline mb-3">
                                <label class="avisa-field-label">{{ __('First name') }}</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $firstName) }}" required>
                            </div>

                            {{-- Last name --}}
                            <div class="avisa-field-outline mb-3">
                                <label class="avisa-field-label">{{ __('Last name') }}</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $lastName) }}" required>
                            </div>

                            {{-- Date of birth (Segmented 3-part box matching wish-profile-edit.png) --}}
                            <div class="mb-3">
                                <label class="fw-bold text-dark fs-13 mb-1.5 d-block">{{ __('Date of birth') }}</label>
                                <div class="avisa-dob-segmented">
                                    {{-- Day (Rightmost in RTL) --}}
                                    <div class="avisa-dob-col">
                                        <span class="avisa-field-label">{{ __('Day') }}</span>
                                        <select name="dob_day" class="form-select font-fanum">
                                            <option value="">-</option>
                                            @for($d = 1; $d <= 31; $d++)
                                                <option value="{{ $d }}" {{ old('dob_day', $currentDobDay) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    {{-- Month (Middle in RTL) --}}
                                    <div class="avisa-dob-col">
                                        <span class="avisa-field-label">{{ __('Month') }}</span>
                                        <select name="dob_month" class="form-select font-fanum">
                                            <option value="">-</option>
                                            @foreach($persianMonths as $mNum => $mName)
                                                <option value="{{ $mNum }}" {{ old('dob_month', $currentDobMonth) == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- Year (Leftmost in RTL) --}}
                                    <div class="avisa-dob-col">
                                        <span class="avisa-field-label">{{ __('Year') }}</span>
                                        <select name="dob_year" class="form-select font-fanum">
                                            <option value="">-</option>
                                            @for($y = 1405; $y >= 1320; $y--)
                                                <option value="{{ $y }}" {{ old('dob_year', $currentDobYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Gender --}}
                            <div class="avisa-field-outline mb-3">
                                <label class="avisa-field-label">{{ __('Gender') }}</label>
                                <select name="sex" class="form-select">
                                    <option value="">{{ __('Select') }}</option>
                                    <option value="MALE" {{ old('sex', $customer->sex) === 'MALE' ? 'selected' : '' }}>مرد</option>
                                    <option value="FEMALE" {{ old('sex', $customer->sex) === 'FEMALE' ? 'selected' : '' }}>زن</option>
                                </select>
                            </div>

                            {{-- National code --}}
                            <div class="avisa-field-outline mb-3">
                                <label class="avisa-field-label">{{ __('National code') }}</label>
                                <input type="text" name="national_code" class="form-control font-fanum" maxlength="10" placeholder="5479942591" value="{{ old('national_code', $customer->national_code) }}" dir="ltr">
                            </div>

                            {{-- Emergency phone --}}
                            <div class="avisa-field-outline mb-1">
                                <label class="avisa-field-label">{{ __('Emergency contact number') }}</label>
                                <input type="text" name="emergency_phone" class="form-control font-fanum" placeholder="09xxxxxxxxx" value="{{ old('emergency_phone', $customer->emergency_phone) }}" dir="ltr">
                            </div>
                            <small class="text-muted fs-12 mt-1 d-block pe-1 mb-4">{{ __('Emergency contact note') }}</small>

                            {{-- Bottom wide Save button matching wish-profile-edit.png --}}
                            <button type="submit" class="avisa-btn-save-ref shadow-xs">
                                {{ __('Save') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 4: FAVORITES (wish-submenu.png Reference Layout)         --}}
                {{-- ============================================================ --}}
                <div class="tab" id="likes">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Favorites') }}</h4>
                    </div>

                    @php
                        $likedProducts = $customer->favorites()->with(['category'])->get();
                    @endphp

                    @if($likedProducts->count() > 0)
                        <div class="row g-3">
                            @foreach($likedProducts as $product)
                                <div class="col-12 col-sm-6">
                                    @include('client.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State matching wish-submenu.png --}}
                        <div class="card avisa-card-ref text-center py-5 px-3 mb-4">
                            <div class="avisa-empty-state-icon mb-3">
                                <svg width="90" height="90" viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 28L45 15L72 28V62L45 75L18 62V28Z" fill="#F1F5F9" stroke="#CBD5E1" stroke-width="2"/>
                                    <path d="M45 15V75" stroke="#E2E8F0" stroke-width="2"/>
                                    <circle cx="45" cy="45" r="16" fill="white" stroke="#DB9A00" stroke-width="2.5"/>
                                    <path d="M40 45H50M45 40V50" stroke="#DB9A00" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="58" cy="24" r="6" fill="#DB9A00"/>
                                </svg>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">{{ __('Your favorites list is empty') }}</h5>
                            <div class="mt-3">
                                <a href="{{ route('client.products') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4 py-2">
                                    {{ __('Explore Products') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 5: ACTIVE ORDERS                                         --}}
                {{-- ============================================================ --}}
                <div class="tab" id="active-orders">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Active orders') }}</h4>
                    </div>

                    @if($activeInvoices->count() > 0)
                        <div class="d-flex flex-column gap-3 mb-4">
                            @foreach($activeInvoices as $inv)
                                @include('client.customer.partials.invoice-card', ['inv' => $inv, 'isActiveOrder' => true])
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="card avisa-card-ref text-center py-5 px-3 mb-4">
                            <div class="avisa-empty-state-icon mb-3">
                                <i class="ri-inbox-line fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">{{ __('Your active orders list is empty') }}</h5>
                            <div class="mt-3">
                                <a href="{{ route('client.products') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4 py-2">
                                    {{ __('Explore Products') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 6: INVOICES & PREVIOUS ORDERS                            --}}
                {{-- ============================================================ --}}
                <div class="tab" id="invoices">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Previous orders & invoices') }}</h4>
                    </div>

                    @if($allInvoices->count() > 0)
                        <div class="d-flex flex-column gap-3 mb-4">
                            @foreach($allInvoices as $inv)
                                @include('client.customer.partials.invoice-card', ['inv' => $inv, 'isActiveOrder' => false])
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="card avisa-card-ref text-center py-5 px-3 mb-4">
                            <div class="avisa-empty-state-icon mb-3">
                                <i class="ri-file-list-3-line fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">{{ __('Your invoices list is empty') }}</h5>
                        </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 7: ADDRESSES                                             --}}
                {{-- ============================================================ --}}
                <div class="tab" id="addresses">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Addresses') }}</h4>
                    </div>

                    <div class="card avisa-card-ref p-3 mb-4">
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

                {{-- ============================================================ --}}
                {{-- TAB 8: SUPPORT & FAQ                                         --}}
                {{-- ============================================================ --}}
                <div class="tab" id="support">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('Support') }}</h4>
                    </div>

                    {{-- Step 1: FAQ Accordion --}}
                    <div class="card avisa-card-ref mb-3 overflow-hidden">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="ri-questionnaire-line text-primary fs-5"></i>
                                {{ __('Frequently Asked Questions') }}
                            </h6>
                        </div>
                        <div class="accordion accordion-flush" id="avisaFaqAccordion">
                            {{-- Q1 --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFaqOne">
                                    <button class="accordion-button collapsed fs-14 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqOne" aria-expanded="false" aria-controls="collapseFaqOne">
                                        {{ __("Order hasn't arrived yet, what should I do?") }}
                                    </button>
                                </h2>
                                <div id="collapseFaqOne" class="accordion-collapse collapse" aria-labelledby="headingFaqOne" data-bs-parent="#avisaFaqAccordion">
                                    <div class="accordion-body text-muted fs-13 lh-base">
                                        {{ __("Order hasn't arrived answer") }}
                                    </div>
                                </div>
                            </div>

                            {{-- Q2 --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFaqTwo">
                                    <button class="accordion-button collapsed fs-14 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqTwo" aria-expanded="false" aria-controls="collapseFaqTwo">
                                        {{ __("Courier arrived, what should I do?") }}
                                    </button>
                                </h2>
                                <div id="collapseFaqTwo" class="accordion-collapse collapse" aria-labelledby="headingFaqTwo" data-bs-parent="#avisaFaqAccordion">
                                    <div class="accordion-body text-muted fs-13 lh-base">
                                        {{ __("Courier arrived answer") }}
                                    </div>
                                </div>
                            </div>

                            {{-- Q3 --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFaqThree">
                                    <button class="accordion-button collapsed fs-14 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqThree" aria-expanded="false" aria-controls="collapseFaqThree">
                                        {{ __("Who do I contact for order follow-up or changes?") }}
                                    </button>
                                </h2>
                                <div id="collapseFaqThree" class="accordion-collapse collapse" aria-labelledby="headingFaqThree" data-bs-parent="#avisaFaqAccordion">
                                    <div class="accordion-body text-muted fs-13 lh-base">
                                        {{ __("Who do I contact answer") }}
                                    </div>
                                </div>
                            </div>

                            {{-- Q4 --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFaqFour">
                                    <button class="accordion-button collapsed fs-14 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaqFour" aria-expanded="false" aria-controls="collapseFaqFour">
                                        {{ __("How do I pay offline and upload a receipt?") }}
                                    </button>
                                </h2>
                                <div id="collapseFaqFour" class="accordion-collapse collapse" aria-labelledby="headingFaqFour" data-bs-parent="#avisaFaqAccordion">
                                    <div class="accordion-body text-muted fs-13 lh-base">
                                        {{ __("How do I pay offline answer") }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Still need help? Toggle Ticket Submission --}}
                    <div class="card avisa-card-ref p-3 mb-3 text-center">
                        <h6 class="fw-bold text-dark mb-1">{{ __('Need more help?') }}</h6>
                        <p class="text-muted fs-13 mb-3">{{ __('If you did not find your answer above, submit a ticket to our support team.') }}</p>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 mx-auto fw-bold" data-bs-toggle="collapse" data-bs-target="#collapseTicketForm">
                            <i class="ri-customer-service-2-line me-1"></i>
                            {{ __('Submit new ticket') }}
                        </button>
                    </div>

                    {{-- Ticket Form Collapse --}}
                    <div class="collapse mb-4" id="collapseTicketForm">
                        <div class="card avisa-card-ref p-3">
                            <h6 class="fw-bold text-dark mb-3">{{ __('Submit new ticket') }}</h6>
                            <form action="{{ route('client.ticket.submit') }}" method="post">
                                @csrf
                                <div class="form-group mb-2">
                                    <label for="ticket_title" class="fs-12 text-muted mb-1">{{ __('Title') }}</label>
                                    <input type="text" id="ticket_title" name="title" class="form-control" required placeholder="{{ __('Title') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="ticket_body" class="fs-12 text-muted mb-1">{{ __('Description Text') }}</label>
                                    <textarea id="ticket_body" name="body" rows="4" class="form-control" required placeholder="{{ __('Your message ...') }}"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold w-100">
                                    <i class="ri-send-plane-2-line me-1"></i>
                                    {{ __('Send ticket') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Step 3: Existing Tickets List --}}
                    @if($ticketsCount > 0)
                        <h6 class="fw-bold text-dark mb-3">{{ __('Previous tickets') }}</h6>
                        <div class="card avisa-card-ref overflow-hidden mb-4">
                            <div class="list-group list-group-flush">
                                @foreach($customer->tickets()->latest()->get() as $ticket)
                                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="fw-bold text-dark fs-14 d-block">{{ $ticket->title }}</span>
                                            <small class="text-muted fs-11 font-fanum">{{ $ticket->created_at->jdate('Y/m/d') }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="inv-badge inv-{{ $ticket->status }}">{{ __($ticket->status) }}</span>
                                            <a href="{{ route('client.ticket.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5 fs-11">
                                                {{ __('View') }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- TAB 9: CREDIT & WALLET BALANCE                               --}}
                {{-- ============================================================ --}}
                <div class="tab" id="credit">
                    <div class="avisa-subnav-head mb-3">
                        <a href="#summary" class="avisa-subnav-back avisa-tab-trigger" aria-label="{{ __('Back') }}">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                        <h4 class="fw-bold mb-0 text-dark">{{ __('My balance and credit') }}</h4>
                    </div>

                    <div class="card avisa-card-ref p-4 text-center mb-3">
                        <div class="avisa-credit-big-icon mb-2 text-primary">
                            <i class="ri-copper-coin-line fs-1"></i>
                        </div>
                        <span class="text-muted fs-13 d-block mb-1">{{ __('Account balance') }}</span>
                        <h2 class="fw-bold text-dark mb-0 font-fanum">
                            {{ number_format($customer->credit) }}
                            <small class="fs-14 text-muted fw-normal">{{ config('app.currency.symbol', 'تومان') }}</small>
                        </h2>
                    </div>

                    <h6 class="fw-bold text-dark mb-3">{{ __('Credit history') }}</h6>
                    @if($customer->credits()->count() > 0)
                        <div class="d-flex flex-column gap-2 mb-4">
                            @foreach($customer->credits()->latest()->get() as $cr)
                                <div class="card avisa-card-ref p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-bold text-dark fs-14 font-fanum">{{ number_format($cr->amount) }} {{ config('app.currency.symbol') }}</span>
                                        <small class="text-muted fs-11 font-fanum">{{ $cr->created_at->jdate('Y/m/d H:i') }}</small>
                                    </div>
                                    @php
                                        $data = json_decode($cr->data);
                                    @endphp
                                    @if(isset($data->message))
                                        <div class="text-muted fs-12 mt-1">
                                            <i class="ri-chat-3-line me-1"></i> {{ $data->message }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="card avisa-card-ref text-center py-4 px-3 mb-4 text-muted fs-13">
                            {{ __('No transactions found') }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Offline Payment Receipt Modal --}}
    <div class="modal fade" id="avisa-receipt-modal" tabindex="-1" aria-labelledby="avisa-receipt-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content avisa-receipt-modal rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold" id="avisa-receipt-modal-label">{{ __('Upload payment receipt') }}</h5>
                        <small class="text-muted" data-receipt-modal-title></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-receipt-modal-close aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="avisa-receipt-modal__intro text-muted fs-13 mb-3">
                        {{ __('This is an offline payment. After transferring the money, upload a clear receipt photo or PDF.') }}
                    </p>
                    @php
                        $modalInvoice = $needUploadInvoices->first()
                            ?? $awaitingReceiptInvoices->first()
                            ?? $customer->invoices()->latest('id')->first()
                            ?? (new \App\Models\Invoice())->forceFill(['id' => 0]);
                    @endphp
                    @include('components.payment-receipt-uploader', [
                        'invoice' => $modalInvoice,
                        'inputId' => 'avisa-modal-receipts',
                        'formId' => 'avisa-modal-receipt-form',
                    ])
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Navigation Bar --}}
    @include('client.customer.partials.bottom-nav')
</section>

@endsection
