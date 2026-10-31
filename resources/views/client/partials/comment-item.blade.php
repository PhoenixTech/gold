<div class="comment-card p-3 rounded-3 bg-white border mb-2 shadow-xs">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar rounded-circle bg-light border p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;" aria-hidden="true">
                <i class="ri-user-3-line text-muted"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <strong class="fs-13 text-main">{{$comment->commentator()['name'] ?? __('Guest')}}</strong>
                    @if($comment->commentator_type == \App\Models\User::class)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-2 fs-11 px-2 py-0.5">{{__("Admin")}}</span>
                    @elseif($comment->commentator_type == \App\Models\Customer::class)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-2 fs-11 px-2 py-0.5">{{__("Customer")}}</span>
                    @endif
                </div>
                <time datetime="{{$comment->created_at->toIso8601String()}}" class="text-muted fs-11 fa-num d-block">
                    {{$comment->created_at->ldate('Y/m/d')}}
                </time>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-2 comment-reply px-2.5 py-1 fs-12 d-inline-flex align-items-center gap-1" data-id="{{$comment->id}}" data-name="{{$comment->commentator()['name'] ?? __('Guest')}}" aria-label="{{__('Reply')}} {{ $comment->commentator()['name'] ?? __('Guest') }}" style="min-height: 34px;">
            <i class="ri-reply-line" aria-hidden="true"></i>
            <span>{{__("Reply")}}</span>
        </button>
    </div>
    <div class="comment-body fs-13 text-body leading-relaxed">
        <p class="mb-0">{{$comment->body}}</p>
    </div>
    @if($comment->approved_children && $comment->approved_children->count() > 0)
        <div class="comment-replies ms-3 ms-md-4 mt-2.5 ps-2.5 ps-md-3 border-start border-2 border-light-subtle d-flex flex-column gap-2">
            @foreach($comment->approved_children as $childComment)
                @include('client.partials.comment-item', ['comment' => $childComment])
            @endforeach
        </div>
    @endif
</div>
