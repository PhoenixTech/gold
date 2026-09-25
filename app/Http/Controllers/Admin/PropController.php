<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\PropSaveRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Prop;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Prop::class)
            ->columns(['name', 'label', 'icon'], ['id'])
            ->searchable(['name', 'label'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.props.prop-list', $tableData);
    }

    public function create(): View
    {
        $cats = Category::all(['id', 'name', 'parent_id']);

        return view('admin.props.prop-form', compact('cats'));
    }

    public function store(PropSaveRequest $request): JsonResponse|RedirectResponse
    {
        $prop = new Prop;
        $this->savePropData($prop, $request);

        logAdmin(__METHOD__, Prop::class, $prop->id);

        return $this->respondAfterSave($request, $prop, __('As you wished created successfully'), 'admin.prop.edit');
    }

    public function edit(Prop|string|int $item): View
    {
        $item = $this->resolveProp($item);
        $cats = Category::all(['id', 'name', 'parent_id']);

        return view('admin.props.prop-form', compact('item', 'cats'));
    }

    public function update(PropSaveRequest $request, Prop|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveProp($item);
        $this->updateProductMetaName($item, $request);
        $this->savePropData($item, $request);

        logAdmin(__METHOD__, Prop::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.prop.edit');
    }

    public function destroy(Prop|string|int $item): RedirectResponse
    {
        $item = $this->resolveProp($item);

        foreach (Product::whereHasMeta($item->name)->get() as $product) {
            $product->removeMeta($item->name);
        }

        logAdmin(__METHOD__, Prop::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Prop::onlyTrashed())
            ->columns(['name', 'label', 'icon'], ['id', 'deleted_at'])
            ->searchable(['name', 'label'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.props.prop-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Prop::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Prop::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Prop::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function sort(): View
    {
        return view('admin.props.prop-sort');
    }

    public function sortSave(Request $request): array
    {
        foreach ($request->input('items', []) as $key => $v) {
            Prop::where('id', $v['id'])->update(['sort' => $key]);
        }

        logAdmin(__METHOD__, static::class, null);

        return ['OK' => true, 'message' => __('As you wished sort saved')];
    }

    protected function resolveProp(Prop|string|int $item): Prop
    {
        if ($item instanceof Prop) {
            return $item;
        }

        return Prop::where('id', $item)->firstOrFail();
    }

    protected function savePropData(Prop $prop, Request $request): void
    {
        $prop->name = $request->input('name');
        $prop->type = $request->input('type');
        $prop->required = $request->input('required');
        $prop->searchable = $request->input('searchable');
        $prop->width = $request->input('width');
        $prop->label = $request->input('label');
        $prop->unit = $request->input('unit');
        $prop->priceable = $request->has('priceable');
        $prop->icon = $request->input('icon');
        $prop->options = $request->filled('options') ? $request->input('options') : [];
        $prop->save();

        if ($request->has('cat')) {
            $prop->categories()->sync((array) $request->input('cat'));
        }
    }

    protected function updateProductMetaName(Prop $prop, Request $request): void
    {
        $newName = $request->input('name');
        if ($newName !== $prop->name && ! empty($newName)) {
            foreach (Product::whereHasMeta($prop->name)->get() as $product) {
                $product->setMeta($newName, $product->getMeta($prop->name));
                $product->removeMeta($prop->name);
            }
        }
    }
}
