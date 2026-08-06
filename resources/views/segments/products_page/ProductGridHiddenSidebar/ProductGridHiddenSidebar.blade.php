<section class='ProductGridHiddenSidebar content live-setting py-4' data-live="{{$data->area_name.'_'.$data->part}}" id="product-list-view">
    <div class="{{gfx()['container']}}">
        <!-- Filter Toolbar Bar -->
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5" id="filter-btn-show" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="custom-tooltip" data-bs-title="{{__("Sort & filter")}}">
                    <i class="ri-filter-3-line"></i>
                    <span>{{__("Sort & filter")}}</span>
                </button>
            </div>
            <span class="text-muted fs-14">
                {{number_format($products->total())}} {{__("Products")}}
            </span>
        </div>

        <div id="hidden-sidebar">
            @include('segments.products_page.ProductGridHiddenSidebar.inc.product-sidebar-filter')
        </div>

        <div class="py-2">
            <div class="row g-3 g-md-4">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include(\App\Models\Area::where('name','product-grid')->first()->defPart(),compact('product'))
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{$products->withQueryString()->links()}}
            </div>
        </div>
    </div>
</section>
