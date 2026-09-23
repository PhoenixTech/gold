<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('Customer-Facing Checkout Flow & Admin Management Guide') }}</h1>
    <p class="help-article-lead">
        {{ __('Visual mobile guide of customer checkout steps, paired with admin control panels and fulfillment safeguards.') }}
    </p>
</header>

<div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3">
    <div class="d-flex align-items-center gap-3">
        <i class="ri-git-branch-fill text-warning fs-3"></i>
        <div>
            <strong class="d-block text-dark">{{ __('Two Distinct Fulfillment Pathways from Cart Step 2:') }}</strong>
            <span class="text-muted fs-13 d-block mt-1">
                <strong>{{ __('Option A Workflow (Courier/Post):') }}</strong> {{ __('Option A Workflow Detail') }}
            </span>
            <span class="text-muted fs-13 d-block mt-1">
                <strong>{{ __('Option B Workflow (Gallery Pickup):') }}</strong> {{ __('Option B Workflow Detail') }}
            </span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-warning fw-bold px-3">
            <i class="ri-dashboard-line me-1"></i>{{ __('Open Order Board') }}
        </a>
        <a href="{{ route('admin.invoice.index') }}" class="btn btn-sm btn-outline-dark fw-bold px-3">
            <i class="ri-file-list-3-line me-1"></i>{{ __('Open Invoices List') }}
        </a>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h3 class="fs-16 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="ri-shopping-cart-2-line text-primary"></i>
        {{ __('Cart Step 2: Delivery Method Selection (2 Options)') }}
    </h3>
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12">
        {{ __('Customer mobile view') }}
    </span>
</div>

<ol class="help-steps mb-5">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Cart Step 1: Items & Live Price Lock') }}</h2>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
                    {{ __('Live gold quote duration') }}
                </span>
            </div>
            <p>{{ __('Cart Step 1 Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Live gold quote duration') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('In-person gallery collection') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_01_cart.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_01_cart.png') }}" alt="{{ __('Cart Step 1: Items & Live Price Lock') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">2A</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Option A: Delivery to Address (Courier / Post)') }}</h2>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
                    {{ __('Option A Workflow (Courier/Post):') }}
                </span>
            </div>
            <p>{{ __('Option A Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-truck-line text-primary"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-primary me-1"></i>{{ __('Option A Workflow Detail') }}</li>
                            <li><i class="ri-check-line text-primary me-1"></i>{{ __('Secret 4-digit courier PIN') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_delivery_address.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_delivery_address.png') }}" alt="{{ __('Option A: Delivery to Address (Courier / Post)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">2B</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Option B: In-Person Gallery Pickup') }}</h2>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">
                    {{ __('Option B Workflow (Gallery Pickup):') }}
                </span>
            </div>
            <p>{{ __('Option B Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-store-2-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Option B Workflow Detail') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('In-person gallery collection') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_delivery_pickup.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_delivery_pickup.png') }}" alt="{{ __('Option B: In-Person Gallery Pickup') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Cart Step 3: Order Review & Payment') }}</h2>
                <span class="badge bg-info-subtle text-info border border-info-subtle fs-12">
                    {{ __('Gallery active bank card') }}
                </span>
            </div>
            <p>{{ __('Cart Step 3 Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-bank-card-line text-info"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-info me-1"></i>{{ __('Gallery active bank card') }}</li>
                            <li><i class="ri-check-line text-info me-1"></i>{{ __('3-hour countdown timer') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_03_payment.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_03_payment.png') }}" alt="{{ __('Cart Step 3: Order Review & Payment') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>
</ol>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pt-4 border-top">
    <h3 class="fs-16 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="ri-file-text-line text-primary"></i>
        {{ __('Customer Mobile Checkout Journey') }}
    </h3>
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12">
        {{ __('Customer mobile view') }}
    </span>
</div>

<ol class="help-steps mb-5">
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Offline Order Placement & 3-Hour Deadline') }}</h2>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
                    {{ __('WAITING_RECEIPT') }}
                </span>
            </div>
            <p>{{ __('Offline Order Placement Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('3-hour countdown timer') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Gallery active bank card') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_waiting_receipt.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_02_waiting_receipt.png') }}" alt="{{ __('Offline Order Placement & 3-Hour Deadline') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">5</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Submitting Bank Payment Receipt') }}</h2>
                <span class="badge bg-info-subtle text-info border border-info-subtle fs-12">
                    {{ __('Receipt slip & tracking ID') }}
                </span>
            </div>
            <p>{{ __('Submitting Bank Payment Receipt Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Receipt slip & tracking ID') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('4-point verification checklist') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_03_receipt_form.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_03_receipt_form.png') }}" alt="{{ __('Submitting Bank Payment Receipt') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">6</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Receipt Under Review (Timer Paused)') }}</h2>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
                    {{ __('WAITING_CONFIRMATION') }}
                </span>
            </div>
            <p>{{ __('Receipt Under Review Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Review in progress banner') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Request re-upload vs decline') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_04_waiting_confirmation.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_04_waiting_confirmation.png') }}" alt="{{ __('Receipt Under Review (Timer Paused)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">7</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Payment Approved & Order Packaging') }}</h2>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">
                    {{ __('PROCESSING') }}
                </span>
            </div>
            <p>{{ __('Payment Approved Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Packaging reassurance banner') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Real-time fulfillment stages') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_05_processing.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_05_processing.png') }}" alt="{{ __('Payment Approved & Order Packaging') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>

    <li>
        <span class="help-step-num" aria-hidden="true">8</span>
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="mb-0">{{ __('Fulfillment: Courier Handover (4-Digit PIN)') }}</h2>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
                    {{ __('OUT_FOR_DELIVERY') }}
                </span>
            </div>
            <p>{{ __('Fulfillment: Courier Handover Desc') }}</p>
            <div class="row g-3 align-items-start mt-2">
                <div class="col-12 col-md-7">
                    <div class="bg-white border rounded-3 p-3 shadow-xs">
                        <span class="fw-bold fs-13 text-dark d-flex align-items-center gap-1 mb-2">
                            <i class="ri-shield-check-line text-success"></i>
                            {{ __('Admin key points') }}
                        </span>
                        <ul class="list-unstyled mb-0 fs-13 text-muted d-grid gap-1">
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('Secret 4-digit courier PIN') }}</li>
                            <li><i class="ri-check-line text-success me-1"></i>{{ __('In-person gallery collection') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-2 mx-auto" style="max-width: 250px;">
                        <a href="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_06_out_for_delivery.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/mobile/checkout_mobile_step_06_out_for_delivery.png') }}" alt="{{ __('Fulfillment: Courier Handover (4-Digit PIN)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-2">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-smartphone-line me-1"></i>{{ __('Customer mobile view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>
</ol>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pt-4 border-top">
    <h3 class="fs-16 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="ri-computer-line text-primary"></i>
        {{ __('Admin Operational Dashboards & Workflows') }}
    </h3>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
        {{ __('Admin desktop view') }}
    </span>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card border rounded-3 shadow-xs bg-white h-100 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-2.5 py-1 fs-12">{{ __('Admin Order Board (Pipeline)') }}</span>
                </div>
                <a href="{{ route('admin.order-board.index') }}" class="btn btn-sm btn-primary rounded-pill px-2.5 py-0.5 fs-12">
                    <i class="ri-external-link-line me-1"></i>{{ __('Open Order Board') }}
                </a>
            </div>
            <div class="card-body p-3">
                <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-11 d-inline-block mb-2">dashboard/order-board</code>
                <p class="text-muted fs-13 mb-3">{{ __('Admin Order Board Desc') }}</p>
                <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center">
                    <a href="{{ asset('workflow-screenshots/admin_order_board.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                        <img src="{{ asset('workflow-screenshots/admin_order_board.png') }}" alt="{{ __('Admin Order Board (Pipeline)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border rounded-3 shadow-xs bg-white h-100 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark px-2.5 py-1 fs-12">{{ __('Admin Invoice Edit & Verification') }}</span>
                </div>
                <a href="{{ route('admin.invoice.index') }}" class="btn btn-sm btn-outline-dark rounded-pill px-2.5 py-0.5 fs-12">
                    <i class="ri-external-link-line me-1"></i>{{ __('Open Invoices List') }}
                </a>
            </div>
            <div class="card-body p-3">
                <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-11 d-inline-block mb-2">dashboard/invoices/edit/{hash}</code>
                <p class="text-muted fs-13 mb-3">{{ __('Admin Invoice Edit Desc') }}</p>
                <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center">
                    <a href="{{ asset('workflow-screenshots/admin_invoice_edit.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                        <img src="{{ asset('workflow-screenshots/admin_invoice_edit.png') }}" alt="{{ __('Admin Invoice Edit & Verification') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border rounded-3 shadow-xs bg-white h-100 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark px-2.5 py-1 fs-12">{{ __('Courier Panel (Delivery Verification)') }}</span>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                    <i class="ri-motorbike-line me-1"></i>{{ __('Courier') }}
                </span>
            </div>
            <div class="card-body p-3">
                <code class="fw-bold text-warning-emphasis font-monospace bg-warning-subtle px-2 py-0.5 rounded border border-warning-subtle fs-11 d-inline-block mb-2">dashboard/deliveries</code>
                <p class="text-muted fs-13 mb-3">{{ __('Courier Panel Desc') }}</p>
                <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center">
                    <a href="{{ asset('workflow-screenshots/courier_panel_deliveries.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                        <img src="{{ asset('workflow-screenshots/courier_panel_deliveries.png') }}" alt="{{ __('Courier Panel (Delivery Verification)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pt-4 border-top">
    <h3 class="fs-16 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="ri-git-commit-line text-primary"></i>
        {{ __('Admin 5-Step Order Stepper') }}
    </h3>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
        {{ __('5-step administrative stepper') }}
    </span>
</div>
<p class="text-muted fs-14 mb-4">{{ __('Admin Stepper Desc') }}</p>

<div class="row g-3 mb-5">
    <div class="col-12">
        <div class="card border rounded-3 shadow-xs bg-white p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-12">1</span>
                    <h5 class="fs-15 fw-bold text-dark mb-0">{{ __('Admin Step 1: Payment (Waiting Receipt)') }}</h5>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
                    {{ __('WAITING_RECEIPT') }}
                </span>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Admin Step 1 Desc') }}</p>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-timer-line me-1 text-warning"></i>{{ __('3-hour countdown timer') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-close-circle-line me-1 text-secondary"></i>{{ __('Cancel invoice') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-error-warning-line me-1 text-danger"></i>{{ __('Mark failed') }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-1.5 mx-auto" style="max-width: 320px;">
                        <a href="{{ asset('workflow-screenshots/admin_step_01_payment.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/admin_step_01_payment.png') }}" alt="{{ __('Admin Step 1: Payment (Waiting Receipt)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-1">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-computer-line me-1"></i>{{ __('Admin desktop view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border rounded-3 shadow-xs bg-white p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-12">2</span>
                    <h5 class="fs-15 fw-bold text-dark mb-0">{{ __('Admin Step 2: Payment Review (Verification Checklist)') }}</h5>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
                    {{ __('WAITING_CONFIRMATION') }}
                </span>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Admin Step 2 Desc') }}</p>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-shield-check-line me-1 text-success"></i>{{ __('4-Point Payment Approval Safeguards') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-check-double-line me-1 text-success"></i>{{ __('Approve Payment') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-refresh-line me-1 text-warning"></i>{{ __('Request Re-upload') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-close-line me-1 text-danger"></i>{{ __('Decline and Cancel') }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-1.5 mx-auto" style="max-width: 320px;">
                        <a href="{{ asset('workflow-screenshots/admin_step_02_payment_review.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/admin_step_02_payment_review.png') }}" alt="{{ __('Admin Step 2: Payment Review (Verification Checklist)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-1">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-computer-line me-1"></i>{{ __('Admin desktop view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border rounded-3 shadow-xs bg-white p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-12">3</span>
                    <h5 class="fs-15 fw-bold text-dark mb-0">{{ __('Admin Step 3: Shipping & Dispatch') }}</h5>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">{{ __('PAID') }}</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle fs-12">{{ __('PROCESSING') }}</span>
                </div>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Admin Step 3 Desc') }}</p>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-save-line me-1 text-primary"></i>{{ __('Save shipment details') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-motorbike-line me-1 text-warning"></i>{{ __('Send for delivery') }} ({{ __('Option A Workflow (Courier/Post):') }})</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-checkbox-circle-line me-1 text-success"></i>{{ __('Mark as completed') }} ({{ __('Option B Workflow (Gallery Pickup):') }})</span>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-1.5 mx-auto" style="max-width: 320px;">
                        <a href="{{ asset('workflow-screenshots/admin_step_03_shipping.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/admin_step_03_shipping.png') }}" alt="{{ __('Admin Step 3: Shipping & Dispatch') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-1">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-computer-line me-1"></i>{{ __('Admin desktop view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border rounded-3 shadow-xs bg-white p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary rounded-pill px-2 py-1 fs-12">4</span>
                    <h5 class="fs-15 fw-bold text-dark mb-0">{{ __('Admin Step 4: Order Delivery & PIN Verification') }}</h5>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
                    {{ __('OUT_FOR_DELIVERY') }}
                </span>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Admin Step 4 Desc') }}</p>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-send-plane-line me-1 text-primary"></i>{{ __('Resend delivery code') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-user-shared-line me-1 text-primary"></i>{{ __('Reassign to another courier') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-arrow-go-back-line me-1 text-secondary"></i>{{ __('Return to preparation') }}</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-12"><i class="ri-shield-keyhole-line me-1"></i>{{ __('Courier PIN Verification Guardrail') }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-1.5 mx-auto" style="max-width: 320px;">
                        <a href="{{ asset('workflow-screenshots/admin_step_04_out_for_delivery.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/admin_step_04_out_for_delivery.png') }}" alt="{{ __('Admin Step 4: Order Delivery & PIN Verification') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-1">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-computer-line me-1"></i>{{ __('Admin desktop view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border rounded-3 shadow-xs bg-white p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success rounded-pill px-2 py-1 fs-12">5</span>
                    <h5 class="fs-15 fw-bold text-dark mb-0">{{ __('Admin Step 5: Completed (Order Finalized)') }}</h5>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">
                    {{ __('COMPLETED') }}
                </span>
            </div>
            <p class="text-muted fs-13 mb-3">{{ __('Admin Step 5 Desc') }}</p>
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-check-line me-1 text-success"></i>{{ __('Order delivered') }}</span>
                        <span class="badge bg-light text-dark border fs-12"><i class="ri-printer-line me-1 text-primary"></i>{{ __('Print invoice') }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-5 text-center">
                    <div class="card border rounded-3 shadow-xs bg-light p-1.5 mx-auto" style="max-width: 320px;">
                        <a href="{{ asset('workflow-screenshots/admin_step_05_completed.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                            <img src="{{ asset('workflow-screenshots/admin_step_05_completed.png') }}" alt="{{ __('Admin Step 5: Completed (Order Finalized)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                        </a>
                        <div class="card-footer bg-white border-top py-1 px-2 text-center mt-1">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-computer-line me-1"></i>{{ __('Admin desktop view') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pt-4 border-top">
    <h3 class="fs-16 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="ri-close-circle-line text-danger"></i>
        {{ __('Terminal States & Failure Workflows') }}
    </h3>
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-12">
        {{ __('Inventory Safeguard:') }}
    </span>
</div>
<p class="text-muted fs-14 mb-4">{{ __('Terminal States Desc') }}</p>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border border-danger-subtle rounded-3 shadow-xs bg-white p-3 h-100 d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-danger px-2.5 py-1 fs-12">{{ __('FAILED') }}</span>
                <i class="ri-time-line text-danger fs-4"></i>
            </div>
            <h5 class="fs-14 fw-bold text-dark mb-2">{{ __('Failed Status (3-Hour Expiry & Online Failures)') }}</h5>
            <p class="text-muted fs-13 mb-3">{{ __('Failed Status Desc') }}</p>
            <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center my-auto mb-3">
                <a href="{{ asset('workflow-screenshots/admin_status_failed.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                    <img src="{{ asset('workflow-screenshots/admin_status_failed.png') }}" alt="{{ __('Failed Status (3-Hour Expiry & Online Failures)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                </a>
            </div>
            <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between text-danger fs-12">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Immediate Gold Stock Restoration') }}</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border border-secondary-subtle rounded-3 shadow-xs bg-white p-3 h-100 d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-secondary px-2.5 py-1 fs-12">{{ __('CANCELED') }}</span>
                <i class="ri-close-line text-secondary fs-4"></i>
            </div>
            <h5 class="fs-14 fw-bold text-dark mb-2">{{ __('Canceled Status (Admin Decline or Customer Cancel)') }}</h5>
            <p class="text-muted fs-13 mb-3">{{ __('Canceled Status Desc') }}</p>
            <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center my-auto mb-3">
                <a href="{{ asset('workflow-screenshots/admin_status_canceled.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                    <img src="{{ asset('workflow-screenshots/admin_status_canceled.png') }}" alt="{{ __('Canceled Status (Admin Decline or Customer Cancel)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                </a>
            </div>
            <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between text-secondary fs-12">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ __('Immediate Gold Stock Restoration') }}</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border border-warning-subtle rounded-3 shadow-xs bg-white p-3 h-100 d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-warning text-dark px-2.5 py-1 fs-12">{{ __('Request Re-upload') }}</span>
                <i class="ri-refresh-line text-warning fs-4"></i>
            </div>
            <h5 class="fs-14 fw-bold text-dark mb-2">{{ __('Receipt Re-Upload Flow (3-Hour Extension)') }}</h5>
            <p class="text-muted fs-13 mb-3">{{ __('Receipt Re-Upload Flow Desc') }}</p>
            <div class="card border rounded-3 overflow-hidden shadow-xs bg-light p-1 text-center my-auto mb-3">
                <a href="{{ asset('workflow-screenshots/admin_status_reupload.png') }}" target="_blank" title="{{ __('Click to open full screenshot') }}">
                    <img src="{{ asset('workflow-screenshots/admin_status_reupload.png') }}" alt="{{ __('Receipt Re-Upload Flow (3-Hour Extension)') }}" class="img-fluid rounded border shadow-sm" loading="lazy">
                </a>
            </div>
            <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between text-warning-emphasis fs-12">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('3-hour countdown timer') }}</span>
            </div>
        </div>
    </div>
</div>

<p class="help-note">
    <strong>{{ __('Inventory Safeguard:') }}</strong>
    {{ __('During WAITING_RECEIPT and WAITING_CONFIRMATION, the gold piece is securely reserved (Reserved). If 3 hours expire without a receipt, the system automatically releases the piece back to available stock.') }}
</p>
