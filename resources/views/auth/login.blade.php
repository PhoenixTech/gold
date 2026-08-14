@extends('website.inc.website-layout')

@section('title')
    {{ __('Login') }} - {{config('app.name')}}
@endsection

@section('content')
    @php
        $area = 'login';
        $title = __('Login');
        $subtitle = __('Sign in to your account');
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
                            <i class="ri-user-shared-2-line"></i>
                        </div>
                        <div class="login-header-meta">
                            <h5>{{ __('Login') }}</h5>
                            <p>{{ __('Sign in to your account') }}</p>
                        </div>
                    </div>

                    @if(config('app.demo'))
                        <div class="alert alert-warning mb-4">
                            {{__("DEMO VERSION")}}
                            <hr class="my-2">
                            <small>{{__("Default admin email is :E1 (developer) or :E2 (admin) and default password is: :P",["E1" => 'developer@example.com','E2' => 'admin@example.com','P' => 'password' ])}}</small>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        @csrf
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
                                           name="email" value="{{ old('email') }}" required autocomplete="email"
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
                                           required autocomplete="current-password" placeholder="{{ __('Password') }}">
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input"
                                           {{ old('remember', true) ? 'checked' : '' }} name="remember"
                                           type="checkbox" role="switch" id="remember">
                                    <label class="form-check-label ms-1" for="remember">{{ __('Remember Me') }}</label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a class="btn btn-link text-decoration-none p-0 fs-14" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2 login-submit-btn">
                                <i class="ri-login-box-line me-1"></i>
                                {{ __('Login') }}
                            </button>

                            @if(app()->environment('local'))
                                <hr class="my-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="{{ route('quick-login.admin') }}"
                                           class="btn btn-success btn-sm w-100">
                                            <i class="ri-flashlight-line me-1"></i>
                                            {{ __('Quick login as Developer') }}
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('quick-login.customer') }}"
                                           class="btn btn-info btn-sm text-white w-100">
                                            <i class="ri-user-line me-1"></i>
                                            {{ __('Quick login as Customer') }}
                                        </a>
                                    </div>
                                </div>
                            @endif
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
