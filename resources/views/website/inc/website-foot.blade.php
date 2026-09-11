</div>
@yield('custom-foot')
<input type="hidden" id="api-display-url" value="{{route('v1.visitor.display')}}">
<input type="hidden" id="api-fav-toggle" value="{{ url('/product/fav/toggle') }}">
<input type="hidden" id="api-bookmark-toggle" value="{{ url('/product/bookmark/toggle') }}">
<input type="hidden" id="api-compare-toggle" value="{{ url('/product/compare/toggle') }}">


@if(session()->has('message'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.$toast?.success(@json(session('message')));
        }, 300);
    });
</script>
@endif

@if(session()->has('error'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.$toast?.error(@json(session('error')));
        }, 300);
    });
</script>
@endif

</body>
</html>
