<nav class="ZarMenu live-setting" data-live="{{$data->area_name.'_'.$data->part}}">
    <ul class="main-zar-btns">
        <li class="btn-main">
            <a href="{{route('client.welcome')}}">
                <i class="ri-home-7-line"></i>
            </a>
        </li>
        <li class="menu-btn  desktop-menu">
            <span>
                <i class="ri-line-chart-line mx-2"></i>
                {{__("Gold price")}}: {{number_format( ((int) getSetting('gold')) )}} {{config('app.currency.symbol')}}
            </span>
        </li>
        <li class="menu-btn  desktop-menu">
            <a href="{{route('client.profile')}}">
                <i class="ri-trophy-line mx-2"></i>
                {{__("Credit")}}
            </a>
        </li>
        <li class="menu-btn desktop-menu">
            <a href="{{route('client.profile')}}">

                <i class="ri-account-circle-line mx-2"></i>
                @if(auth('customer')->check())
                    {{auth('customer')->user()->name}}
                @else
                    {{__("Guest")}}
                @endif
            </a>
        </li>
        <li class="menu-btn" id="open-zar-2">
            <span>
                <i class="ri-search-line mx-2"></i>
            </span>
        </li>
        <li class="menu-btn">
            <a href="{{route('client.card')}}">
                <i class="ri-shopping-bag-4-line mx-2"></i>
            </a>
        </li>

        <li class="menu-btn" id="open-zar-1">
            <span>
                <i class="ri-menu-line me-4 ms-2"></i>
            </span>
        </li>
    </ul>
    <ul class="tab-menu">
        <li class="menu-btn">
            <a href="{{route('client.profile')}}">

                <i class="ri-account-circle-line mx-2"></i>
                @if(auth('customer')->check())
                    {{auth('customer')->user()->name}}
                @else
                    {{__("Guest")}}
                @endif
            </a>
        </li>
        <li class="menu-btn">
            <a href="{{route('client.profile')}}">
                <i class="ri-trophy-line mx-2"></i>
                {{__("Credit")}}
            </a>
        </li>
        <li class="menu-btn  desktop-menu">
            <span>
                <i class="ri-line-chart-line mx-2"></i>
                 {{number_format( ((int) getSetting('gold')) )}} {{config('app.currency.symbol')}}
            </span>
        </li>
    </ul>
</nav>
<div id="zar-menu">
    <nav>
        <ul>
            @if(config('app.xlang.active'))
                <li>
                    @foreach(\App\Models\XLang::all() as $lang)
                        @if($lang->tag != app()->getLocale())
                            <a href="/{{$lang->tag}}" class="d-inline-block px-1">
                                {{$lang->emoji}}
                            </a>
                        @endif
                    @endforeach
                </li>
            @endif
            <li>
                <form action="{{route('client.search')}}" class="side-data">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" placeholder="{{__('Search')}}...">
                        <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                            <i class="ri-search-2-line"></i>
                        </button>
                    </div>
                </form>
            </li>

            @foreach(getMenuBySettingItems($data->area_name.'_'.$data->part.'_menu') as $item)
                <li>
                    <a href="{{$item->webUrl()}}">
                        {{$item->title}}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
