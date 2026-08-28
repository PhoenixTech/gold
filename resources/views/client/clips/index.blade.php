@extends('website.inc.website-layout')

@section('title')
    {{$title ?? __('Videos & Clips')}} - {{config('app.name')}}
@endsection

@section('content')
<div class="clips-page-view">
    @include('client.partials.parallax-header', ['title' => $title ?? __('Videos & Clips'), 'subtitle' => $subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row g-4">
            @if(isset($clips) && $clips->count() > 0)
                @foreach($clips as $clip)
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div class="position-relative" style="aspect-ratio: 16/9;">
                                <img src="{{$clip->imgUrl()}}" alt="{{$clip->title}}" class="w-100 h-100 object-fit-cover" loading="lazy">
                                <a href="{{$clip->webUrl()}}" class="position-absolute top-50 start-50 translate-middle btn btn-primary rounded-circle p-0 d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px;">
                                    <i class="ri-play-fill fs-20"></i>
                                </a>
                            </div>
                            <div class="card-body p-3">
                                <h5 class="fw-bold text-dark fs-15 mb-1">{{$clip->title}}</h5>
                                <small class="text-muted d-block text-truncate fs-12">{{$clip->subtitle}}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5 text-muted">
                    <i class="ri-video-line fs-1 d-block mb-2"></i>
                    <p>{{__('No videos found.')}}</p>
                </div>
            @endif
        </div>
        @if(isset($clips) && method_exists($clips, 'links'))
            <div class="mt-4 d-flex justify-content-center">
                {{$clips->links()}}
            </div>
        @endif
    </div>
</div>
@endsection
