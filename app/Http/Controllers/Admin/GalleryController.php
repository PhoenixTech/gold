<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\GallerySaveRequest;
use App\Models\Gallery;
use App\Models\Image;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Gallery::class)
            ->columns(['title', 'status'], ['id', 'slug'])
            ->searchable(['title', 'description'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.galleries.gallery-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.galleries.gallery-form');
    }

    public function store(GallerySaveRequest $request, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $gallery = new Gallery;
        $this->saveGalleryData($gallery, $request, $slugService);

        logAdmin(__METHOD__, Gallery::class, $gallery->id);

        return $this->respondAfterSave($request, $gallery, __('As you wished created successfully'), 'admin.gallery.edit');
    }

    public function edit(Gallery|string|int $item): View
    {
        $item = $this->resolveGallery($item);

        return view('admin.galleries.gallery-form', compact('item'));
    }

    public function update(GallerySaveRequest $request, Gallery|string|int $item, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $item = $this->resolveGallery($item);
        $this->saveGalleryData($item, $request, $slugService);

        logAdmin(__METHOD__, Gallery::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.gallery.edit');
    }

    public function destroy(Gallery|string|int $item): RedirectResponse
    {
        $item = $this->resolveGallery($item);

        logAdmin(__METHOD__, Gallery::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Gallery::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'publish') {
                    Gallery::whereIn('id', $ids)->update(['status' => 1]);

                    return __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'draft') {
                    Gallery::whereIn('id', $ids)->update(['status' => 0]);

                    return __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    public function updateTitle(Request $request): RedirectResponse
    {
        foreach ($request->input('titles', []) as $k => $title) {
            Image::where('id', $k)->update(['title' => $title]);
        }

        return redirect()->back()->with(['message' => __('Titles updated')]);
    }

    public function show($item)
    {
        $gallery = $this->resolveGallery($item);
        if ($gallery && method_exists($gallery, 'webUrl')) {
            return redirect($gallery->webUrl());
        }

        return redirect()->route('admin.gallery.index');
    }

    protected function resolveGallery(Gallery|string|int $item): Gallery
    {
        return $this->resolveModel(Gallery::class, $item);
    }

    protected function saveGalleryData(Gallery $gallery, Request $request, SlugService $slugService): void
    {
        $gallery->title = $request->input('title');
        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $gallery->title;
        $gallery->slug = $slugService->makeUnique(Gallery::class, $titleForSlug, $gallery->id);
        $gallery->description = $request->input('description');
        $gallery->status = $request->input('status');
        $gallery->user_id = auth()->id();
        $gallery->save();

        if ($request->hasFile('image')) {
            $gallery->media()->delete();
            $gallery->addMedia($request->file('image'))
                ->preservingOriginal()
                ->toMediaCollection();
            $gallery->save();
        }
    }
}
