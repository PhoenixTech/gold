@extends('website.inc.website-layout')

@section('hide-header', true)
@section('hide-footer', true)

@section('content')
<section id="AvisaCustomer">
    <div class="{{ gfx()['container'] ?? 'container' }}">
        <div class="avisa-container-mobile">
            @yield('customer-content')
        </div>
    </div>

    @include('client.customer.partials.bottom-nav')
</section>
@endsection
