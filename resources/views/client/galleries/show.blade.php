@extends('website.inc.website-layout')

@section('title')
    {{$gallery->title}} - {{config('app.name')}}
@endsection

@section('content')
<div class="gallery-single-view">
    @include('client.partials.parallax-header', ['title' => $gallery->title, 'subtitle' => $gallery->subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row g-4">
            @if($gallery->images->count() > 0)
                @foreach($gallery->images as $image)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a class="light-box card border-0 shadow-sm rounded-4 overflow-hidden d-block" data-toggle="lightbox" data-gallery="{{$gallery->slug}}" href="{{$image->imgOriginalUrl()}}">
                            <img src="{{$image->imgurl()}}" class="img-fluid w-100 object-fit-cover rounded-4" style="aspect-ratio: 1/1;" alt="{{$image->title}}" title="{{$image->title}}" loading="lazy">
                        </a>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5 text-muted">
                    <i class="ri-image-line fs-1 d-block mb-2"></i>
                    <p>{{__('No images in this gallery.')}}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
