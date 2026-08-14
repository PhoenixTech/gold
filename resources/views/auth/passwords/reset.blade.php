@extends('website.inc.website-layout')

@section('title')
    {{ __('Reset Password') }} - {{config('app.name')}}
@endsection

@section('content')
    @php
        $area = 'login';
        $title = __('Reset Password');
        $subtitle = __('Set a new password for your account');
    @endphp
    <main>
        @php($headerParts = getParts('defaultHeader'))
        @foreach($headerParts as $part)
            @php($p = $part->getBladeWithData())
            @include($p['blade'],['data' => $p['data']])
        @endforeach

        <section id="LoginPatternBg" class="content">
            <div id="login-container">
                <div class="login-card">
                    <div class="login-card-header">
                        <div class="login-header-icon">
                            <i class="ri-lock-password-line"></i>
                        </div>
                        <div class="login-header-meta">
                            <h5>{{ __('Reset Password') }}</h5>
                            <p>{{ __('Set a new password for your account') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" id="login-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="text-start">
                            @include('components.err')
                        </div>

                        <div id="login-content">
                            <div class="form-group mb-3">
                                <label for="email">
                                    {{ __('Email Address') }}
                                </label>
                                <div class="input-with-icon">
                                    <span class="input-icon"><i class="ri-mail-line"></i></span>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                           name="email" value="{{ $email ?? old('email') }}" required autocomplete="email"
                                           autofocus placeholder="{{ __('Email Address') }}">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">
                                    {{ __('Password') }}
                                </label>
                                <div class="input-with-icon">
                                    <span class="input-icon"><i class="ri-lock-2-line"></i></span>
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror" name="password"
                                           required autocomplete="new-password" placeholder="{{ __('Password') }}">
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="password-confirm">
                                    {{ __('Confirm Password') }}
                                </label>
                                <div class="input-with-icon">
                                    <span class="input-icon"><i class="ri-lock-check-line"></i></span>
                                    <input id="password-confirm" type="password" class="form-control"
                                           name="password_confirmation" required autocomplete="new-password"
                                           placeholder="{{ __('Confirm Password') }}">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2 login-submit-btn">
                                <i class="ri-checkbox-circle-line me-1"></i>
                                {{ __('Reset Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        @php($footerParts = getParts('defaultFooter'))
        @foreach($footerParts as $part)
            @php($p = $part->getBladeWithData())
            @include($p['blade'],['data' => $p['data']])
        @endforeach
    </main>
@endsection
