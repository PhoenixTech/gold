<section class='SimpleRegister live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="register-card">
            <div class="register-card-header">
                <div class="register-header-icon">
                    <i class="ri-user-add-line"></i>
                </div>
                <div class="register-header-meta">
                    <h3>{{$title ?? __("Sign-up")}}</h3>
                    <p>{{__("Register or Reset password")}}</p>
                </div>
            </div>
            <form action="/blah" method="post" class="safe-form" id="email-register">
                @csrf
                @include('components.err')
                <input type="hidden" class="safe-url" data-url="{{route('client.sign-up-now')}}">
                <div class="register-content">
                    <div class="form-group mb-3">
                        <label for="email">
                            {{__("Email")}}
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="ri-mail-line"></i></span>
                            <input type="email" id="email" required name="email" value="{{old('email')}}" placeholder="{{__("Email")}}" class="form-control">
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 mt-2 register-submit-btn">
                        <i class="ri-user-add-line me-1"></i>
                        {{__("Sign-up")}}
                    </button>
                    <div class="register-card-footer mt-4 pt-3 border-top text-center">
                        <span class="text-muted fs-14">{{__("Already have an account?")}}</span>
                        <a href="{{route('client.sign-in')}}" class="ms-1 fw-bold text-decoration-none text-primary-theme">
                            {{__("Sign-in")}}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

