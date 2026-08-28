@if(!empty($title) || !empty($subtitle))
<header class="ParallaxHeader py-5 mb-4 position-relative" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('{{$bg ?? asset('assets/default/header-bg.jpg')}}'); background-size: cover; background-position: center;">
    <div class="{{gfx()['container']}} text-center text-white py-3">
        @if(!empty($title))
            <h1 class="mb-2 text-white fw-bold fs-2">
                {{$title}}
            </h1>
        @endif
        @if(!empty(trim($subtitle ?? '')))
            <p class="fs-6 text-white-75 mb-0 fw-normal mx-auto" style="max-width: 650px;">
                {{$subtitle}}
            </p>
        @endif
    </div>
</header>
@endif
