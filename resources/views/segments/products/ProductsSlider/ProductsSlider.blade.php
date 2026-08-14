<section class='ProductsSlider live-setting' data-live='{{$data->area_name.'_'.$data->part}}' >
    <div class="{{gfx()['container']}}">
        <h3>
            {{getSetting($part->area_name . '_' . $part->part.'_title')}}
        </h3>

        <div class="products-slider">
            @foreach(getProductsQueryBySetting($part->area_name . '_' . $part->part.'_query') as $product)
                <div class="slider-content">
                    @include(\App\Models\Area::where('name','product-grid')->first()->defPart(),compact('product'))
                </div>
            @endforeach
        </div>
    </div>
</section>
