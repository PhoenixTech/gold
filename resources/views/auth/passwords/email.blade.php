@extends('website.inc.website-layout')

@section('title')
    {{ __('Reset Password') }} - {{config('app.name')}}
@endsection

@section('content')
<div class="auth-reset-password-view py-5">
    <div class="{{gfx()['container']}}">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="ri-key-2-line fs-24"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">{{ __('Reset Password') }}</h4>
                        <p class="text-muted fs-14 mb-0">{{ __('Enter your email to receive a password reset link') }}</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success mb-4 fs-14" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="login-form">
                        @csrf
                        @include('components.err')

                        <div class="form-group mb-4">
                            <label for="email" class="form-label fw-semibold fs-14">
                                {{ __('Email Address') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="ri-mail-line text-muted"></i></span>
                                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email"
                                       autofocus placeholder="{{ __('Email Address') }}">
                            </div>
                            @error('email')
                                <span class="invalid-feedback d-block mt-1" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold">
                            <i class="ri-send-plane-line me-1"></i>
                            {{ __('Send Password Reset Link') }}
                        </button>

                        <div class="mt-4 pt-3 border-top text-center">
                            <a href="{{ route('login') }}" class="fw-bold text-decoration-none text-primary fs-14">
                                <i class="ri-arrow-right-line me-1"></i>
                                {{ __('Back to login') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
