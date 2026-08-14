<section class='SimpleRegister live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="register-card">
            <div class="register-card-header">
                <div class="register-header-icon">
                    <i class="ri-user-add-line"></i>
                </div>
                <div class="register-header-meta">
                    <h5>{{$title ?? __("Sign-up")}}</h5>
                    <p>{{__("Fill essential fields to create your account")}}</p>
                </div>
            </div>
            <form action="/blah" method="post" class="safe-form" id="email-register">
                @csrf
                @include('components.err')
                <input type="hidden" class="safe-url" data-url="{{route('client.sign-up-now')}}">
                <div class="register-content">
                    <div class="form-group mb-3">
                        <label for="name">{{__("Name")}}</label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="ri-user-line"></i></span>
                            <input type="text" id="name" required name="name" value="{{old('name')}}"
                                   placeholder="{{__("Name")}}" class="form-control">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="mobile">{{__("Mobile")}}</label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="ri-smartphone-line"></i></span>
                            <input type="tel" id="mobile" required name="mobile" value="{{old('mobile')}}"
                                   placeholder="09xxxxxxxxx" class="form-control" dir="ltr">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="email">{{__("Email")}}</label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="ri-mail-line"></i></span>
                            <input type="email" id="email" required name="email" value="{{old('email')}}"
                                   placeholder="{{__("Email")}}" class="form-control">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="address">{{__("Address")}}</label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="ri-map-pin-line"></i></span>
                            <textarea id="address" required name="address" rows="3"
                                      placeholder="{{__("Full delivery address")}}"
                                      class="form-control">{{old('address')}}</textarea>
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
