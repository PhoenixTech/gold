@extends('website.inc.website-layout')

@section('title')
    {{$title ?? __('Articles & News')}} - {{config('app.name')}}
@endsection

@section('content')
<main class="posts-page-view py-4">
    <div class="{{gfx()['container']}}">
        <header class="posts-header-wrapper mb-4 pb-3 border-bottom border-light-subtle">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb fs-13 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('client.welcome') }}" class="text-muted text-decoration-none hover-primary">{{ __("Home") }}</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">{{ $title ?? __("Articles & News") }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="fs-3 fw-bold text-main mb-1">
                        {{ $title ?? __('Articles & News') }}
                    </h1>
                    @if(!empty(trim($subtitle ?? '')))
                        <p class="text-muted fs-14 mb-0">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
        </header>

        <div class="row g-4">
            <div class="col-lg-8 col-xl-9">
                @if($posts->count() > 0)
                    <div class="row g-3 g-md-4">
                        @foreach($posts as $post)
                            <div class="col-sm-6 col-md-6 col-lg-4">
                                @include('client.partials.post-card', ['post' => $post])
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-2 d-flex justify-content-center">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="card border rounded-3 p-5 text-center bg-white shadow-xs">
                        <i class="ri-article-line fs-1 text-muted mb-2" aria-hidden="true"></i>
                        <h2 class="fw-bold fs-5 text-main mb-1">{{ __("No articles found") }}</h2>
                        <p class="text-muted fs-14 mb-3">{{ __("There are currently no articles in this section.") }}</p>
                        <div>
                            <a href="{{ route('client.welcome') }}" class="btn btn-outline-primary btn-sm px-3 rounded-2">
                                {{ __("Home") }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4 col-xl-3">
                @include('client.partials.post-sidebar')
            </div>
        </div>
    </div>
</main>
@endsection
