<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\GroupSaveRequest;
use App\Models\Group;
use App\Models\Item;
use App\Models\Setting;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\AdminMediaService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Group::class)
            ->columns(['name', 'subtitle', 'parent_id'], ['id', 'slug', 'image'])
            ->searchable(['name', 'subtitle', 'description'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.groups.group-list', $tableData);
    }

    public function create(): View
    {
        $cats = Group::all();

        return view('admin.groups.group-form', compact('cats'));
    }

    public function store(GroupSaveRequest $request, SlugService $slugService, AdminMediaService $mediaService): JsonResponse|RedirectResponse
    {
        $group = new Group;
        $this->fillGroup($group, $request, $slugService);
        $group->save();

        $mediaService->handleOptimizedImage($request, $group, 'image', 'groups');
        $mediaService->handleOptimizedImage($request, $group, 'bg', 'groups');
        $group->save();

        logAdmin(__METHOD__, Group::class, $group->id);

        return $this->respondAfterSave($request, $group, __('As you wished created successfully'), 'admin.group.edit');
    }

    public function edit(Group|string|int $item): View
    {
        $item = $this->resolveGroup($item);
        $cats = Group::all();

        return view('admin.groups.group-form', compact('item', 'cats'));
    }

    public function update(GroupSaveRequest $request, Group|string|int $item, SlugService $slugService, AdminMediaService $mediaService): JsonResponse|RedirectResponse
    {
        $item = $this->resolveGroup($item);
        $this->fillGroup($item, $request, $slugService);
        $item->save();

        $mediaService->handleOptimizedImage($request, $item, 'image', 'groups');
        $mediaService->handleOptimizedImage($request, $item, 'bg', 'groups');
        $item->save();

        logAdmin(__METHOD__, Group::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.group.edit');
    }

    public function destroy(Group|string|int $item): RedirectResponse
    {
        $item = $this->resolveGroup($item);

        if (Setting::where('type', 'GROUP')->where('raw', $item->id)->exists()) {
            return redirect()->back()->withErrors(__("You can't delete this item while using it in setting."));
        }

        if (Item::where('menuable_type', Group::class)->where('menuable_id', $item->id)->exists()) {
            return redirect()->back()->withErrors(__("You can't delete this item while using it in menu."));
        }

        logAdmin(__METHOD__, Group::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Group::onlyTrashed())
            ->columns(['name', 'subtitle', 'parent_id'], ['id', 'slug', 'image', 'deleted_at'])
            ->searchable(['name', 'subtitle', 'description'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.groups.group-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Group::withTrashed()->where('id', $item)->first()
            ?? Group::withTrashed()->where('slug', $item)->firstOrFail();

        logAdmin(__METHOD__, Group::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Group::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function show($item)
    {
        $group = $this->resolveGroup($item);
        if ($group && method_exists($group, 'webUrl')) {
            return redirect($group->webUrl());
        }

        return redirect()->route('admin.group.index');
    }

    public function sort(): View
    {
        $items = Group::orderBy('sort')->get(['id', 'name', 'parent_id']);

        return view('admin.commons.sort', compact('items'));
    }

    public function sortSave(Request $request): array
    {
        foreach ($request->input('items', []) as $key => $item) {
            Group::where('id', $item['id'])->update([
                'sort' => $key,
                'parent_id' => $item['parentId'] ?? null,
            ]);
        }

        logAdmin(__METHOD__, static::class, null);

        return ['OK' => true, 'message' => __('As you wished sort saved')];
    }

    protected function resolveGroup(Group|string|int $item): Group
    {
        if ($item instanceof Group) {
            return $item;
        }

        return Group::where('slug', $item)->first()
            ?? Group::where('id', $item)->firstOrFail();
    }

    protected function fillGroup(Group $group, Request $request, SlugService $slugService): void
    {
        $group->name = $request->input('name');
        $group->subtitle = $request->input('subtitle');
        $group->description = $request->input('description');
        $group->hide = $request->has('hide');
        $group->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;

        if ($request->filled('canonical')) {
            $group->canonical = $request->input('canonical');
        }

        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $group->name;
        $group->slug = $slugService->makeUnique(Group::class, $titleForSlug, $group->id);
    }
}
