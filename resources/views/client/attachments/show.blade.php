@extends('website.inc.website-layout')

@section('title')
    {{$attachment->title}} - {{config('app.name')}}
@endsection

@section('content')
<div class="attachment-single-view">
    @include('client.partials.parallax-header', ['title' => $attachment->title, 'subtitle' => $attachment->subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                    <i class="ri-file-download-line fs-1 text-primary mb-3"></i>
                    <h3 class="fw-bold text-dark mb-2">{{$attachment->title}}</h3>
                    <p class="text-muted fs-14 mb-4">{{$attachment->description}}</p>
                    <div>
                        <a href="{{$attachment->dlUrl()}}" class="btn btn-primary btn-lg rounded-pill px-5 py-2.5 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="ri-download-cloud-2-line fs-20"></i>
                            <span>{{__('Download File')}}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
