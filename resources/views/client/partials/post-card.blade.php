<article class="grid-post-card card h-100 border rounded-3 overflow-hidden d-flex flex-column bg-white shadow-xs">
    <a href="{{$post->webUrl()}}" class="card-img-wrapper d-block position-relative overflow-hidden bg-light" tabindex="-1" aria-hidden="true">
        <img src="{{$post->imgUrl()}}" alt="{{$post->title}}" class="post-card-img w-100 h-100 object-fit-cover" loading="lazy" width="400" height="225">
    </a>

    <div class="card-body p-3 d-flex flex-column flex-grow-1">
        @if($post->mainGroup)
            <div class="mb-1.5">
                <a href="{{$post->mainGroup->webUrl()}}" class="text-decoration-none text-muted hover-primary fs-12 fw-medium">
                    {{$post->mainGroup->name}}
                </a>
            </div>
        @endif

        <h3 class="post-card-title fs-14 fw-bold mb-2 leading-snug">
            <a href="{{$post->webUrl()}}" class="text-decoration-none text-main hover-primary line-clamp-2">
                {{$post->title}}
            </a>
        </h3>

        @if(!empty(trim($post->subtitle ?? '')))
            <p class="text-muted fs-12 mb-3 line-clamp-2 leading-relaxed">
                {{$post->subtitle}}
            </p>
        @endif

        <div class="mt-auto pt-2 border-top border-light-subtle d-flex align-items-center justify-content-between text-muted fs-12">
            <time datetime="{{$post->created_at->toIso8601String()}}" class="d-inline-flex align-items-center gap-1 fa-num">
                <i class="ri-calendar-line" aria-hidden="true"></i>
                <span>{{$post->created_at->ldate('Y/m/d')}}</span>
            </time>
            <span class="d-inline-flex align-items-center gap-1 fa-num">
                <i class="ri-time-line" aria-hidden="true"></i>
                <span>{{$post->spendTime()}}</span>
            </span>
        </div>
    </div>
</article>
