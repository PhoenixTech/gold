@php
    $menu = \App\Models\Menu::first();
    $menuItems = ($menu && $menu->items) ? $menu->items : [];
    $socialsRaw = getSettingsGroup('social_');
    $socials = is_array($socialsRaw) ? $socialsRaw : [];
    $tel = getSetting('tel');
    $email = getSetting('email');
    $address = getSetting('address');
    $copyright = getSetting('copyright') ?: ('© ' . date('Y') . ' ' . config('app.name') . '. ' . __('All rights reserved.'));
    $about = getSetting('about');
@endphp

<footer class="TypicalFooter site-footer mt-auto">
    <div class="{{gfx()['container']}}">
        <div class="tf-grid">
            <!-- Brand & About -->
            <div class="tf-col about">
                <a href="{{url('/')}}" class="tf-brand" title="{{config('app.name')}}">
                    <img src="{{asset('upload/images/logo.svg')}}" onerror="this.src='{{asset('assets/default/logo.png')}}'" alt="{{config('app.name')}}">
                </a>
                <div class="tf-about">
                    @if(!empty($about))
                        {!! $about !!}
                    @else
                        <p class="mb-0">
                            {{config('app.name')}} - {{__("Specialized jewelry and gold store offering authentic pieces, live rates, and guaranteed quality.")}}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Quick Links -->
            <div class="tf-col">
                <h4>{{__("Quick links")}}</h4>
                <ul class="tf-links">
                    @if(!empty($menuItems) && count($menuItems) > 0)
                        @foreach($menuItems as $item)
                            <li>
                                <a href="{{$item->webUrl()}}">
                                    {{$item->title}}
                                </a>
                            </li>
                        @endforeach
                    @else
                        <li><a href="{{route('client.welcome')}}">{{__("Home Page")}}</a></li>
                        <li><a href="{{route('client.products')}}">{{__("Products")}}</a></li>
                        <li><a href="{{route('client.posts')}}">{{__("Articles")}}</a></li>
                        <li><a href="{{route('client.contact')}}">{{__("Contact us")}}</a></li>
                    @endif
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="tf-col">
                <h4>{{__("Contact us")}}</h4>
                <ul class="tf-contact">
                    @if(!empty($tel))
                        <li>
                            <i class="ri-phone-line"></i>
                            <a dir="ltr" href="tel:{{$tel}}">{{$tel}}</a>
                        </li>
                    @endif
                    @if(!empty($email))
                        <li>
                            <i class="ri-mail-line"></i>
                            <a href="mailto:{{$email}}">{{$email}}</a>
                        </li>
                    @endif
                    @if(!empty($address))
                        <li>
                            <i class="ri-map-pin-line"></i>
                            <span>{{$address}}</span>
                        </li>
                    @endif
                </ul>
            </div>

            <!-- Social Links & Follow -->
            <div class="tf-col">
                <h4>{{__("Follow us")}}</h4>
                @if(!empty($socials))
                    <ul class="tf-social">
                        @foreach($socials as $k => $social)
                            <li>
                                <a href="{{$social}}" target="_blank" rel="noopener noreferrer" aria-label="{{$k}}">
                                    <i class="ri-{{$k}}-line"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p class="tf-social-hint mt-3">
                    {{__("Stay connected with us on social media for the latest collections and offers.")}}
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom Copyright -->
    <div class="tf-bottom">
        <div class="{{gfx()['container']}}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>{{$copyright}}</div>
                <div class="tf-bottom-links d-flex align-items-center gap-3">
                    <a href="{{route('client.products')}}">{{__("Products")}}</a>
                    <a href="{{route('client.posts')}}">{{__("Articles")}}</a>
                    <a href="{{route('client.contact')}}">{{__("Contact us")}}</a>
                </div>
            </div>
        </div>
    </div>
</footer>
