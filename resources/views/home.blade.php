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

        <!-- Visitor Chart Widget -->
        <div class="row mb-4">
            <div class="col-12" id="visitor-container">
                <div class="avisa-summary-widget">
                    <div class="widget-header">
                        <h4><i class="ri-bar-chart-box-line me-2 text-primary"></i> {{__("Last month visits")}}</h4>
                    </div>
                    <div class="widget-body p-3" style="height: 320px; position: relative;">
                        <canvas id="visitor-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Devices & Orders Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="avisa-summary-widget h-100">
                    <div class="widget-header">
                        <h4><i class="ri-computer-line me-2 text-primary"></i> {{__("Last month visitors devices")}}</h4>
                    </div>
                    <div class="widget-body p-3" style="height: 300px; position: relative;">
                        <canvas id="visitor-device"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="avisa-summary-widget h-100">
                    <div class="widget-header">
                        <h4><i class="ri-shopping-bag-3-line me-2 text-primary"></i> {{__("Last week orders")}}</h4>
                    </div>
                    <div class="widget-body p-3" style="height: 300px; position: relative;">
                        <canvas id="orders-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js-content')
    <script>
        window.addEventListener('resize', function () {
            if (window.vchart) {
                window.vchart.resize(null, 300);
            }
        });

        window.addEventListener('load', function () {
            if (window.isPaintedChart) {
                return;
            }

            window.isPaintedChart = true;

            let visits = @json($visits);
            let ctx = document.getElementById('visitor-chart').getContext('2d');
            window.vchart = new window.chartjs(ctx, {
                type: 'line',
                data: {
                    labels: @json($dates),
                    datasets: [
                        {
                            label: "{{__('Visitors')}}",
                            backgroundColor: 'rgba(128,0,255,0.1)',
                            borderColor: 'rgba(140,0,255,0.6)',
                            data: visits.subItem('count', 1),
                            fill: true,
                        },
                        {
                            label: "{{__('Visits')}}",
                            backgroundColor: 'rgba(255,0,0,0.1)',
                            borderColor: '#ff000099',
                            data: visits.subItem('visits', 1),
                            fill: true,
                        },
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    resizeDelay: 1000,
                    layout: {
                        padding: 10,
                    },
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Website visits traffic'
                    }
                }
            });

            let ctx2 = document.getElementById('visitor-device').getContext('2d');
            window.dchart = new window.chartjs(ctx2, {
                type: 'pie',
                data: {
                    labels: ['All visitors','Desktop', 'Mobile / Tablet'],
                    datasets: [
                        {
                            label:"{{__('Devices')}}",
                            data: [{{$all_visitor}},{{$all_visitor - $mobiles_count}}, {{$mobiles_count}}],
                            backgroundColor: ['rgba(255,128,0,0.69)', 'rgba(255,0,54,0.56)','rgba(0,202,202,0.56)'],
                            hoverBackgroundColor: ['rgba(255,128,0,0.9)', 'rgba(255,0,54,0.9)','rgba(0,202,202,0.9)'],
                            borderWidth: 1,
                            borderColor: '#00000011'
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    resizeDelay: 1000,
                    layout: {
                        padding: 10
                    },
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Visitor device'
                    }
                }
            });

            let ctx3 = document.getElementById('orders-chart').getContext('2d');
            window.ochart = new window.chartjs(ctx3, {
                type: 'bar',
                data: {
                    labels: @json($week),
                    datasets: [
                        {
                            label: "{{__('Orders')}}",
                            backgroundColor: 'rgba(128,0,255,0.4)',
                            borderColor: 'rgba(140,0,255,0.6)',
                            data: @json($orders),
                            fill: true,
                        },
                        {
                            label: "{{__('Invoices')}}",
                            backgroundColor: 'rgba(255,0,0,0.4)',
                            borderColor: '#ff000099',
                            data: @json($invoices),
                            fill: true,
                        },
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    resizeDelay: 1000,
                    layout: {
                        padding: 10
                    },
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Visitor device'
                    }
                }
            });

            window.dispatchEvent(new Event('resize'));
        });
    </script>
@endsection
