@extends('website.inc.website-layout')

@section('title')
    {{ __("Sign-in") }} - {{ config('app.name') }}
@endsection

@section('content')
<section class="auth-page-section py-5 bg-light-subtle">
    <div class="{{ gfx()['container'] }}">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                <div class="auth-card card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <a href="{{ route('home') }}" class="d-inline-block mb-3">
                            <img src="{{ asset('upload/images/logo.svg') }}" alt="{{ config('app.name') }}" width="130" height="36">
                        </a>
                        <h1 class="fw-bold fs-20 text-dark mb-1">
                            {{ $title ?? __('Sign in to your account') }}
                        </h1>
                        <p class="text-muted fs-13 mb-0">
                            {{ $subtitle ?? __('Please enter your account details to continue') }}
                        </p>
                    </div>

                    @include('components.err')

                    <form @if(!config('app.sms.sign')) action="{{ route('client.sign-in-do') }}" @endif id="login-form" method="post" data-msg-invalid-mobile="{{ __('Mobile number format is invalid') }}" data-msg-invalid-code="{{ __('Auth code is invalid') }}">
                        @csrf

                        @if(!config('app.sms.sign'))
                            <div class="mb-3">
                                <label for="login-email" class="form-label fs-13 fw-semibold">
                                    {{ __("Email") }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-mail-line" aria-hidden="true"></i></span>
                                    <input type="email" id="login-email" class="form-control ps-2" placeholder="{{ __('Email') }}" name="email" value="{{ old('email') }}" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="login-password" class="form-label fs-13 fw-semibold mb-1">
                                    {{ __("Password") }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-lock-2-line" aria-hidden="true"></i></span>
                                    <input type="password" id="login-password" class="form-control ps-2" placeholder="{{ __('Password') }}" name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold fs-15 shadow-sm mt-3 rounded-3">
                                <i class="ri-login-box-line me-1" aria-hidden="true"></i>
                                <span>{{ __("Sign-in") }}</span>
                            </button>

                            <div class="mt-4 pt-3 border-top text-center fs-13">
                                <span class="text-muted">{{ __("Don't have an account?") }}</span>
                                <a href="{{ route('client.sign-up') }}" class="ms-1 fw-bold text-decoration-none text-primary">
                                    {{ __("Create Account") }}
                                </a>
                            </div>
                        @else
                            <div class="mb-3">
                                <label for="tel" class="form-label fs-13 fw-semibold">
                                    {{ __("Mobile") }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-phone-line" aria-hidden="true"></i></span>
                                    <input type="tel" maxlength="12" class="form-control ps-2 text-center"
                                           id="tel" placeholder="{{ __('09xxxxxxxx') }}" dir="ltr" autofocus required>
                                </div>
                            </div>

                            <div class="not-send mb-3">
                                <label for="auth" class="form-label fs-13 fw-semibold">
                                    {{ __("Auth code") }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-key-2-line" aria-hidden="true"></i></span>
                                    <input type="tel" maxlength="6" minlength="5" id="auth" class="form-control ps-2 text-center fs-18 fw-bold"
                                           placeholder="------" dir="ltr">
                                </div>

                                <button type="button" class="btn btn-primary w-100 py-2.5 fw-bold fs-15 shadow-sm mt-3 rounded-3"
                                        id="send-auth-check" data-route="{{ route('client.check-auth') }}"
                                        data-profile="{{ route('client.profile') }}">
                                    <i class="ri-checkbox-circle-line me-1" aria-hidden="true"></i>
                                    <span>{{ __("Check authenticate code") }}</span>
                                </button>
                            </div>

                            <div class="sent">
                                <button type="button" class="btn btn-primary w-100 py-2.5 fw-bold fs-15 shadow-sm mt-2 rounded-3"
                                        id="send-auth-code" data-route="{{ route('client.send-sms') }}">
                                    <i class="ri-send-plane-line me-1" aria-hidden="true"></i>
                                    <span>{{ __("Send authenticate code") }}</span>
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
