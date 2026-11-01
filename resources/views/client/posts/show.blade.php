@extends('website.inc.website-layout')

@section('title')
    {{$post->title}} - {{config('app.name')}}
@endsection

@section('content')
<main class="post-single-view py-4">
    <div class="{{gfx()['container']}}">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb fs-13 mb-0">
                <li class="breadcrumb-item"><a href="{{ route('client.welcome') }}" class="text-muted text-decoration-none hover-primary">{{ __("Home") }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('client.posts') }}" class="text-muted text-decoration-none hover-primary">{{ __("Posts") }}</a></li>
                @if($post->mainGroup)
                    <li class="breadcrumb-item"><a href="{{ $post->mainGroup->webUrl() }}" class="text-muted text-decoration-none hover-primary">{{ $post->mainGroup->name }}</a></li>
                @endif
                <li class="breadcrumb-item active text-dark fw-semibold text-truncate" style="max-width: 320px;" aria-current="page">{{ $post->title }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8 col-xl-9">
                <article class="post-article-card card border-0 rounded-4 p-3 p-md-4 mb-4 bg-white shadow-xs">
                    <header class="post-header mb-3">
                        @if($post->mainGroup)
                            <div class="mb-2">
                                <a href="{{$post->mainGroup->webUrl()}}" class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2.5 py-1 text-decoration-none fs-12 fw-medium">
                                    {{$post->mainGroup->name}}
                                </a>
                            </div>
                        @endif

                        <h1 class="post-title fs-22 fs-md-26 fw-bold text-main mb-2 leading-snug">
                            {{$post->title}}
                        </h1>

                        <div class="d-flex align-items-center flex-wrap gap-3 py-2 border-bottom border-light-subtle fs-12 text-muted">
                            <time datetime="{{$post->created_at->toIso8601String()}}" class="d-inline-flex align-items-center gap-1 fa-num">
                                <i class="ri-calendar-line" aria-hidden="true"></i>
                                <span>{{$post->created_at->ldate('Y/m/d')}}</span>
                            </time>
                            <span class="d-inline-flex align-items-center gap-1 fa-num">
                                <i class="ri-time-line" aria-hidden="true"></i>
                                <span>{{$post->spendTime()}}</span>
                            </span>
                            <span class="d-inline-flex align-items-center gap-1 fa-num">
                                <i class="ri-chat-3-line" aria-hidden="true"></i>
                                <span>{{number_format($post->approvedComments()->count())}} {{__("Comments")}}</span>
                            </span>
                        </div>
                    </header>

                    @if($post->imgUrl())
                        <figure class="post-featured-media mb-4 overflow-hidden rounded-4">
                            <img src="{{$post->orgUrl()}}" alt="{{$post->title}}" class="img-fluid w-100 object-fit-cover rounded-4" style="aspect-ratio: 16 / 9; max-height: 480px;" fetchpriority="high">
                        </figure>
                    @endif

                    <div class="post-content leading-loose fs-15 text-body mb-4">
                        {!! $post->body !!}
                    </div>

                    @if($post->tags && $post->tags->count() > 0)
                        <footer class="post-tags-list d-flex align-items-center flex-wrap gap-1.5 pt-3 border-top border-light-subtle">
                            @foreach($post->tags as $tag)
                                <a href="{{tagUrl($tag->slug)}}" class="badge bg-light text-muted border rounded-pill px-2.5 py-1 text-decoration-none fs-11 hover-primary">
                                    #{{$tag->name}}
                                </a>
                            @endforeach
                        </footer>
                    @endif
                </article>

                <section class="card border-0 rounded-4 p-3 p-md-4 mb-4 bg-white shadow-xs" id="comments" aria-labelledby="comments-heading">
                    <h2 id="comments-heading" class="fw-bold fs-16 text-main mb-3 fa-num">
                        {{__("Comments")}} ({{number_format($post->approvedComments()->count())}})
                    </h2>

                    @php
                        $approvedComments = $post->approvedComments()->whereNull('parent_id')->with(['approved_children'])->orderByDesc('id')->get();
                    @endphp

                    @if($approvedComments->count() > 0)
                        <div class="comments-list mb-4 d-flex flex-column gap-3">
                            @foreach($approvedComments as $comment)
                                @include('client.partials.comment-item', ['comment' => $comment])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted border rounded-3 bg-light-subtle mb-4">
                            <p class="mb-0 fs-13">{{ __("No comments yet. Be the first to share your thoughts!") }}</p>
                        </div>
                    @endif

                    <div class="comment-form-card border rounded-4 p-3 p-md-4 bg-light-subtle">
                        <h3 class="fw-bold fs-15 text-main mb-3">
                            {{ __("Post your comment") }}
                        </h3>
                        @include('components.err')
                        <form id="comment-form" class="safe-form" method="post" action="{{ route('client.comment.submit') }}">
                            <div class="safe-url" data-url="{{route('client.comment.submit')}}"></div>
                            @csrf
                            <input type="hidden" name="commentable_type" value="{{\App\Models\Post::class}}">
                            <input type="hidden" name="commentable_id" value="{{$post->id}}">
                            <input type="hidden" name="parent_id" id="parent_id" value="">

                            <div id="replying-to-badge" class="alert alert-secondary py-1.5 px-3 fs-12 d-none align-items-center justify-content-between mb-3 rounded-3">
                                <span>{{ __('Reply') }}: <strong id="reply-to-name"></strong></span>
                                <button type="button" class="btn-close fs-10" id="cancel-reply-btn" aria-label="{{ __('Cancel') }}"></button>
                            </div>

                            <div class="row g-3">
                                @if(auth()->check())
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 mb-0 fs-12 d-flex align-items-center gap-2 rounded-3">
                                            <i class="ri-user-line" aria-hidden="true"></i>
                                            <span>{{ __("Commenting as") }}: <strong>{{ auth()->user()->name }}</strong> ({{ __('Admin') }})</span>
                                        </div>
                                    </div>
                                @elseif(auth('customer')->check())
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 mb-0 fs-12 d-flex align-items-center gap-2 rounded-3">
                                            <i class="ri-user-line" aria-hidden="true"></i>
                                            <span>{{ __("Commenting as") }}: <strong>{{ auth('customer')->user()->name ?: auth('customer')->user()->mobile }}</strong></span>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="comment-name" class="form-label fs-12 fw-medium text-main mb-1">{{ __("Name") }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="comment-name" class="form-control form-control-sm rounded-3" placeholder="{{ __("Your name") }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="comment-email" class="form-label fs-12 fw-medium text-main mb-1">{{ __("Email") }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="comment-email" class="form-control form-control-sm rounded-3" placeholder="name@example.com" required>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label for="comment-message" class="form-label fs-12 fw-medium text-main mb-1">{{ __("Message") }} <span class="text-danger">*</span></label>
                                    <textarea name="message" id="comment-message" rows="3" class="form-control rounded-3 fs-13" placeholder="{{ __("Write your thoughts...") }}" required></textarea>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold fs-14 d-inline-flex align-items-center gap-2 shadow-xs">
                                        <span>{{ __("Submit comment") }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-lg-4 col-xl-3">
                @include('client.partials.post-sidebar')
            </div>
        </div>
    </div>
</main>
@endsection
