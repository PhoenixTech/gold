<div class="simple-single-comment p-3 rounded-3 bg-white border mb-2">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar rounded-circle bg-light border p-1 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="ri-user-3-line text-muted"></i>
            </div>
            <div>
                <strong class="d-block fs-14 text-dark">{{$comment->commentator()['name'] ?? __('Guest')}}</strong>
                @if($comment->commentator_type == \App\Models\User::class)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-11 px-2 py-0.5">{{__("Admin")}}</span>
                @elseif($comment->commentator_type == \App\Models\Customer::class)
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-11 px-2 py-0.5">{{__("Customer")}}</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill fs-11 px-2 py-0.5">{{__("Guest")}}</span>
                @endif
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill comment-reply px-2.5 py-1 fs-12" data-id="{{$comment->id}}">
            <i class="ri-reply-line me-1"></i>
            <span>{{__("Reply")}}</span>
        </button>
    </div>
    <div class="comment-body mt-2.5 fs-14 text-muted leading-relaxed">
        <p class="mb-0">{{$comment->body}}</p>
    </div>
    @if($comment->approved_children && $comment->approved_children->count() > 0)
        <div class="comment-replies ms-4 mt-3 ps-3 border-start">
            @foreach($comment->approved_children as $childComment)
                @include('client.partials.comment-item', ['comment' => $childComment])
            @endforeach
        </div>
    @endif
</div>
