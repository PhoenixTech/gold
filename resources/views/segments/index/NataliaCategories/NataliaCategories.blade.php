<section class='NataliaCategories  live-setting' data-live="{{$data->area_name.'_'.$data->part}}"
         style="background-image: url('{{$bg??asset('upload/images/'.$part->area_name . '.' . $part->part.'.webp')}}')">
    <div class="{{gfx()['container']}}">
        <div class="row nata-content">
            <div class="col-md-6 pt-5 slider-content">
                <div class="text-center">
                    <h1>
                        {{getSetting($data->area_name.'_'.$data->part.'_title')}}
                    </h1>
                    <h2>
                        {{getSetting($data->area_name.'_'.$data->part.'_subtitle')}}
                    </h2>
                </div>
                <ul class="main-dir">
                    @foreach(getCategorySubCatsBySetting($data->area_name.'_'.$data->part.'_category',4,'sort','ASC') as $category)
                        <li>
                            <a href="{{$category->webUrl()}}">
                                <img src="{{$category->svgUrl()}}" alt="{{$category->name}}" class="mx-2 float-start">
                                {{$category->name}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-6 nata-bg">

            </div>
        </div>
    </div>
</section>
