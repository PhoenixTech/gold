@php
    $hiddenAreas = ['customer', 'login', 'register', 'card', 'index'];
    $shouldShow = isset($showParallaxHeader)
        ? (bool)$showParallaxHeader
        : (isset($area) ? !in_array($area, $hiddenAreas) : true);
@endphp

@if($shouldShow)
<header class='SimpleHeader live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <h3 class="mb-1">
            {{$title}}
        </h3>
        <h2 class="fs-6 opacity-75 mb-0 fw-normal">
            {{$subtitle}}
        </h2>
    </div>
</header>
@endif
