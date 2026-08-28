@extends('website.inc.website-layout')

@section('title')
    {{$clip->title}} - {{config('app.name')}}
@endsection

@section('content')
<div class="clip-single-view">
    @include('client.partials.parallax-header', ['title' => $clip->title, 'subtitle' => $clip->subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="clip-player-wrapper mb-4 rounded-4 overflow-hidden bg-dark">
                        {!! $clip->player() !!}
                    </div>
                    <h3 class="fw-bold text-dark mb-2">{{$clip->title}}</h3>
                    @if($clip->subtitle)
                        <p class="text-muted fs-15 mb-3">{{$clip->subtitle}}</p>
                    @endif
                    @if($clip->body)
                        <div class="clip-description border-top pt-3 fs-15 leading-relaxed text-dark">
                            {!! $clip->body !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
