@if(langIsRTL(config('app.locale')))
    <link rel="stylesheet" href="{{asset('assets/vendor/bootstrap/dist/css/bootstrap.rtl.min.css')}}">
@else
    <link rel="stylesheet" href="{{asset('assets/vendor/bootstrap/dist/css/bootstrap.min.css')}}">
@endif

@vite(['resources/sass/app.scss', 'resources/js/app.js'])

