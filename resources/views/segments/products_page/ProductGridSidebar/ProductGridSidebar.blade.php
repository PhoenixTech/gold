<section class='ProductGridSidebar content live-setting py-4' data-live="{{$data->area_name.'_'.$data->part}}" id="product-list-view">
    <div class="{{gfx()['container']}}">
        <div class="row g-4">
            @if(getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.products_page.ProductGridSidebar.inc.product-sidebar')
                </div>
            @endif

            <div class="col-lg-8 col-xl-9">
                <div class="row g-3 g-md-4">
                    @foreach($products as $product)
                        <div class="col-6 col-md-6 col-lg-4">
                            @include(\App\Models\Area::where('name','product-grid')->first()->defPart(),compact('product'))
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    {{$products->withQueryString()->links()}}
                </div>
            </div>

            @if(!getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.products_page.ProductGridSidebar.inc.product-sidebar')
                </div>
            @endif
        </div>
    </div>
</section>
