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
                            <i class="ri-lock-password-line fs-24"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">{{ __('Reset Password') }}</h4>
                        <p class="text-muted fs-14 mb-0">{{ __('Set a new password for your account') }}</p>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" id="login-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        @include('components.err')

                        <div class="form-group mb-3">
                            <label for="email" class="form-label fw-semibold fs-14">
                                {{ __('Email Address') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="ri-mail-line text-muted"></i></span>
                                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror"
                                       name="email" value="{{ $email ?? old('email') }}" required autocomplete="email"
                                       autofocus placeholder="{{ __('Email Address') }}">
                            </div>
                            @error('email')
                                <span class="invalid-feedback d-block mt-1" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="password" class="form-label fw-semibold fs-14">
                                {{ __('Password') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="ri-lock-2-line text-muted"></i></span>
                                <input id="password" type="password"
                                       class="form-control border-start-0 @error('password') is-invalid @enderror" name="password"
                                       required autocomplete="new-password" placeholder="{{ __('Password') }}">
                            </div>
                            @error('password')
                                <span class="invalid-feedback d-block mt-1" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="password-confirm" class="form-label fw-semibold fs-14">
                                {{ __('Confirm Password') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="ri-lock-check-line text-muted"></i></span>
                                <input id="password-confirm" type="password" class="form-control border-start-0"
                                       name="password_confirmation" required autocomplete="new-password"
                                       placeholder="{{ __('Confirm Password') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold">
                            <i class="ri-checkbox-circle-line me-1"></i>
                            {{ __('Reset Password') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
