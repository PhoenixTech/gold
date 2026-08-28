@include('website.inc.website-head')

@sectionMissing('hide-header')
    @include('client.partials.header')
@endif

<main>
    @yield('content')
</main>

@sectionMissing('hide-footer')
    @include('client.partials.footer')
@endif

@include('website.inc.website-foot')

