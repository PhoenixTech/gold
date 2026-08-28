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
<div class="contact-page-view pb-5">
    <!-- Header Banner -->
    @include('client.partials.parallax-header', [
        'title' => __('Contact us'),
        'subtitle' => __('We are here to help and answer any questions you may have')
    ])

    <div class="{{gfx()['container']}} pt-5">
        <!-- Brand Presentation Section -->
        <div class="mb-5">
            @include('client.partials.brand-intro')
        </div>

        <!-- 3 Contact Channels Cards -->
        <div class="row g-4 mb-5">
            <!-- Phone Support Card -->
            <div class="col-md-4">
                <div class="contact-info-card shadow-sm d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="info-icon-wrapper flex-shrink-0">
                            <i class="ri-phone-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0 fs-16">{{__("Phone Consultation")}}</h5>
                            <span class="text-muted fs-12">{{__("Direct line with jewelry experts")}}</span>
                        </div>
                    </div>
                    <p class="text-muted fs-13 mb-3 leading-relaxed">
                        {{__("For instant price inquiries, order assistance, or custom design questions, feel free to call us.")}}
                    </p>
                    <div class="mt-auto pt-2 border-top">
                        @if(!empty($tel))
                            <a href="tel:{{$tel}}" class="btn btn-outline-primary rounded-pill w-100 fw-bold fs-14 d-inline-flex align-items-center justify-content-center gap-2" dir="ltr">
                                <i class="ri-phone-line"></i>
                                <span>{{$tel}}</span>
                            </a>
                        @else
                            <span class="text-muted fs-13">{{__("Phone number available on request")}}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Showroom / Location Card -->
            <div class="col-md-4">
                <div class="contact-info-card shadow-sm d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="info-icon-wrapper flex-shrink-0">
                            <i class="ri-map-pin-2-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0 fs-16">{{__("Showroom & Atelier")}}</h5>
                            <span class="text-muted fs-12">{{__("In-person showroom visits")}}</span>
                        </div>
                    </div>
                    <p class="text-muted fs-13 mb-3 leading-relaxed">
                        @if(!empty($address))
                            {{$address}}
                        @else
                            {{__("Central Atelier & Boutique, dedicated to fine jewelry showcases and private appointments.")}}
                        @endif
                    </p>
                    <div class="mt-auto pt-2 border-top">
                        <div class="text-muted fs-12 d-flex align-items-center gap-1.5">
                            <i class="ri-time-line text-primary"></i>
                            <span>{{__("Sat - Thu: 09:30 to 20:30")}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Online & Social Messaging Card -->
            <div class="col-md-4">
                <div class="contact-info-card shadow-sm d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="info-icon-wrapper flex-shrink-0">
                            <i class="ri-chat-smile-2-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0 fs-16">{{__("Online Messaging")}}</h5>
                            <span class="text-muted fs-12">{{__("Instant chat & social channels")}}</span>
                        </div>
                    </div>
                    <p class="text-muted fs-13 mb-3 leading-relaxed">
                        @if(!empty($email))
                            <span class="d-block mb-2">
                                <i class="ri-mail-line text-primary me-1"></i>
                                <a href="mailto:{{$email}}" class="text-decoration-none text-muted hover-primary">{{$email}}</a>
                            </span>
                        @endif
                        {{__("Reach us on social media for catalogs, live rates, and private consultations.")}}
                    </p>
                    <div class="mt-auto pt-2 border-top">
                        @if(!empty($socials))
                            <div class="d-flex align-items-center gap-2">
                                @foreach($socials as $k => $social)
                                    <a href="{{$social}}" target="_blank" rel="noopener noreferrer" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center shadow-xs text-dark hover-primary" style="width: 38px; height: 38px;" aria-label="{{$k}}">
                                        <i class="ri-{{$k}}-line fs-18"></i>
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

        <!-- Form and Working Schedule Section -->
        <div class="row g-4 mb-5">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <div>
                            <h4 class="fw-bold text-dark mb-1 fs-18 d-flex align-items-center gap-2">
                                <i class="ri-mail-send-line text-primary"></i>
                                <span>{{__("Send Us a Message")}}</span>
                            </h4>
                            <p class="text-muted fs-13 mb-0">{{__("Fill out the form below and we will get back to you promptly")}}</p>
                        </div>
                    </div>

                    @include('components.err')

                    @if(session('message'))
                        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4">
                            <i class="ri-checkbox-circle-fill fs-20"></i>
                            <span>{{session('message')}}</span>
                        </div>
                    @endif

                    <form class="safe-form" method="post" action="{{route('client.send-contact')}}">
                        <input type="hidden" class="safe-url" data-url="{{route('client.send-contact')}}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-semibold text-dark">{{__("Name and lastname")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light-subtle border-end-0"><i class="ri-user-line text-muted"></i></span>
                                    <input name="full_name" type="text" class="form-control rounded-end-3" placeholder="{{__('Your full name')}}" value="{{old('full_name', auth('customer')->user()->name ?? null)}}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-semibold text-dark">{{__("Phone")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light-subtle border-end-0"><i class="ri-smartphone-line text-muted"></i></span>
                                    <input name="phone" type="tel" class="form-control rounded-end-3" placeholder="09xxxxxxxxx" value="{{old('phone', auth('customer')->user()->mobile ?? null)}}" dir="ltr" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-semibold text-dark">{{__("Email")}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light-subtle border-end-0"><i class="ri-mail-line text-muted"></i></span>
                                    <input name="email" type="email" class="form-control rounded-end-3" placeholder="name@example.com" value="{{old('email', auth('customer')->user()->email ?? null)}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-13 fw-semibold text-dark">{{__("Subject")}}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light-subtle border-end-0"><i class="ri-price-tag-3-line text-muted"></i></span>
                                    <input name="subject" type="text" class="form-control rounded-end-3" placeholder="{{__('Custom order, price inquiry, general question...')}}" value="{{old('subject')}}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-13 fw-semibold text-dark">{{__("Your message...")}} <span class="text-danger">*</span></label>
                                <textarea name="bodya" rows="5" class="form-control" placeholder="{{__('Please explain your request or question in detail...')}}" required>{{old('bodya')}}</textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                                    <i class="ri-send-plane-fill"></i>
                                    <span>{{__("Send Message")}}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Showroom Hours & Promise Card -->
            <div class="col-lg-5">
                <div class="contact-side-card h-100 d-flex flex-column justify-content-between shadow-sm">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="ri-time-line text-warning fs-22"></i>
                            <h5 class="side-heading mb-0">{{__("Atelier Working Hours")}}</h5>
                        </div>
                        <p class="text-white-70 fs-13 leading-relaxed mb-4">
                            {{__("Our customer care team and jewelry consultants are active during the following hours to respond to your calls and inquiries:")}}
                        </p>

                        <div class="schedule-list mb-4">
                            <div class="schedule-row">
                                <span>{{__("Saturday - Wednesday")}}</span>
                                <span class="fw-semibold text-white">09:30 - 20:30</span>
                            </div>
                            <div class="schedule-row">
                                <span>{{__("Thursday")}}</span>
                                <span class="fw-semibold text-white">09:30 - 18:00</span>
                            </div>
                            <div class="schedule-row">
                                <span>{{__("Friday & Official Holidays")}}</span>
                                <span class="text-warning fw-semibold">{{__("Online & Web Inquiries Only")}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-white-10 rounded-4 border border-white-15 mt-3">
                        <div class="d-flex align-items-start gap-2.5">
                            <i class="ri-customer-service-2-line text-warning fs-24 flex-shrink-0 mt-0.5"></i>
                            <div>
                                <h6 class="fw-bold text-white fs-14 mb-1">{{__("Fast Response Commitment")}}</h6>
                                <p class="text-white-70 fs-12 mb-0 leading-relaxed">
                                    {{__("All messages received through this form are reviewed by our specialists and answered within standard working hours.")}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        @include('client.partials.faq')
    </div>
</div>
@endsection
