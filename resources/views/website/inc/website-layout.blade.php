@include('website.inc.website-head')

@sectionMissing('hide-header')
    @if(View::hasSection('use-legacy-header'))
        @include('client.partials.header')
    @else
        @include('client.partials.zar-menu')
    @endif
@endif

<main>
    @yield('content')
</main>

@sectionMissing('hide-footer')
    @include('client.partials.footer')
@endif

@include('website.inc.website-foot')

