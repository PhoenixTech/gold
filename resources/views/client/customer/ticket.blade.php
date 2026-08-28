@extends('website.inc.website-layout')

@section('title')
    {{$ticket->title}} - {{config('app.name')}}
@endsection

@section('content')
<div class="ticket-view-page py-5">
    <div class="{{gfx()['container']}}" id="ticket-content">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <h4 class="fw-bold text-dark mb-0">
                <i class="ri-customer-service-2-line text-primary me-1"></i>
                {{$ticket->title}}
            </h4>
            <a href="{{route('client.profile')}}#tickets" class="btn btn-outline-primary rounded-pill btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                <i class="ri-arrow-go-back-line"></i>
                <span>{{__("Back to profile")}}</span>
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="ticket-messages d-flex flex-column gap-3 mb-4">
                <div class="t-message p-3 rounded-3 bg-light border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong class="text-primary">{{__("You")}}</strong>
                        <span class="text-muted fs-12">{{$ticket->created_at->format('Y-m-d H:i')}}</span>
                    </div>
                    <div class="fs-14 text-dark leading-relaxed">{{$ticket->body}}</div>
                </div>

                @if($ticket->answer != null)
                    <div class="t-answer p-3 rounded-3 bg-primary-subtle border border-primary-subtle ms-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-primary">{{$ticket->user->name ?? __('Support Agent')}}</strong>
                            <span class="text-muted fs-12">{{$ticket->created_at->format('Y-m-d H:i')}}</span>
                        </div>
                        <div class="fs-14 text-dark leading-relaxed">{{$ticket->answer}}</div>
                    </div>
                @endif

                @foreach($ticket->subTickets as $t)
                    <div class="t-message p-3 rounded-3 bg-light border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-primary">{{__("You")}}</strong>
                            <span class="text-muted fs-12">{{$t->created_at->format('Y-m-d H:i')}}</span>
                        </div>
                        <div class="fs-14 text-dark leading-relaxed">{{$t->body}}</div>
                    </div>

                    @if($t->answer != null)
                        <div class="t-answer p-3 rounded-3 bg-primary-subtle border border-primary-subtle ms-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong class="text-primary">{{$t->user->name ?? __('Support Agent')}}</strong>
                                <span class="text-muted fs-12">{{$t->updated_at->format('Y-m-d H:i')}}</span>
                            </div>
                            <div class="fs-14 text-dark leading-relaxed">{{$t->answer}}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Reply Form -->
            <div class="reply-form-wrapper border-top pt-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-1.5">
                    <i class="ri-reply-line text-primary"></i>
                    <span>{{__("Send your reply")}}</span>
                </h6>
                <form action="{{ route('client.ticket.answer', $ticket->id) }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <textarea rows="4" name="body" class="form-control rounded-3" placeholder="{{__('Write your answer here...')}}" required>{{old('body')}}</textarea>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="ri-send-plane-2-line"></i>
                            <span>{{__("Send answer")}}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
