<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategorySaveRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\CategoryService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Category::class)
            ->columns(['name', 'code', 'subtitle', 'parent_id'], ['id', 'slug', 'image'])
            ->searchable(['name', 'subtitle', 'description'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.categories.category-list', $tableData);
    }

    public function create(): View
    {
        $cats = Category::all();

        return view('admin.categories.category-form', compact('cats'));
    }

    public function store(CategorySaveRequest $request, CategoryService $categoryService, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $category = new Category;
        $this->fillCategory($category, $request, $slugService);
        $category->save();

        $categoryService->handleUploads($category, $request);
        $category->save();

        logAdmin(__METHOD__, Category::class, $category->id);

        return $this->respondAfterSave($request, $category, __('As you wished created successfully'), 'admin.category.edit');
    }

    public function edit(Category|string|int $item): View
    {
        $item = $this->resolveCategory($item);
        $cats = Category::all();

        return view('admin.categories.category-form', compact('item', 'cats'));
    }

    public function update(CategorySaveRequest $request, Category|string|int $item, CategoryService $categoryService, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $item = $this->resolveCategory($item);
        $this->fillCategory($item, $request, $slugService);
        $item->save();

        $categoryService->handleUploads($item, $request);
        $item->save();

        logAdmin(__METHOD__, Category::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.category.edit');
    }

    public function destroy(Category|string|int $item): RedirectResponse
    {
        $item = $this->resolveCategory($item);

        if (Setting::where('type', 'CATEGORY')->where('raw', $item->id)->exists()) {
            return redirect()->back()->withErrors(__("You can't delete this item while using it in setting."));
        }

        if (Item::where('menuable_type', Category::class)->where('menuable_id', $item->id)->exists()) {
            return redirect()->back()->withErrors(__("You can't delete this item while using it in menu."));
        }

        logAdmin(__METHOD__, Category::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Category::onlyTrashed())
            ->columns(['name', 'code', 'subtitle', 'parent_id'], ['id', 'slug', 'image', 'deleted_at'])
            ->searchable(['name', 'subtitle', 'description'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.categories.category-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Category::withTrashed()->where('id', $item)->first()
            ?? Category::withTrashed()->where('slug', $item)->firstOrFail();

        logAdmin(__METHOD__, Category::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Category::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function show($item)
    {
        $category = Category::where('id', $item)->orWhere('slug', $item)->first();
        if ($category && method_exists($category, 'webUrl')) {
            return redirect($category->webUrl());
        }

        return redirect()->route('admin.category.index');
    }

    public function sort(): View
    {
        $items = Category::orderBy('sort')->get(['id', 'name', 'parent_id']);

        return view('admin.commons.sort', compact('items'));
    }

    public function sortSave(Request $request): array
    {
        foreach ($request->input('items', []) as $key => $item) {
            Category::where('id', $item['id'])->update([
                'sort' => $key,
                'parent_id' => $item['parentId'] ?? null,
            ]);
        }

        logAdmin(__METHOD__, static::class, null);

        return ['OK' => true, 'message' => __('As you wished sort saved')];
    }

    public function omg(): View
    {
        return view('admin.categories.omg');
    }

    public function omgSave(Request $request, CategoryService $categoryService): string
    {
        $request->validate([
            'table' => ['required', 'string', 'min:10'],
        ]);

        $categoryService->importNestedFromHtml($request->input('table'));

        return __('It saved, now just God can help you :)');
    }

    protected function resolveCategory(Category|string|int $item): Category
    {
        if ($item instanceof Category) {
            return $item;
        }

        return Category::where('slug', $item)->first()
            ?? Category::where('id', $item)->firstOrFail();
    }

    protected function fillCategory(Category $category, Request $request, SlugService $slugService): void
    {
        $category->name = $request->input('name');
        $category->code = $request->input('code') ?: null;
        $category->subtitle = $request->input('subtitle');
        $category->color = $request->input('color') ?: '#000000';
        $category->bg_color = $request->input('bg_color') ?: '#ffffff';
        $category->icon = $request->input('icon');
        $category->description = $request->input('description');
        $category->hide = $request->has('hide');
        $category->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;

        if ($request->filled('canonical')) {
            $category->canonical = $request->input('canonical');
        }

        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $category->name;
        $category->slug = $slugService->makeUnique(Category::class, $titleForSlug, $category->id);
    }
}
