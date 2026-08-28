@extends('website.inc.website-layout')

@section('title')
    {{$title ?? __('Downloads & Files')}} - {{config('app.name')}}
@endsection

@section('content')
<div class="attachments-page-view">
    @include('client.partials.parallax-header', ['title' => $title ?? __('Downloads & Files'), 'subtitle' => $subtitle ?? ''])
    <div class="{{gfx()['container']}} py-5">
        <div class="row g-4">
            @if(isset($attachments) && $attachments->count() > 0)
                @foreach($attachments as $attach)
                    <div class="col-12 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center justify-content-between gap-3 bg-white">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="ri-file-download-line fs-20"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1 fs-14">{{$attach->title}}</h6>
                                    <small class="text-muted fs-12">{{$attach->downloads}} {{__('downloads')}}</small>
                                </div>
                            </div>
                            <a href="{{$attach->dlUrl()}}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                                <i class="ri-download-2-line"></i>
                                <span>{{__('Download')}}</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5 text-muted">
                    <i class="ri-file-download-line fs-1 d-block mb-2"></i>
                    <p>{{__('No files available for download.')}}</p>
                </div>
            @endif
        </div>
        @if(isset($attachments) && method_exists($attachments, 'links'))
            <div class="mt-4 d-flex justify-content-center">
                {{$attachments->links()}}
            </div>
        @endif
    </div>
</div>
@endsection
