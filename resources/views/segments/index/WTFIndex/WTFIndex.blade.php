<section class="WTFIndex live-setting my-4" data-live="{{$data->area_name.'_'.$data->part}}">
    <!-- Top Category Tabs Bar -->
    <div class="wtf-tabs-container bg-white border-top border-bottom shadow-sm mb-4">
        <div class="{{gfx()['container']}}">
            <div id="wtf-main-btns" class="d-flex align-items-center justify-content-center overflow-auto py-3 gap-2 text-nowrap">
                @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $k => $mainCategory)
                    <button type="button" class="btn main-dir rounded-pill px-4 py-2 fw-bold fs-14 transition-all @if($k == 0) active @endif shadow-sm"
                            style="background: {{$mainCategory->bg_color}}; color: {{$mainCategory->color}};"
                            data-id="#wtf-{{$mainCategory->id}}">
                        {{$mainCategory->name}}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Category Content Panels -->
    <div class="py-2">
        @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $k => $mainCategory)
            @php($x = explode(' ',$mainCategory->name))
            <div class="{{gfx()['container']}} wtf-section" id="wtf-{{$mainCategory->id}}" @if($k == 0) style="display: block" @endif>
                <div class="row g-3 g-md-4">
                    @foreach($mainCategory->children()->where('hide',0)->orderBy('sort')->get() as $childCategory)
                        <div class="col-6 col-sm-4 col-md-3">
                            <a class="wtf-cat-card card border-0 shadow-sm rounded-4 overflow-hidden text-decoration-none h-100 transition-all d-block position-relative" href="{{$childCategory->webUrl()}}">
                                <div class="card-img-box position-relative bg-dark overflow-hidden" style="height: 200px;">
                                    <img src="{{$childCategory->imgUrl()}}" alt="{{$childCategory->name}}" class="w-100 h-100 object-fit-cover cat-img-hover opacity-85" loading="lazy">
                                    <div class="card-overlay-vignette position-absolute inset-0"></div>
                                    <div class="position-absolute bottom-0 start-0 end-0 p-3 text-center z-2">
                                        <h5 class="cat-title fs-15 fw-bold text-white mb-1 text-shadow">
                                            {{implode(' ',array_diff(explode(' ',$childCategory->name),$x)) ?: $childCategory->name}}
                                        </h5>
                                        <span class="badge bg-white-20 text-white rounded-pill px-2.5 py-0.5 fs-12 border border-white-30 backdrop-blur d-inline-flex align-items-center gap-1">
                                            <span>{{__("View category")}}</span>
                                            <i class="ri-arrow-left-s-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
