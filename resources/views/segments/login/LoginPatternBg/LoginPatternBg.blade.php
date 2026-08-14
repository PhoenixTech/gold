<section id='LoginPatternBg' class='content live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div id="login-container"
         style="background-image: url('{{asset('upload/images/'.$data->area_name.'.'.$data->part.'.jpg')}}')">
        <div class="login-card">
            <div class="login-card-header">
                <div class="login-header-icon">
                    <i class="ri-user-shared-2-line"></i>
                </div>
                <div class="login-header-meta">
                    <h5>{{$title ?? __("Sign-in")}}</h5>
                    <p>{{$subtitle ?? __('Sign in as customer')}}</p>
                </div>
            </div>
            <form @if(!config('app.sms.sign')) action="{{route('client.sign-in-do')}}" @endif id="login-form" method="post">
                @csrf
                <div class="text-start">
                    @include('components.err')
                </div>
                <div id="login-content">
                    @if(!config('app.sms.sign'))
                        <div class="form-group mb-3">
                            <label for="login-email">
                                {{__("Email")}}
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon"><i class="ri-mail-line"></i></span>
                                <input type="email" id="login-email" class="form-control" placeholder="{{__("Email")}}" name="email" value="{{old('email')}}" required>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="login-password">
                                {{__("Password")}}
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon"><i class="ri-lock-2-line"></i></span>
                                <input type="password" id="login-password" class="form-control" placeholder="{{__('Password')}}" name="password" required>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-2 login-submit-btn">
                            <i class="ri-login-box-line me-1"></i>
                            {{__("Sign-in")}}
                        </button>
                    @else
                        <div class="form-group mb-3">
                            <label for="tel">
                                {{__("Mobile")}}
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon"><i class="ri-phone-line"></i></span>
                                <input type="tel" maxlength="12" class="form-control text-center"
                                       id="tel" placeholder="{{__("09xxxxxxxx")}}">
                            </div>
                        </div>
                        <div class="not-send mb-3">
                            <label for="auth">
                                {{__("Auth code")}}
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon"><i class="ri-key-2-line"></i></span>
                                <input type="tel" maxlength="5" minlength="5" id="auth" class="form-control text-center"
                                       placeholder="xxxxx">
                            </div>

                            <button type="button" class="btn btn-primary w-100 mt-3 login-submit-btn"
                                    id="send-auth-check" data-route="{{route('client.check-auth')}}"
                                    data-profile="{{route('client.profile')}}">
                                <i class="ri-checkbox-circle-line me-1"></i>
                                {{__("Check authenticate code")}}
                            </button>
                        </div>
                        <div class="sent">
                            <button type="button" class="btn btn-primary w-100 mt-3 login-submit-btn"
                                    id="send-auth-code" data-route="{{route('client.send-sms')}}">
                                <i class="ri-send-plane-line me-1"></i>
                                {{__("Send authenticate code")}}
                            </button>
                        </div>
                    @endif
                    @if(!config('app.sms.sign'))
                        <div class="login-card-footer mt-4 pt-3 border-top text-center">
                            <span class="text-muted fs-14">{{__("Don't have an account?")}}</span>
                            <a href="{{route('client.sign-up')}}" class="ms-1 fw-bold text-decoration-none text-primary-theme">
                                {{__("Sign-up")}}
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>

