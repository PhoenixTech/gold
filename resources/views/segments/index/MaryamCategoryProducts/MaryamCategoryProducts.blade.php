<section class='MaryamCategoryProducts live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <h3>
            {{getSetting($data->area_name.'_'.$data->part.'_title')}}
        </h3>

        <div class="maryam-row">
            @foreach(getCategoryProductBySetting($part->area_name . '_' . $part->part.'_category',12) as $product)
                <a class="maryam-item" href="{{$product->webUrl()}}">
                    <img src="{{$product->imgUrl()}}" alt="{{$product->name}}" class="img-fluid" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</section>
