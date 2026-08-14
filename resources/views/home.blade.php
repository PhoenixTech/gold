@extends('layouts.app')

@section('content')
    <section id="AvisaCustomer" class="admin-dashboard container-fluid px-0">
        <!-- Welcome Hero Banner -->
        <div class="avisa-hero-card mb-4">
            <div class="avisa-hero-body">
                <div class="avisa-hero-user">
                    <img src="{{auth()->user()->avatar()}}" alt="avatar" class="avisa-hero-avatar">
                    <div>
                        <h3 class="avisa-hero-title">
                            {{__("Welcome back")}}, {{auth()->user()->name}}! 👋
                        </h3>
                        <p class="avisa-hero-sub text-muted mb-0">
                            <i class="ri-mail-line me-1"></i> {{auth()->user()->email}}
                            @if(auth()->user()->role)
                                <span class="mx-2">•</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 fs-12">
                                    {{auth()->user()->role}}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="avisa-hero-actions">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="text-center px-2">
                            <span class="d-block text-muted fs-12 fw-bold">{{__("Posts")}}</span>
                            <span class="fw-extrabold fs-16">{{number_format(auth()->user()->posts()->count())}}</span>
                        </div>
                        <div class="vr opacity-25 d-none d-sm-block"></div>
                        <div class="text-center px-2">
                            <span class="d-block text-muted fs-12 fw-bold">{{__("Products")}}</span>
                            <span class="fw-extrabold fs-16">{{number_format(auth()->user()->products()->count())}}</span>
                        </div>
                        <div class="vr opacity-25 d-none d-sm-block"></div>
                        <div class="text-center px-2">
                            <span class="d-block text-muted fs-12 fw-bold">{{__("Comments")}}</span>
                            <span class="fw-extrabold fs-16">{{number_format(auth()->user()->comments()->count())}}</span>
                        </div>
                        <div class="vr opacity-25 d-none d-sm-block"></div>
                        <div class="text-center px-2">
                            <span class="d-block text-muted fs-12 fw-bold">{{__("Tickets")}}</span>
                            <span class="fw-extrabold fs-16">{{number_format(auth()->user()->tickets()->count())}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Summary Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="avisa-summary-stat-card card-invoice" onclick="window.location.href='{{route('admin.invoice.index')}}?filter%5Bstatus%5D=%5B&quot;PAID&quot;%5D'">
                    <div class="stat-icon-wrapper">
                        <i class="ri-shopping-bag-4-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{__("Need process orders")}}</span>
                        <h3 class="stat-value">{{number_format(\App\Models\Invoice::where('status','PAID')->count())}}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="avisa-summary-stat-card card-ticket" onclick="window.location.href='{{route('admin.ticket.index')}}?filter%5Bstatus%5D=%5B&quot;PENDING&quot;%5D'">
                    <div class="stat-icon-wrapper">
                        <i class="ri-customer-service-2-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{__("Pending tickets")}}</span>
                        <h3 class="stat-value">{{number_format(\App\Models\Ticket::where('status','PENDING')->count())}}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="avisa-summary-stat-card card-credit">
                    <div class="stat-icon-wrapper">
                        <i class="ri-user-heart-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{__("Monthly Visitors")}}</span>
                        <h3 class="stat-value">{{number_format($all_visitor)}}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="avisa-summary-stat-card card-address">
                    <div class="stat-icon-wrapper">
                        <i class="ri-smartphone-line"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-label">{{__("Mobile Visitors")}}</span>
                        <h3 class="stat-value">{{number_format($mobiles_count)}}</h3>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
