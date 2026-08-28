@extends('website.inc.website-layout')

@section('title')
    {{$post->title}} - {{config('app.name')}}
@endsection

@section('content')
<div class="post-single-view py-4">
    <div class="{{gfx()['container']}}">

        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-light-subtle p-2 px-3 rounded-pill border d-inline-flex align-items-center mb-0 fs-13">
                <li class="breadcrumb-item">
                    <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                        <i class="ri-home-2-line me-1"></i> {{config('app.name')}}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{route('client.posts')}}" class="text-decoration-none text-muted hover-primary">
                        {{__("Posts")}}
                    </a>
                </li>
                @if($post->mainGroup)
                    <li class="breadcrumb-item">
                        <a href="{{$post->mainGroup->webUrl()}}" class="text-decoration-none text-muted hover-primary">
                            {{$post->mainGroup->name}}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active text-dark fw-bold text-truncate" style="max-width: 280px;" aria-current="page">
                    {{$post->title}}
                </li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Article Body Column -->
            <div class="col-lg-8 col-xl-9">
                <article class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <!-- Featured Image -->
                    @if($post->imgUrl())
                        <div class="post-image-wrapper mb-4 overflow-hidden rounded-4">
                            <img src="{{$post->orgUrl()}}" alt="{{$post->title}}" class="img-fluid w-100 object-fit-cover rounded-4 shadow-sm" style="max-height: 440px;" loading="lazy">
                        </div>
                    @endif

                    <!-- Title -->
                    <h1 class="fs-2 fw-bold text-dark mb-3">
                        {{$post->title}}
                    </h1>

                    <!-- Meta Bar -->
                    <div class="d-flex align-items-center flex-wrap gap-3 py-2 px-3 bg-light-subtle rounded-3 border mb-4 fs-13 text-muted">
                        <div class="d-flex align-items-center gap-1">
                            <i class="ri-calendar-line text-primary"></i>
                            <span>{{$post->created_at->format('Y-m-d')}}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="ri-time-line text-primary"></i>
                            <span>{{__("Time spend")}}: {{$post->spendTime()}}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="ri-eye-line text-primary"></i>
                            <span>{{number_format($post->view)}} {{__("views")}}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="ri-chat-3-line text-primary"></i>
                            <span>{{number_format($post->approvedComments()->count())}} {{__("Comments")}}</span>
                        </div>
                    </div>

                    <!-- Post Body Content -->
                    <div class="post-content leading-relaxed fs-15 text-dark mb-4">
                        {!! $post->body !!}
                    </div>

                    <!-- Tags -->
                    @if($post->tags && $post->tags->count() > 0)
                        <div class="post-tags-list d-flex align-items-center flex-wrap gap-1 pt-3 border-top">
                            <span class="fw-semibold text-dark fs-13 me-1">{{__("Tags")}}:</span>
                            @foreach($post->tags as $tag)
                                <a href="{{tagUrl($tag->slug)}}" class="badge bg-light text-dark border rounded-pill px-3 py-1.5 text-decoration-none fs-12 hover-primary">
                                    # {{$tag->name}}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>

                <!-- Comments Section -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" id="comments">
                    <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="ri-chat-3-line text-primary"></i>
                        <span>{{__("Comments")}} ({{$post->approvedComments()->count()}})</span>
                    </h5>

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
                        <div class="text-center py-4 text-muted border rounded-3 bg-light mb-4">
                            <i class="ri-chat-smile-3-line fs-2 d-block mb-1 text-secondary"></i>
                            <p class="mb-0 fs-14">{{ __("No comments yet. Be the first to share your thoughts!") }}</p>
                        </div>
                    @endif

                    <!-- Comment Submit Form -->
                    <div class="comment-form-card border rounded-3 p-3 p-md-4 bg-light-subtle">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="ri-edit-line text-primary"></i>
                            <span>{{ __("Post your comment") }}</span>
                        </h6>
                        @include('components.err')
                        <form id="comment-form" class="safe-form" method="post" action="{{ route('client.comment.submit') }}">
                            <div class="safe-url" data-url="{{route('client.comment.submit')}}"></div>
                            @csrf
                            <input type="hidden" name="commentable_type" value="{{\App\Models\Post::class}}">
                            <input type="hidden" name="commentable_id" value="{{$post->id}}">
                            <input type="hidden" name="parent_id" id="parent_id" value="">

                            <div class="row g-3">
                                @if(auth()->check())
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 mb-0 fs-14 d-flex align-items-center gap-2">
                                            <i class="ri-user-line"></i>
                                            <span>{{ __("Commenting as") }}: <strong>{{ auth()->user()->name }}</strong> ({{ __('Admin') }})</span>
                                        </div>
                                    </div>
                                @elseif(auth('customer')->check())
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 mb-0 fs-14 d-flex align-items-center gap-2">
                                            <i class="ri-user-line"></i>
                                            <span>{{ __("Commenting as") }}: <strong>{{ auth('customer')->user()->name ?: auth('customer')->user()->mobile }}</strong></span>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <label for="comment-name" class="form-label fs-14 fw-semibold">{{ __("Name") }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="comment-name" class="form-control rounded-3" placeholder="{{ __("Your name") }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="comment-email" class="form-label fs-14 fw-semibold">{{ __("Email") }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="comment-email" class="form-control rounded-3" placeholder="name@example.com" required>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label for="comment-message" class="form-label fs-14 fw-semibold">{{ __("Message") }} <span class="text-danger">*</span></label>
                                    <textarea name="message" id="comment-message" rows="4" class="form-control rounded-3" placeholder="{{ __("Write your thoughts...") }}" required></textarea>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                                        <i class="ri-send-plane-2-line"></i>
                                        <span>{{ __("Submit comment") }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-4 col-xl-3">
                @include('client.partials.post-sidebar')
            </div>
        </div>
    </div>
</div>
@endsection
