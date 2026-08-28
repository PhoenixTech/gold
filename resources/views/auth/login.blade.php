@extends('website.inc.website-layout')

@section('title')
    {{ __('Login') }} - {{config('app.name')}}
@endsection

@section('content')
<div class="auth-login-view py-5 bg-light-subtle min-vh-75 d-flex align-items-center">
    <div class="{{gfx()['container']}}">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle mb-3 shadow-xs" style="width: 58px; height: 58px;">
                            <i class="ri-shield-user-line fs-24"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1 fs-20">{{ __('Management Portal') }}</h4>
                        <p class="text-muted fs-13 mb-0">{{ __('Sign in with your staff or administrator credentials') }}</p>
                    </div>

                    @if(config('app.demo'))
                        <div class="alert alert-warning mb-4 fs-12 rounded-3 border">
                            <strong>{{__("DEMO VERSION")}}</strong>
                            <hr class="my-1.5">
                            <span>{{__("Default admin email is :E1 (developer) or :E2 (admin) and default password is: :P",["E1" => 'developer@example.com','E2' => 'admin@example.com','P' => 'password' ])}}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        @csrf
                        @include('components.err')

                        <div class="form-group mb-3">
                            <label for="email" class="form-label fw-semibold fs-13">
                                {{ __('Email Address') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-mail-line"></i></span>
                                <input id="email" type="email" class="form-control border-start-0 ps-2 @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email"
                                       autofocus placeholder="admin@example.com">
                            </div>
                            @error('email')
                                <span class="invalid-feedback d-block mt-1" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="password" class="form-label fw-semibold fs-13 mb-0">
                                    {{ __('Password') }} <span class="text-danger">*</span>
                                </label>
                                @if (Route::has('password.request'))
                                    <a class="text-decoration-none fs-12 text-primary hover-underline" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-lock-2-line"></i></span>
                                <input id="password" type="password"
                                       class="form-control border-start-0 ps-2 @error('password') is-invalid @enderror" name="password"
                                       required autocomplete="current-password" placeholder="••••••••">
                            </div>
                            @error('password')
                                <span class="invalid-feedback d-block mt-1" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input"
                                       {{ old('remember', true) ? 'checked' : '' }} name="remember"
                                       type="checkbox" role="switch" id="remember">
                                <label class="form-check-label ms-1 fs-13 text-muted" for="remember">{{ __('Remember Me') }}</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold fs-15 shadow-sm">
                            <i class="ri-login-box-line me-1"></i>
                            <span>{{ __('Login') }}</span>
                        </button>

                        @if(app()->environment('local'))
                            <hr class="my-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('quick-login.admin') }}"
                                       class="btn btn-outline-success btn-sm w-100 rounded-pill py-2 fw-semibold">
                                        <i class="ri-flashlight-line me-1"></i>
                                        <span>{{ __('Admin Login') }}</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('quick-login.customer') }}"
                                       class="btn btn-outline-info btn-sm w-100 rounded-pill py-2 fw-semibold">
                                        <i class="ri-user-line me-1"></i>
                                        <span>{{ __('Customer Login') }}</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
