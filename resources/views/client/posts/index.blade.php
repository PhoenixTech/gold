@extends('website.inc.website-layout')

@section('title')
    {{$title ?? __('Articles & News')}} - {{config('app.name')}}
@endsection

@section('content')
<div class="posts-page-view">
    @include('client.partials.parallax-header', ['title' => $title ?? __('Articles & News'), 'subtitle' => $subtitle ?? __('Latest articles, news and gold market guides')])

    <div class="{{gfx()['container']}} py-4">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-4 col-xl-3">
                @include('client.partials.post-sidebar')
            </div>

            <!-- Posts List -->
            <div class="col-lg-8 col-xl-9">
                @if($posts->count() > 0)
                    <div class="row g-4">
                        @foreach($posts as $post)
                            <div class="col-md-6 col-lg-4">
                                @include('client.partials.post-card', ['post' => $post])
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-5 d-flex justify-content-center">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                        <i class="ri-article-line fs-1 text-muted mb-2"></i>
                        <h5 class="fw-bold text-dark mb-1">{{ __("No articles found") }}</h5>
                        <p class="text-muted fs-14 mb-0">{{ __("There are currently no articles in this section.") }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
