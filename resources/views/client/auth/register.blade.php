@extends('website.inc.website-layout')

@section('title')
    {{__("Sign-up")}} - {{config('app.name')}}
@endsection

@section('content')
<section class="auth-page-section py-5 bg-light-subtle">
    <div class="{{gfx()['container']}}">
        <div class="auth-split-wrapper card border-0 shadow-lg rounded-4 overflow-hidden mx-auto bg-white" style="max-width: 980px;">
            <div class="row g-0">

                <!-- Showcase & Benefits Column -->
                <div class="col-lg-5 auth-showcase-column p-4 p-md-5 d-flex flex-column justify-content-between text-white position-relative overflow-hidden" style="background: linear-gradient(145deg, #1c1917 0%, #292524 100%);">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img src="{{asset('upload/images/logo.svg')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}" height="32" style="filter: brightness(0) invert(1);">
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fs-12 fw-bold">
                                {{config('app.name')}}
                            </span>
                        </div>

                        <h3 class="fw-bold text-white fs-22 mb-3 leading-snug">
                            {{__("Join Our Exclusive Jewelry Club")}}
                        </h3>
                        <p class="text-white-70 fs-14 leading-relaxed mb-4">
                            {{__("Register today to enjoy personalized jewelry craftsmanship, insured delivery to your doorstep, and instant order tracking.")}}
                        </p>

                        <!-- Benefits List -->
                        <div class="auth-benefits-list d-flex flex-column gap-3 mb-4">
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-gift-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("Welcome Gift & Offers")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Exclusive discounts on your initial gold purchase")}}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-shield-star-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("Certified 18K Quality")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Lifetime authenticity guarantee with official invoices")}}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-magic-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("Custom 3D Atelier Orders")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Direct collaboration with our master goldsmiths")}}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Guarantee Footer -->
                    <div class="pt-3 border-top border-white-15 d-flex align-items-center justify-content-between text-white-70 fs-12">
                        <span class="d-flex align-items-center gap-1">
                            <i class="ri-shield-check-line text-warning"></i>
                            <span>{{__("Privacy Protected")}}</span>
                        </span>
                        <span>{{__("Standard 18K Gold")}}</span>
                    </div>
                </div>

                <!-- Auth Register Form Column -->
                <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                    <div class="mb-4">
                        <h4 class="fw-bold text-dark fs-22 mb-1">
                            {{$title ?? __("Create your account")}}
                        </h4>
                        <p class="text-muted fs-14 mb-0">
                            {{__("Fill in your essential details to get started")}}
                        </p>
                    </div>

                    @include('components.err')

                    <form action="/blah" method="post" class="safe-form" id="email-register">
                        @csrf
                        <input type="hidden" class="safe-url" data-url="{{route('client.sign-up-now')}}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fs-13 fw-semibold">{{__("Full Name")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-user-line"></i></span>
                                    <input type="text" id="name" required name="name" value="{{old('name')}}"
                                           placeholder="{{__("Full Name")}}" class="form-control border-start-0 ps-2" autofocus>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="mobile" class="form-label fs-13 fw-semibold">{{__("Mobile")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-smartphone-line"></i></span>
                                    <input type="tel" id="mobile" required name="mobile" value="{{old('mobile')}}"
                                           placeholder="09xxxxxxxxx" class="form-control border-start-0 ps-2" dir="ltr">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label fs-13 fw-semibold">{{__("Email")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-mail-line"></i></span>
                                    <input type="email" id="email" required name="email" value="{{old('email')}}"
                                           placeholder="name@example.com" class="form-control border-start-0 ps-2">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label fs-13 fw-semibold">{{__("Address")}} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted align-items-start pt-2"><i class="ri-map-pin-line"></i></span>
                                    <textarea id="address" required name="address" rows="2"
                                              placeholder="{{__("Full delivery address for insured shipments")}}"
                                              class="form-control border-start-0 ps-2">{{old('address')}}</textarea>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold fs-15 shadow-sm">
                                    <i class="ri-user-add-line me-1"></i>
                                    <span>{{__("Create Account")}}</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-center">
                            <span class="text-muted fs-14">{{__("Already have an account?")}}</span>
                            <a href="{{route('client.sign-in')}}" class="ms-1 fw-bold text-decoration-none text-primary hover-underline">
                                {{__("Sign-in")}} &larr;
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
