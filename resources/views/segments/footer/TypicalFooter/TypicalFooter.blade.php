@cache('typical_footer_'.$data->area_name.'_'.$data->part.cacheNumber(), 90)
<footer class='TypicalFooter live-setting' data-live="{{$data->area_name.'_'.$data->part}}"
        style="--xshop-tf-bg: {{getSetting($data->area_name.'_'.$data->part.'_bg')}};
        --xshop-tf-accent: {{getSetting($data->area_name.'_'.$data->part.'_accent')}}">
    <div class="{{gfx()['container']}}">
        <div class="tf-grid">
            <div class="tf-col about">
                <a href="{{url('/')}}" class="tf-brand">
                    <img src="{{asset('upload/images/logo.svg')}}" alt="{{config('app.name')}}">
                </a>
                <div class="tf-about">
                    {!! getSetting($data->area_name.'_'.$data->part.'_about') !!}
                </div>
            </div>
            <div class="tf-col">
                <h4>
                    {{__("Quick links")}}
                </h4>
                <ul class="tf-links">
                    @foreach(getMenuBySettingItems($data->area_name.'_'.$data->part.'_menu') as $item)
                        <li>
                            <a href="{{$item->webUrl()}}">
                                {{$item->title}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="tf-col">
                <h4>
                    {{__("Contact us")}}
                </h4>
                <ul class="tf-contact">
                    <li>
                        <i class="ri-phone-line"></i>
                        <a dir="ltr" href="tel:{{getSetting('tel')}}">{{getSetting('tel')}}</a>
                    </li>
                    <li>
                        <i class="ri-mail-line"></i>
                        <a href="mailto:{{getSetting('email')}}">{{getSetting('email')}}</a>
                    </li>
                    <li>
                        <i class="ri-map-pin-line"></i>
                        <span>{{getSetting($data->area_name.'_'.$data->part.'_address')}}</span>
                    </li>
                </ul>
            </div>
            <div class="tf-col">
                <h4>
                    {{__("Follow us")}}
                </h4>
                <ul class="tf-social">
                    @foreach(getSettingsGroup('social_')??[] as $k => $social)
                        <li>
                            <a href="{{$social}}" aria-label="{{$k}}">
                                <i class="ri-{{$k}}-line"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="tf-bottom">
        <div class="{{gfx()['container']}}">
            {{getSetting('copyright')}}
        </div>
    </div>
</footer>
@endcache
