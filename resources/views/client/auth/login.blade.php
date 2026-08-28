@extends('website.inc.website-layout')

@section('title')
    {{__("Sign-in")}} - {{config('app.name')}}
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
                            {{__("VIP Customer Club & Order Portal")}}
                        </h3>
                        <p class="text-white-70 fs-14 leading-relaxed mb-4">
                            {{__("Sign in to access your official invoices, track insured shipments, view saved pieces, and receive personalized jewelry consultation.")}}
                        </p>

                        <!-- Benefits List -->
                        <div class="auth-benefits-list d-flex flex-column gap-3 mb-4">
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-shield-check-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("18K Assay & Official Invoice")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Every order verified with authentic serial code")}}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-truck-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("Track Insured Shipments")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Live dispatch and PIN verification status")}}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-start gap-2.5">
                                <div class="benefit-icon-box rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="ri-heart-3-line fs-16"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold text-white fs-14 mb-0.5">{{__("Saved Favorites & Wishlist")}}</h6>
                                    <span class="text-white-70 fs-12">{{__("Quick access to your curated jewelry collection")}}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Guarantee Footer -->
                    <div class="pt-3 border-top border-white-15 d-flex align-items-center justify-content-between text-white-70 fs-12">
                        <span class="d-flex align-items-center gap-1">
                            <i class="ri-lock-line text-warning"></i>
                            <span>{{__("Secure 256-Bit SSL")}}</span>
                        </span>
                        <span>{{__("24/7 Dedicated Support")}}</span>
                    </div>
                </div>

                <!-- Auth Form Column -->
                <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                    <div class="mb-4">
                        <h4 class="fw-bold text-dark fs-22 mb-1">
                            {{$title ?? __("Sign in to your account")}}
                        </h4>
                        <p class="text-muted fs-14 mb-0">
                            {{$subtitle ?? __('Please enter your account details to continue')}}
                        </p>
                    </div>

                    @include('components.err')

                    <form @if(!config('app.sms.sign')) action="{{route('client.sign-in-do')}}" @endif id="login-form" method="post">
                        @csrf

                        @if(!config('app.sms.sign'))
                            <div class="mb-3">
                                <label for="login-email" class="form-label fs-13 fw-semibold">
                                    {{__("Email")}} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-mail-line"></i></span>
                                    <input type="email" id="login-email" class="form-control border-start-0 ps-2" placeholder="{{__("Email")}}" name="email" value="{{old('email')}}" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label for="login-password" class="form-label fs-13 fw-semibold mb-0">
                                        {{__("Password")}} <span class="text-danger">*</span>
                                    </label>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-lock-2-line"></i></span>
                                    <input type="password" id="login-password" class="form-control border-start-0 ps-2" placeholder="{{__('Password')}}" name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold fs-15 shadow-sm mt-3">
                                <i class="ri-login-box-line me-1"></i>
                                <span>{{__("Sign-in")}}</span>
                            </button>

                            <div class="mt-4 pt-3 border-top text-center">
                                <span class="text-muted fs-14">{{__("Don't have an account?")}}</span>
                                <a href="{{route('client.sign-up')}}" class="ms-1 fw-bold text-decoration-none text-primary hover-underline">
                                    {{__("Create Account")}} &larr;
                                </a>
                            </div>
                        @else
                            <div class="mb-3">
                                <label for="tel" class="form-label fs-13 fw-semibold">
                                    {{__("Mobile")}} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-phone-line"></i></span>
                                    <input type="tel" maxlength="12" class="form-control border-start-0 ps-2 text-center"
                                           id="tel" placeholder="{{__("09xxxxxxxx")}}" dir="ltr" autofocus required>
                                </div>
                            </div>

                            <div class="not-send mb-3">
                                <label for="auth" class="form-label fs-13 fw-semibold">
                                    {{__("Auth code")}} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-key-2-line"></i></span>
                                    <input type="tel" maxlength="5" minlength="5" id="auth" class="form-control border-start-0 ps-2 text-center fs-18 fw-bold letter-spacing-2"
                                           placeholder="xxxxx" dir="ltr">
                                </div>

                                <button type="button" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold fs-15 shadow-sm mt-3"
                                        id="send-auth-check" data-route="{{route('client.check-auth')}}"
                                        data-profile="{{route('client.profile')}}">
                                    <i class="ri-checkbox-circle-line me-1"></i>
                                    <span>{{__("Check authenticate code")}}</span>
                                </button>
                            </div>

                            <div class="sent">
                                <button type="button" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold fs-15 shadow-sm mt-2"
                                        id="send-auth-code" data-route="{{route('client.send-sms')}}">
                                    <i class="ri-send-plane-line me-1"></i>
                                    <span>{{__("Send authenticate code")}}</span>
                                </button>
                            </div>
                        @endif
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
