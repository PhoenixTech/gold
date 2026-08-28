<div class="grid-post-card card h-100 border-0 shadow-xs hover-lift rounded-4 overflow-hidden d-flex flex-column transition-all bg-white">
    <div class="card-img-wrapper position-relative overflow-hidden bg-light">
        @if($post->mainGroup)
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill position-absolute top-0 start-0 m-3 z-2 fs-11 fw-semibold px-2.5 py-1">
                <i class="ri-folder-3-line me-0.5"></i> {{$post->mainGroup->name}}
            </span>
        @endif
        <a href="{{$post->webUrl()}}" class="d-block overflow-hidden" style="height: 200px;">
            <img src="{{$post->imgUrl()}}" alt="{{$post->title}}" class="card-img-top post-card-img w-100 h-100 object-fit-cover transition-all" loading="lazy">
        </a>
    </div>

    <div class="card-body p-3.5 d-flex flex-column flex-grow-1">
        <h5 class="post-card-title fs-15 fw-bold mb-2 leading-snug">
            <a href="{{$post->webUrl()}}" class="text-decoration-none text-main hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                {{$post->title}}
            </a>
        </h5>

        @if($post->subtitle)
            <p class="card-text text-muted fs-13 mb-3 leading-relaxed" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                {{$post->subtitle}}
            </p>
        @endif

        <div class="mt-auto pt-3 border-top border-light-subtle d-flex align-items-center justify-content-between text-muted fs-12">
            <span class="d-inline-flex align-items-center">
                <i class="ri-calendar-line text-warning me-1"></i>
                {{$post->created_at->format('Y/m/d')}}
            </span>
            <span class="d-inline-flex align-items-center">
                <i class="ri-eye-line text-warning me-1"></i>
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
