@extends('website.inc.website-layout')

@section('title')
    #{{$tag->name}} - {{config('app.name')}}
@endsection

@section('content')
<div class="posts-tag-view">
    @include('client.partials.parallax-header', ['title' => '#' . $tag->name, 'subtitle' => $subtitle ?? ''])
    <div class="{{gfx()['container']}} py-4">
        <div class="row g-4">
            <div class="col-lg-4 col-xl-3">
                @include('client.partials.post-sidebar')
            </div>
            <div class="col-lg-8 col-xl-9">
                @if(isset($posts) && $posts->count() > 0)
                    <div class="row g-4">
                        @foreach($posts as $post)
                            <div class="col-md-6 col-lg-4">
                                @include('client.partials.post-card', ['post' => $post])
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 d-flex justify-content-center">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                        <i class="ri-price-tag-3-line fs-1 text-muted mb-2"></i>
                        <h5 class="fw-bold text-dark mb-1">{{ __("No articles found for this tag") }}</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
