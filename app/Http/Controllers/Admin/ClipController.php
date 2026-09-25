<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClipSaveRequest;
use App\Models\Clip;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\AdminMediaService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClipController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Clip::class)
            ->columns(['title', 'status'], ['id', 'slug', 'cover'])
            ->searchable(['title', 'body'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.clips.clip-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.clips.clip-form');
    }

    public function store(ClipSaveRequest $request, SlugService $slugService, AdminMediaService $mediaService): JsonResponse|RedirectResponse
    {
        $clip = new Clip;
        $this->saveClipData($clip, $request, $slugService, $mediaService);

        logAdmin(__METHOD__, Clip::class, $clip->id);

        return $this->respondAfterSave($request, $clip, __('As you wished created successfully'), 'admin.clip.edit');
    }

    public function edit(Clip|string|int $item): View
    {
        $item = $this->resolveClip($item);

        return view('admin.clips.clip-form', compact('item'));
    }

    public function update(ClipSaveRequest $request, Clip|string|int $item, SlugService $slugService, AdminMediaService $mediaService): JsonResponse|RedirectResponse
    {
        $item = $this->resolveClip($item);
        $this->saveClipData($item, $request, $slugService, $mediaService);

        logAdmin(__METHOD__, Clip::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.clip.edit');
    }

    public function destroy(Clip|string|int $item): RedirectResponse
    {
        $item = $this->resolveClip($item);

        logAdmin(__METHOD__, Clip::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Clip::onlyTrashed())
            ->columns(['title', 'status'], ['id', 'slug', 'cover', 'deleted_at'])
            ->searchable(['title', 'body'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.clips.clip-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Clip::withTrashed()->where('id', $item)->first()
            ?? Clip::withTrashed()->where('slug', $item)->firstOrFail();

        logAdmin(__METHOD__, Clip::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Clip::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'publish') {
                    Clip::whereIn('id', $ids)->update(['status' => 1]);

                    return __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'draft') {
                    Clip::whereIn('id', $ids)->update(['status' => 0]);

                    return __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    public function show($item)
    {
        $clip = $this->resolveClip($item);
        if ($clip && method_exists($clip, 'webUrl')) {
            return redirect($clip->webUrl());
        }

        return redirect()->route('admin.clip.index');
    }

    protected function resolveClip(Clip|string|int $item): Clip
    {
        if ($item instanceof Clip) {
            return $item;
        }

        return Clip::where('slug', $item)->first()
            ?? Clip::where('id', $item)->firstOrFail();
    }

    protected function saveClipData(Clip $clip, Request $request, SlugService $slugService, AdminMediaService $mediaService): void
    {
        $clip->title = $request->input('title');
        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $clip->title;
        $clip->slug = $slugService->makeUnique(Clip::class, $titleForSlug, $clip->id);
        $clip->body = $request->input('body');
        $clip->user_id = auth()->id();
        $clip->status = $request->input('status');

        if ($request->hasFile('cover')) {
            $mediaService->handleOptimizedImage($request, $clip, 'cover', 'clips');
        }

        if ($request->hasFile('clip')) {
            $clipFile = $request->file('clip');
            $name = time().'-'.$clipFile->getClientOriginalName();
            $clipFile->storeAs('public/clips', $name);
            $clip->file = $name;
        }

        $clip->save();

        if ($request->filled('tags')) {
            $tags = array_filter(explode(',,', (string) $request->input('tags')));
            if (count($tags) > 0) {
                $clip->syncTags($tags);
            }
        }
    }
}
