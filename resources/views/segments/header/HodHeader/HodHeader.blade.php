@php
    $hiddenAreas = ['customer', 'login', 'register', 'card', 'index'];
    $shouldShow = isset($showParallaxHeader)
        ? (bool)$showParallaxHeader
        : (isset($area) ? !in_array($area, $hiddenAreas) : true);
@endphp

@if($shouldShow)
<section class='HodHeader live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="row align-items-center">
            <div class="col-md">
                <h3 class="mb-1">
                    {{$title}}
                </h3>
                <h2 class="fs-6 opacity-75 mb-0 fw-normal">
                    {{$subtitle}}
                </h2>
            </div>
            <div class="col-md-3 text-end">
                <div id="hod-logo">
                    <img src="{{asset('upload/images/logo.png')}}" alt="logo">
                </div>
            </div>
        </div>
    </div>
</section>
@endif
