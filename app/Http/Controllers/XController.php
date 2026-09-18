<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesAdminFilters;
use App\Http\Controllers\Traits\HandlesAdminSlugs;
use App\Http\Controllers\Traits\HandlesAdminUploads;
use App\Http\Requests\UserSaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class XController extends Controller
{
    use HandlesAdminFilters;
    use HandlesAdminSlugs;
    use HandlesAdminUploads;

    protected $_MODEL_ = User::class;

    protected $SAVE_REQUEST = UserSaveRequest::class;

    protected $cols = [];

    protected $extra_cols = ['id'];

    protected $listView = 'admin.users.user-list';

    protected $formView = 'admin.users.user-form';

    protected $searchable = [];

    protected $buttons = [
        'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
        'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
    ];

    public function __construct($model = null, $request = null)
    {
        if ($model !== null) {
            $this->_MODEL_ = $model;
        }
        if ($request !== null) {
            $this->SAVE_REQUEST = $request;
        }
    }

    /**
     * Child classes should persist and return the model item.
     */
    public function save($item, $request)
    {
        return $item;
    }

    // =========================================================================
    // Core List & Resource Actions
    // =========================================================================

    public function index()
    {
        $query = $this->makeSortAndFilter();

        return $this->showList($query);
    }

    public function store(Request $request)
    {
        $saveRequest = $this->resolveSaveRequest($request);
        $item = new ($this->_MODEL_)();
        $savedItem = $this->save($item, $saveRequest);
        logAdmin(__METHOD__, $this->_MODEL_, $savedItem->id);

        return $this->respondAfterSave($savedItem, __('As you wished created successfully'));
    }

    public function show($item)
    {
        $target = $this->resolveItem($item);
        if ($target && method_exists($target, 'webUrl')) {
            return redirect($target->webUrl());
        }
    }

    public function trashed()
    {
        $query = $this->makeSortAndFilter()->onlyTrashed();

        return $this->showList($query);
    }

    public function bulk(Request $request)
    {
        $data = explode('.', (string) $request->input('action'));
        $action = $data[0];
        $ids = (array) $request->input('id', []);

        switch ($action) {
            case 'delete':
                $this->_MODEL_::destroy($ids);
                $msg = __(':COUNT items deleted successfully', ['COUNT' => count($ids)]);
                break;

            case 'restore':
                foreach ($ids as $id) {
                    $this->_MODEL_::withTrashed()->find($id)?->restore();
                }
                $msg = __(':COUNT items restored successfully', ['COUNT' => count($ids)]);
                break;

            default:
                $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $action]);
        }

        return $this->do_bulk($msg, $action, $ids);
    }

    // =========================================================================
    // CRUD Handlers (Invoked directly or through fallback __call)
    // =========================================================================

    public function bringUp(Request $request, $item)
    {
        $target = $this->resolveItem($item);
        $saveRequest = $this->resolveSaveRequest($request);
        $savedItem = $this->save($target, $saveRequest);
        logAdmin(__METHOD__, $this->_MODEL_, $savedItem->id);

        return $this->respondAfterSave($savedItem, __('As you wished updated successfully'));
    }

    public function delete($item)
    {
        $target = $this->resolveItem($item);
        if ($target) {
            logAdmin(__METHOD__, $this->_MODEL_, $target->id);
            $target->delete();
        }

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function restoreing($item)
    {
        if ($item instanceof Model) {
            $target = $item;
        } else {
            $dummy = new ($this->_MODEL_);
            $routeKey = $dummy->getRouteKeyName();
            $target = $this->_MODEL_::withTrashed()->where($routeKey, $item)->first()
                ?? $this->_MODEL_::withTrashed()->find($item);
        }

        if ($target) {
            logAdmin(__METHOD__, $this->_MODEL_, $target->id);
            $target->restore();
        }

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    // =========================================================================
    // Dynamic Fallback for standard methods (create, edit, update, destroy, restore)
    // Allows child controllers to omit them without signature conflict errors.
    // =========================================================================

    public function __call($method, $parameters)
    {
        switch ($method) {
            case 'create':
                return view($this->formView);

            case 'edit':
                $item = $this->resolveItem($parameters[0] ?? null);

                return view($this->formView, compact('item'));

            case 'update':
                return $this->bringUp($parameters[0], $parameters[1]);

            case 'destroy':
                return $this->delete($parameters[0]);

            case 'restore':
                return $this->restoreing($parameters[0]);
        }

        return parent::__call($method, $parameters);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function do_bulk($msg, $action, $ids)
    {
        logAdminBatch(__METHOD__.'.'.$action, $this->_MODEL_, $ids);

        return redirect()->back()->with(['message' => $msg]);
    }

    protected function showList($query)
    {
        if (hasRoute('trashed')) {
            $this->extra_cols[] = 'deleted_at';
        }

        $quickCounts = $this->getQuickCounts();

        $items = $query->paginate(
            config('app.panel.page_count', 15),
            array_merge($this->extra_cols, $this->cols)
        );

        $cols = $this->cols;
        $buttons = $this->buttons;

        return view($this->listView, compact('items', 'cols', 'buttons', 'quickCounts'));
    }

    protected function resolveItem($item)
    {
        if ($item instanceof Model) {
            return $item;
        }

        $dummy = new ($this->_MODEL_);
        $routeKey = $dummy->getRouteKeyName();

        return $this->_MODEL_::where($routeKey, $item)->first()
            ?? $this->_MODEL_::find($item);
    }

    protected function resolveSaveRequest(Request $request): Request
    {
        if ($this->SAVE_REQUEST && class_exists($this->SAVE_REQUEST)) {
            return app()->make($this->SAVE_REQUEST)->merge($request->all());
        }

        return $request;
    }

    protected function respondAfterSave($item, string $message)
    {
        if (request()->ajax()) {
            return [
                'OK' => true,
                'message' => $message,
                'id' => $item->id,
                'data' => modelWithCustomAttrs($item),
                'url' => getRoute('edit', $item->{$item->getRouteKeyName()}),
            ];
        }

        return redirect(getRoute('edit', $item->{$item->getRouteKeyName()}))
            ->with(['message' => $message]);
    }
}
