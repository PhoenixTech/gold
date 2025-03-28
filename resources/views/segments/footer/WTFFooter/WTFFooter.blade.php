<section class="WTFFooter live-setting" data-live="{{$data->area_name.'_'.$data->part}}" >
    @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $mainCategory)
        <a class="wtfooter-btn" href="{{$mainCategory->webUrl()}}" >
            <img src="{{$mainCategory->svgUrl()}}" alt="{{$mainCategory->name}}">
            {{$mainCategory->name}}
        </a>
    @endforeach
</section>
