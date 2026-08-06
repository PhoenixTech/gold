<section class="PostSidebar content live-setting py-4" data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb bg-light-subtle p-2 px-3 rounded-pill border d-inline-flex align-items-center mb-0 fs-14">
                <li class="breadcrumb-item">
                    <a href="{{homeUrl()}}" class="text-decoration-none text-muted">
                        <i class="ri-home-2-line me-1"></i> {{config('app.name')}}
                    </a>
                </li>
                @if($post->mainGroup)
                    <li class="breadcrumb-item">
                        <a href="{{$post->mainGroup->webUrl()}}" class="text-decoration-none text-muted">
                            {{$post->mainGroup->name}}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active text-truncate" style="max-width: 280px;" aria-current="page">
                    {{$post->title}}
                </li>
            </ol>
        </nav>

        <div class="row g-4">
            @if(getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.post.PostSidebar.inc.sidebar')
                </div>
            @endif

            <div class="col-lg-8 col-xl-9">
                <article class="post-article-card mb-4">
                    <!-- Featured Image -->
                    @if($post->imgUrl())
                        <div class="post-image-wrapper mb-4">
                            <img src="{{$post->orgUrl()}}" alt="{{$post->title}}" class="post-featured-image img-fluid rounded-4 shadow-sm" loading="lazy">
                        </div>
                    @endif

                    <!-- Meta Bar -->
                    <div class="post-meta-bar d-flex align-items-center flex-wrap gap-3 py-2.5 px-3 bg-light-subtle rounded-3 border mb-4 fs-14 text-muted">
                        <div class="meta-item">
                            <i class="ri-calendar-line text-primary me-1"></i>
                            <span>{{$post->created_at->ldate('Y/m/d')}}</span>
                        </div>
                        <div class="meta-item">
                            <i class="ri-time-line text-primary me-1"></i>
                            <span>{{__("Time spend")}}: {{$post->spendTime()}}</span>
                        </div>
                        <div class="meta-item">
                            <i class="ri-eye-line text-primary me-1"></i>
                            <span>{{number_format($post->view)}} {{__("view")}}</span>
                        </div>
                        <div class="meta-item">
                            <i class="ri-chat-3-line text-primary me-1"></i>
                            <span>{{number_format($post->approvedComments()->count())}} {{__("Comments")}}</span>
                        </div>
                    </div>

                    <!-- Article Body -->
                    <div class="post-content fs-16 leading-relaxed">
                        @if($post->table_of_contents)
                            <div class="post-toc p-3.5 mb-4 rounded-3 border bg-light-subtle">
                                {!! $post->tableOfContents() !!}
                            </div>
                            {!! $post->bodyContent() !!}
                        @else
                            {!! $post->body !!}
                        @endif
                    </div>

                    <!-- Tags -->
                    @if($post->tags()->count() > 0)
                        <div class="post-tags mt-4 pt-3 border-top d-flex align-items-center flex-wrap gap-2">
                            <span class="fw-bold text-muted me-2"><i class="ri-price-tag-3-line me-1"></i>{{__("Tags")}}:</span>
                            @foreach($post->tags as $tag)
                                <a href="{{tagUrl($tag->slug)}}" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 text-decoration-none fs-13">
                                    # {{$tag->name}}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>

            @if(!getSetting($data->area_name.'_'.$data->part.'_invert'))
                <div class="col-lg-4 col-xl-3">
                    @include('segments.post.PostSidebar.inc.sidebar')
                </div>
            @endif
        </div>
    </div>
</section>
