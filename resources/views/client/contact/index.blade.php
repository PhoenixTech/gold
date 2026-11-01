@extends('website.inc.website-layout')

@section('title')
    {{__("Contact us")}} - {{config('app.name')}}
@endsection

@php
    $tel = getSetting('tel') ?: getSetting('phone');
    $email = getSetting('email');
    $address = getSetting('address');
    $about = getSetting('about');
    $socialsRaw = getSettingsGroup('social_');
    $socials = is_array($socialsRaw) ? $socialsRaw : [];
@endphp

@section('content')
<div class="contact-page-view py-4">
    <div class="{{gfx()['container']}}">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb fs-13 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                        {{config('app.name')}}
                    </a>
                </li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                    {{__("Contact us")}}
                </li>
            </ol>
        </nav>

        <div class="mb-4">
            <h1 class="page-title fw-bold text-main mb-1 fs-22 d-flex align-items-center gap-2">
                <i class="ri-customer-service-2-fill text-primary"></i>
                <span>{{__("Contact us")}}</span>
            </h1>
        </div>

        <div class="row g-3 g-md-4 mb-4">
            <div class="col-md-4">
                <div class="contact-info-card card border-warning-subtle shadow-xs rounded-4 p-3.5 bg-warning-subtle h-100 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2.5 mb-2.5">
                        <div class="info-icon-wrapper rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 42px; height: 42px;">
                            <i class="ri-phone-fill fs-18"></i>
                        </div>
                        <h2 class="fw-bold text-main mb-0 fs-15">{{__("Phone Consultation")}}</h2>
                    </div>
                    <div class="mt-auto pt-3 border-top border-warning-subtle">
                        @if(!empty($tel))
                            <a href="tel:{{$tel}}" class="btn btn-primary rounded-pill w-100 fw-bold fs-14 d-inline-flex align-items-center justify-content-center gap-2 shadow-xs" dir="ltr">
                                <i class="ri-phone-line"></i>
                                <span>{{$tel}}</span>
                            </a>
                        @else
                            <span class="text-muted fs-13">{{__("Phone number available on request")}}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="contact-info-card card border shadow-xs rounded-4 p-3.5 bg-white h-100 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2.5 mb-2.5">
                        <div class="info-icon-wrapper rounded-circle bg-warning-subtle text-primary border border-warning-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                            <i class="ri-map-pin-2-fill fs-18"></i>
                        </div>
                        <h2 class="fw-bold text-main mb-0 fs-15">{{__("Showroom & Atelier")}}</h2>
                    </div>
                    <p class="text-muted fs-13 mb-3 leading-relaxed">
                        @if(!empty($address))
                            {{$address}}
                        @else
                            {{__("Central Atelier & Boutique, dedicated to fine jewelry showcases and private appointments.")}}
                        @endif
                    </p>
                    <div class="mt-auto pt-2 border-top border-light-subtle">
                        <div class="text-muted fs-12 d-flex align-items-center gap-1.5">
                            <i class="ri-time-line text-primary"></i>
                            <span>{{__("Sat - Thu: 09:30 to 20:30")}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="contact-info-card card border shadow-xs rounded-4 p-3.5 bg-white h-100 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2.5 mb-2.5">
                        <div class="info-icon-wrapper rounded-circle bg-warning-subtle text-primary border border-warning-subtle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                            <i class="ri-chat-smile-2-fill fs-18"></i>
                        </div>
                        <h2 class="fw-bold text-main mb-0 fs-15">{{__("Online Messaging")}}</h2>
                    </div>
                    @if(!empty($email))
                        <div class="mb-3 fs-13">
                            <a href="mailto:{{$email}}" class="text-decoration-none text-muted hover-primary d-inline-flex align-items-center gap-1.5">
                                <i class="ri-mail-line text-primary"></i>
                                <span>{{$email}}</span>
                            </a>
                        </div>
                    @endif
                    <div class="mt-auto pt-2 border-top border-light-subtle">
                        @if(!empty($socials))
                            <div class="d-flex align-items-center gap-2">
                                @foreach($socials as $k => $social)
                                    <a href="{{$social}}" target="_blank" rel="noopener noreferrer" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center border shadow-xs text-dark hover-primary" style="width: 36px; height: 36px;" aria-label="{{$k}}">
                                        <i class="ri-{{$k}}-line fs-17"></i>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted fs-13">{{__("Available on all major messengers")}}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-7">
                <div class="contact-form-card card border shadow-xs rounded-4 p-4 bg-white">
                    <div class="mb-4 pb-2 border-bottom border-light-subtle">
                        <h3 class="fw-bold text-main mb-1 fs-18 d-flex align-items-center gap-2">
                            <i class="ri-mail-send-fill text-primary"></i>
                            <span>{{__("Send Us a Message")}}</span>
                        </h3>
                    </div>

                    @include('components.err')

                    @if(session('message'))
                        <div class="alert alert-success rounded-3 d-flex align-items-center gap-2 mb-4">
                            <i class="ri-checkbox-circle-fill fs-20"></i>
                            <span>{{session('message')}}</span>
                        </div>
                    @endif

                    <form class="safe-form" method="post" action="{{route('client.send-contact')}}">
                        <input type="hidden" class="safe-url" data-url="{{route('client.send-contact')}}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-medium text-main mb-1">{{__("Name and lastname")}} <span class="text-danger">*</span></label>
                                <input name="full_name" type="text" class="form-control rounded-3" placeholder="{{__('Your full name')}}" value="{{old('full_name', auth('customer')->user()->name ?? null)}}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-medium text-main mb-1">{{__("Phone")}} <span class="text-danger">*</span></label>
                                <input name="phone" type="tel" class="form-control rounded-3" placeholder="09xxxxxxxxx" value="{{old('phone', auth('customer')->user()->mobile ?? null)}}" dir="ltr" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-medium text-main mb-1">{{__("Email")}}</label>
                                <input name="email" type="email" class="form-control rounded-3" placeholder="name@example.com" value="{{old('email', auth('customer')->user()->email ?? null)}}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-medium text-main mb-1">{{__("Subject")}}</label>
                                <input name="subject" type="text" class="form-control rounded-3" placeholder="{{__('Custom order, price inquiry, general question...')}}" value="{{old('subject')}}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-13 fw-medium text-main mb-1">{{__("Your message...")}} <span class="text-danger">*</span></label>
                                <textarea name="bodya" rows="4" class="form-control rounded-3 fs-13" placeholder="{{__('Please explain your request or question in detail...')}}" required>{{old('bodya')}}</textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2.5 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs">
                                    <i class="ri-send-plane-fill"></i>
                                    <span>{{__("Send Message")}}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="contact-side-card card border-warning-subtle shadow-xs rounded-4 p-4 bg-warning-subtle h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-warning-subtle">
                            <i class="ri-time-fill text-primary fs-20"></i>
                            <h3 class="side-heading fw-bold mb-0 fs-16 text-main">{{__("Atelier Working Hours")}}</h3>
                        </div>

                        <div class="schedule-list mb-4 d-flex flex-column gap-2">
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-warning-subtle fs-13">
                                <span class="text-body">{{__("Saturday - Wednesday")}}</span>
                                <span class="fw-bold text-main">09:30 - 20:30</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-warning-subtle fs-13">
                                <span class="text-body">{{__("Thursday")}}</span>
                                <span class="fw-bold text-main">09:30 - 18:00</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between py-2 fs-13">
                                <span class="text-muted">{{__("Friday & Official Holidays")}}</span>
                                <span class="text-primary fw-semibold">{{__("Online & Web Inquiries Only")}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-white rounded-3 border border-warning-subtle shadow-xs">
                        <div class="d-flex align-items-center gap-2 text-dark fs-12">
                            <i class="ri-shield-check-fill text-primary fs-18"></i>
                            <span>{{__("All messages received through this form are reviewed by our specialists and answered within standard working hours.")}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('client.partials.faq')
    </div>
</div>
@endsection
