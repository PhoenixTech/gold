<section class="BottomBar live-setting" data-live="{{$data->area_name.'_'.$data->part}}" >
    <ul>
        @foreach(getSettingsGroup('social_')??[] as $k => $social)
            <li>
                <a href="{{$social}}">
                    <i class="ri-{{$k}}-line"></i>
                </a>
            </li>
        @endforeach
    </ul>
</section>
