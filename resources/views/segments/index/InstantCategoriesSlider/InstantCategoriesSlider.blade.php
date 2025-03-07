<section class='MainCategoriesSlider live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <h1>
            {{getSetting($data->area_name.'_'.$data->part.'_title')}}
        </h1>
        <div id="instant-cats">
            @foreach(getCategorySubCatsBySetting($data->area_name.'_'.$data->part.'_category',10,'sort','ASC') as $category)
                <div class="item slider-content">
                    <a class="instant-category" href="{{$category->webUrl()}}">
                        <img src="{{$category->imgUrl()}}" alt="{{$category->name}}" title="{{$category->name}}" >
                        <h4>
                            {{$category->name}}
                        </h4>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
