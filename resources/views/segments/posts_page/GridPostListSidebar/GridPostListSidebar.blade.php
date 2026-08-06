<section class="GridPostListSidebar content live-setting py-4" data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="row g-4">
            @if(getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.posts_page.GridPostListSidebar.inc.sidebar')
                </div>
            @endif

            <div class="col-lg-8 col-xl-9">
                <div class="row g-4">
                    @foreach($posts as $post)
                        <div class="col-md-6 col-lg-4">
                            <div class="grid-post-card card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column transition-all">
                                <div class="card-img-wrapper position-relative overflow-hidden">
                                    @if($post->mainGroup)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill position-absolute top-0 start-0 m-3 z-2 fs-12 px-3 py-1.5 shadow-sm">
                                            <i class="ri-folder-3-line me-1"></i> {{$post->mainGroup->name}}
                                        </span>
                                    @endif
                                    <a href="{{$post->webUrl()}}" class="d-block overflow-hidden">
                                        <img src="{{$post->imgUrl()}}" alt="{{$post->title}}" class="card-img-top post-card-img" loading="lazy">
                                    </a>
                                </div>

                                <div class="card-body p-3.5 d-flex flex-column flex-grow-1">
                                    <h3 class="post-card-title fs-16 fw-bold mb-2">
                                        <a href="{{$post->webUrl()}}" class="text-decoration-none text-dark hover-primary line-clamp-2">
                                            {{$post->title}}
                                        </a>
                                    </h3>

                                    @if($post->subtitle)
                                        <p class="card-text text-muted fs-14 mb-3 line-clamp-2 leading-relaxed">
                                            {{$post->subtitle}}
                                        </p>
                                    @endif

                                    <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between text-muted fs-13">
                                        <span class="d-inline-flex align-items-center">
                                            <i class="ri-calendar-line text-primary me-1"></i>
                                            {{$post->created_at->ldate('Y/m/d')}}
                                        </span>
                                        <span class="d-inline-flex align-items-center">
                                            <i class="ri-eye-line text-primary me-1"></i>
                                            {{number_format($post->view)}}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                    <a href="{{$post->webUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold py-1.5 d-flex align-items-center justify-content-center gap-1">
                                        <span>{{__("Read more")}}</span>
                                        <i class="ri-arrow-left-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    {{$posts->links()}}
                </div>
            </div>

            @if(!getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.posts_page.GridPostListSidebar.inc.sidebar')
                </div>
            @endif
        </div>
    </div>
</section>
