@php
    $hiddenAreas = ['customer', 'login', 'register', 'card', 'index'];
    $shouldShow = isset($showParallaxHeader)
        ? (bool)$showParallaxHeader
        : (isset($area) ? !in_array($area, $hiddenAreas) : true);
@endphp

@if($shouldShow)
<header class='ParallaxHeader live-setting' data-live="{{$data->area_name.'_'.$data->part}}" style="background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url('{{$bg??asset('upload/images/'.$part->area_name . '.' . $part->part.'.jpg')}}')">
    <div class="{{gfx()['container']}} text-center text-white">
        @if(!empty($title))
            <h1 class="mb-1 text-white fw-bold">
                {{$title}}
            </h1>
        @endif
        @if(!empty(trim($subtitle)))
            <p class="fs-6 text-white-50 mb-0 fw-normal">
                {{$subtitle}}
            </p>
        @endif
    </div>
</header>
@endif
