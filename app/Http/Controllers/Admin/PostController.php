<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostSaveRequest;
use App\Models\Group;
use App\Models\Post;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    use RespondsWithAdmin;
    use ResolvesAdminModel;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Post::class)
            ->columns(['title', 'hash', 'status'], ['id', 'slug'])
            ->searchable(['title', 'subtitle', 'body'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
                'group' => ['title' => 'Edit group', 'class' => 'btn-outline-info edit-group-btn', 'icon' => 'ri-list-check-3'],
            ])
            ->build($request);

        return view('admin.posts.post-list', $tableData);
    }

    public function create(): View
    {
        $cats = Group::all(['name', 'id', 'parent_id']);

        return view('admin.posts.post-form', compact('cats'));
    }

    public function store(PostSaveRequest $request, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $post = new Post;
        $this->savePostData($post, $request, $slugService);

        logAdmin(__METHOD__, Post::class, $post->id);

        return $this->respondAfterSave($request, $post, __('As you wished created successfully'), 'admin.post.edit');
    }

    public function edit(Post|string|int $item): View
    {
        $item = $this->resolvePost($item);
        $cats = Group::all(['name', 'id', 'parent_id']);

        return view('admin.posts.post-form', compact('item', 'cats'));
    }

    public function update(PostSaveRequest $request, Post|string|int $item, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $item = $this->resolvePost($item);
        $this->savePostData($item, $request, $slugService);

        logAdmin(__METHOD__, Post::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.post.edit');
    }

    public function destroy(Post|string|int $item): RedirectResponse
    {
        $item = $this->resolvePost($item);

        logAdmin(__METHOD__, Post::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Post::onlyTrashed())
            ->columns(['title', 'hash', 'status'], ['id', 'slug', 'deleted_at'])
            ->searchable(['title', 'subtitle', 'body'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.posts.post-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Post::withTrashed()->where('id', $item)->first()
            ?? Post::withTrashed()->where('slug', $item)->firstOrFail();

        logAdmin(__METHOD__, Post::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Post::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'publish') {
                    Post::whereIn('id', $ids)->update(['status' => 1]);

                    return __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'draft') {
                    Post::whereIn('id', $ids)->update(['status' => 0]);

                    return __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    public function show($item)
    {
        $post = $this->resolvePost($item);
        if ($post && method_exists($post, 'webUrl')) {
            return redirect($post->webUrl());
        }

        return redirect()->route('admin.post.index');
    }

    public function groupEdit($id): View
    {
        $post = Post::findOrFail($id);
        $groups = Group::all(['id', 'name', 'parent_id']);

        return view('admin.posts.group-edit', compact('post', 'groups'));
    }

    public function groupSave(Post|string|int $item, Request $request): array|RedirectResponse
    {
        $post = $this->resolvePost($item);
        $post->groups()->sync((array) $request->input('cat'));

        logAdmin(__METHOD__, static::class, $post->id);

        if ($request->ajax()) {
            return ['OK' => true, 'message' => __('Groups saved successfully')];
        }

        return redirect()->back()->with(['message' => __('Groups saved successfully')]);
    }

    protected function resolvePost(Post|string|int $item): Post
    {
        return $this->resolveModel(Post::class, $item);
    }

    protected function savePostData(Post $post, Request $request, SlugService $slugService): void
    {
        $post->title = $request->input('title');
        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $post->title;
        $post->slug = $slugService->makeUnique(Post::class, $titleForSlug, $post->id);
        $post->body = $request->input('body');
        $post->subtitle = $request->input('subtitle');
        $post->status = $request->input('status');
        $post->group_id = $request->input('group_id');
        $post->user_id = auth()->id();
        $post->is_pinned = $request->has('is_pin');
        $post->table_of_contents = $request->has('table_of_contents');
        $post->icon = $request->input('icon');
        $post->keyword = $request->input('keyword');

        if ($request->filled('canonical')) {
            $post->canonical = $request->input('canonical');
        }

        if ($post->hash === null) {
            $post->hash = date('Ym').str_pad(dechex(crc32($post->slug)), 8, '0', STR_PAD_LEFT);
        }

        $post->save();

        if ($request->has('cat')) {
            $post->groups()->sync((array) $request->input('cat'));
        }

        if ($request->filled('tags')) {
            $tags = array_filter(explode(',,', (string) $request->input('tags')));
            if (count($tags) > 0) {
                $post->syncTags($tags);
            }
        }

        if ($request->hasFile('image')) {
            $post->media()->delete();
            $post->addMedia($request->file('image'))
                ->preservingOriginal()
                ->toMediaCollection();
        }
    }
}
