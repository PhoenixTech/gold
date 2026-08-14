<section class='ParallelCategoriesGrid content live-setting py-4 my-3' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        @if(count($category->parallelCategories()) > 0)
            <div class="parallel-categories-wrapper">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <h5 class="fs-4 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ri-grid-fill text-primary"></i>
                        <span>{{getSetting($data->area_name.'_'.$data->part.'_title') ?: __('Other categories')}}</span>
                    </h5>
                </div>

                <div class="row g-3 g-md-4">
                    @foreach($category->parallelCategories() as $subCat)
                        <div class="col-6 col-sm-4 col-md-3">
                            <a href="{{$subCat->webUrl()}}" class="parallel-category-card card border-0 shadow-sm rounded-4 overflow-hidden d-block text-decoration-none transition-all">
                                <div class="card-img-box position-relative overflow-hidden bg-dark" style="height: 200px;">
                                    <img src="{{$subCat->imgUrl()}}" alt="{{$subCat->name}}" class="card-img-bg w-100 h-100 object-fit-cover opacity-85 transition-all" loading="lazy">
                                    <div class="card-overlay-gradient position-absolute inset-0"></div>
                                    <div class="card-content-overlay position-absolute bottom-0 start-0 end-0 p-3 text-center text-white z-2">
                                        <h4 class="category-title fs-15 fw-bold text-white mb-1 text-shadow">
                                            {{$subCat->name}}
                                        </h4>
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
        @endif
    </div>
</section>
