@if(!empty($title) || !empty($subtitle))
<header class="ParallaxHeader py-4 mb-4 position-relative" style="{{ !empty($bg) ? "background-image: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url('{$bg}'); background-size: cover; background-position: center;" : 'background-color: var(--xshop-secondary);' }}">
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
