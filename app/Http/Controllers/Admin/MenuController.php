<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\MenuSaveRequest;
use App\Models\Item;
use App\Models\Menu;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Menu::class)
            ->columns(['name'], ['id'])
            ->searchable(['name'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.menus.menu-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.menus.menu-form');
    }

    public function store(MenuSaveRequest $request): JsonResponse|RedirectResponse
    {
        $menu = new Menu;
        $this->saveMenuData($menu, $request);

        logAdmin(__METHOD__, Menu::class, $menu->id);

        return $this->respondAfterSave($request, $menu, __('As you wished created successfully'), 'admin.menu.edit');
    }

    public function edit(Menu|string|int $item): View
    {
        $item = $this->resolveMenu($item);

        return view('admin.menus.menu-form', compact('item'));
    }

    public function update(MenuSaveRequest $request, Menu|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveMenu($item);
        $this->saveMenuData($item, $request);

        logAdmin(__METHOD__, Menu::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.menu.edit');
    }

    public function destroy(Menu|string|int $item): RedirectResponse
    {
        $item = $this->resolveMenu($item);

        logAdmin(__METHOD__, Menu::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Menu::onlyTrashed())
            ->columns(['name'], ['id', 'deleted_at'])
            ->searchable(['name'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.menus.menu-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Menu::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Menu::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Menu::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function sort(Menu|string|int $item): View
    {
        $item = $this->resolveMenu($item);

        return view('admin.menus.menu-sort', compact('item'));
    }

    public function sortSave(Request $request): array
    {
        foreach ($request->input('items', []) as $key => $v) {
            Item::where('id', $v['id'])->update(['sort' => $key]);
        }

        logAdmin(__METHOD__, static::class, null);

        return ['OK' => true, 'message' => __('As you wished sort saved')];
    }

    protected function resolveMenu(Menu|string|int $item): Menu
    {
        if ($item instanceof Menu) {
            return $item;
        }

        return Menu::where('id', $item)->firstOrFail();
    }

    protected function saveMenuData(Menu $menu, Request $request): void
    {
        $menu->name = $request->input('name');
        if ($menu->user_id === null) {
            $menu->user_id = auth()->id();
        }
        $menu->save();

        $items = json_decode((string) $request->input('items', '[]'));
        if (is_array($items)) {
            foreach ($items as $item) {
                $i = empty($item->id) ? new Item : Item::where('id', $item->id)->first() ?? new Item;
                $i->user_id = auth()->id();
                $i->menu_id = $menu->id;
                $i->meta = $item->meta ?? null;
                $i->sort = $item->sort ?? 0;
                $i->parent = $item->parent ?? null;
                $i->kind = $item->kind ?? null;
                $i->title = $item->title ?? '';
                $i->menuable_id = $item->menuable_id ?? null;
                $i->menuable_type = $item->menuable_type ?? null;
                $i->save();
            }
        }

        $removed = json_decode((string) $request->input('removed', '[]'), true);
        if (is_array($removed) && count($removed) > 0) {
            Item::whereIn('id', $removed)->delete();
        }
    }
}
