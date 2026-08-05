@extends('website.inc.website-layout')

@section('title')
    {{ __('Reset Password') }} - {{config('app.name')}}
@endsection

@section('content')
    @php
        $area = 'login';
        $title = __('Reset Password');
        $subtitle = __('Enter your email to receive a password reset link');
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
                            <i class="ri-key-2-line"></i>
                        </div>
                        <div class="login-header-meta">
                            <h3>{{ __('Reset Password') }}</h3>
                            <p>{{ __('Enter your email to receive a password reset link') }}</p>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success mb-4" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="login-form">
                        @csrf
                        <div class="text-start">
                            @include('components.err')
                        </div>

                        <div id="login-content">
                            <div class="form-group mb-4">
                                <label for="email">
                                    {{ __('Email Address') }}
                                </label>
                                <div class="input-with-icon">
                                    <span class="input-icon"><i class="ri-mail-line"></i></span>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                           name="email" value="{{ old('email') }}" required autocomplete="email"
                                           autofocus placeholder="{{ __('Email Address') }}">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100 login-submit-btn">
                                <i class="ri-send-plane-line me-1"></i>
                                {{ __('Send Password Reset Link') }}
                            </button>

                            <div class="login-card-footer mt-4 pt-3 border-top text-center">
                                <a href="{{ route('login') }}" class="fw-bold text-decoration-none text-primary-theme">
                                    <i class="ri-arrow-right-line me-1"></i>
                                    {{ __('Back to login') }}
                                </a>
                            </div>
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
