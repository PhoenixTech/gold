<section class="WTFFooter live-setting" data-live="{{$data->area_name.'_'.$data->part}}" >
    @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $k => $mainCategory)
        <a class="wtfooter-btn" href="{{$mainCategory->webUrl()}}" >
            @if($k == 3)
                <img id="ballon" src="{{asset('assets/default/ballon.webp')}}" alt="">
            @endif
            <img src="{{$mainCategory->svgUrl()}}" alt="{{$mainCategory->name}}">
            {{$mainCategory->name}}
        </a>
    @endforeach
</section>
