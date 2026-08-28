@extends('website.inc.website-layout')

@section('title')
    {{$title ?? __('Galleries')}} - {{config('app.name')}}
@endsection

@section('content')
<div class="galleries-page-view">
    @include('client.partials.parallax-header', ['title' => $title ?? __('Galleries'), 'subtitle' => $subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row g-4">
            @if(isset($galleries) && $galleries->count() > 0)
                @foreach($galleries as $gallery)
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{$gallery->webUrl()}}" class="card border-0 shadow-sm rounded-4 overflow-hidden text-decoration-none d-block transition-all">
                            <div class="position-relative" style="aspect-ratio: 16/10;">
                                <img src="{{$gallery->imgUrl()}}" alt="{{$gallery->title}}" class="w-100 h-100 object-fit-cover" loading="lazy">
                            </div>
                            <div class="card-body text-center p-3">
                                <h5 class="fw-bold text-dark mb-0 fs-16">{{$gallery->title}}</h5>
                            </div>
                        </a>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5 text-muted">
                    <i class="ri-image-line fs-1 d-block mb-2"></i>
                    <p>{{__('No galleries found.')}}</p>
                </div>
            @endif
        </div>
        @if(isset($galleries) && method_exists($galleries, 'links'))
            <div class="mt-4 d-flex justify-content-center">
                {{$galleries->links()}}
            </div>
        @endif
    </div>
</div>
@endsection
