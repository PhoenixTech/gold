<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommentSaveRequest;
use App\Models\Comment;
use App\Models\User;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Comment::class)
            ->columns(['*'], ['id'])
            ->searchable(['body', 'name', 'email', 'ip'])
            ->buttons([])
            ->build($request);

        return view('admin.comments.comment-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.comments.comment-form');
    }

    public function store(CommentSaveRequest $request): JsonResponse|RedirectResponse
    {
        $comment = new Comment;
        $comment->fill($request->validated());
        $comment->save();

        logAdmin(__METHOD__, Comment::class, $comment->id);

        return $this->respondAfterSave($request, $comment, __('As you wished created successfully'), 'admin.comment.edit');
    }

    public function edit(Comment|string|int $item): View
    {
        $item = $this->resolveComment($item);

        return view('admin.comments.comment-form', compact('item'));
    }

    public function update(Request $request, Comment|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveComment($item);
        $item->fill($request->all());
        $item->save();

        logAdmin(__METHOD__, Comment::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.comment.edit');
    }

    public function destroy(Comment|string|int $item): RedirectResponse
    {
        $item = $this->resolveComment($item);

        logAdmin(__METHOD__, Comment::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function status(Comment|string|int $item, $status): RedirectResponse
    {
        $item = $this->resolveComment($item);
        $item->status = $status;
        $item->save();

        $statuses = [
            -1 => __('rejected'),
            0 => __('pending'),
            1 => __('approved'),
        ];

        return redirect()->back()->with(['message' => __('Comment :STATUS', ['STATUS' => $statuses[$status] ?? $status])]);
    }

    public function reply(Comment|string|int $item): View
    {
        $item = $this->resolveComment($item);

        return view('admin.comments.comment-reply', compact('item'));
    }

    public function replying(Comment|string|int $item): RedirectResponse
    {
        $item = $this->resolveComment($item);

        $reply = new Comment;
        $reply->ip = request()->ip();
        $reply->commentator_type = User::class;
        $reply->commentator_id = auth()->id();
        $reply->commentable_type = $item->commentable_type;
        $reply->commentable_id = $item->commentable_id;
        $reply->parent_id = $item->id;
        $reply->status = 1;
        $reply->body = request()->input('body');
        $reply->save();

        logAdmin(__METHOD__, Comment::class, $reply->id);

        return redirect()->route('admin.comment.index')->with(['message' => __('Comment replay')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Comment::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'status' && $subAction !== null) {
                    Comment::whereIn('id', $ids)->update(['status' => $subAction]);

                    return __(':COUNT items changed status successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    protected function resolveComment(Comment|string|int $item): Comment
    {
        return $this->resolveModel(Comment::class, $item);
    }
}
