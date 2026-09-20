<div class="grid-post-card card h-100 border-0 shadow-xs hover-lift rounded-3 rounded-md-4 overflow-hidden d-flex flex-column transition-all bg-white">
    <div class="card-img-wrapper position-relative overflow-hidden bg-light">
        @if($post->mainGroup)
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill position-absolute top-0 start-0 m-1.5 m-md-2 z-2 fs-10 fs-md-11 fw-semibold px-2 py-0.5">
                <i class="ri-folder-3-line me-0.5"></i> {{$post->mainGroup->name}}
            </span>
        @endif
        <a href="{{$post->webUrl()}}" class="d-block overflow-hidden h-100 w-100">
            <img src="{{$post->imgUrl()}}" alt="{{$post->title}}" class="card-img-top post-card-img w-100 h-100 object-fit-cover transition-all" loading="lazy">
        </a>
    </div>

    <div class="card-body p-2 p-md-2.5 d-flex flex-column flex-grow-1">
        <h3 class="post-card-title fs-13 fs-md-14 fw-semibold mb-1 leading-snug">
            <a href="{{$post->webUrl()}}" class="text-decoration-none text-main hover-primary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                {{$post->title}}
            </a>
        </h3>

        <div class="mt-auto pt-1.5 border-top border-light-subtle d-flex align-items-center justify-content-between gap-1 text-muted fs-11 fs-md-12">
            <span class="d-inline-flex align-items-center">
                <i class="ri-calendar-line text-warning me-1"></i>
                {{$post->created_at->format('Y/m/d')}}
            </span>
            <a href="{{$post->webUrl()}}"
               class="btn btn-outline-primary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center flex-shrink-0 post-read-btn"
               data-bs-custom-class="custom-tooltip"
               data-bs-toggle="tooltip" data-bs-placement="top" title="{{__("Read more")}}">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
    </div>
</div>
