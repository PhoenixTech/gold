<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserSaveRequest;
use App\Models\User;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(User::class)
            ->columns(['name', 'email', 'role', 'mobile'], ['id'])
            ->searchable(['name', 'mobile', 'email'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'log' => ['title' => 'Logs', 'class' => 'btn-outline-secondary', 'icon' => 'ri-file-list-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.users.user-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.users.user-form');
    }

    public function store(UserSaveRequest $request, UserService $userService): JsonResponse|RedirectResponse
    {
        $user = new User;
        $savedUser = $userService->saveUser($user, $request);

        logAdmin(__METHOD__, User::class, $savedUser->id);

        return $this->respondAfterSave($request, $savedUser, __('As you wished created successfully'), 'admin.user.edit');
    }

    public function edit(User|string|int $item, UserService $userService): View
    {
        $user = $this->resolveUser($item);
        $routes = $userService->getAdminRoutesMatrix();

        return view('admin.users.user-form', ['item' => $user, 'routes' => $routes]);
    }

    public function update(UserSaveRequest $request, User|string|int $item, UserService $userService): JsonResponse|RedirectResponse
    {
        $user = $this->resolveUser($item);
        $savedUser = $userService->saveUser($user, $request);

        logAdmin(__METHOD__, User::class, $savedUser->id);

        return $this->respondAfterSave($request, $savedUser, __('As you wished updated successfully'), 'admin.user.edit');
    }

    public function destroy(User|string|int $item): RedirectResponse
    {
        $user = $this->resolveUser($item);

        logAdmin(__METHOD__, User::class, $user->id);
        $user->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(User::onlyTrashed())
            ->columns(['name', 'email', 'role', 'mobile'], ['id', 'deleted_at'])
            ->searchable(['name', 'mobile', 'email'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.users.user-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = User::withTrashed()->where('id', $item)->first()
            ?? User::withTrashed()->where('email', $item)->firstOrFail();

        logAdmin(__METHOD__, User::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            User::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'role' && $subAction !== null) {
                    foreach ($ids as $id) {
                        $user = User::where('id', $id)->first();
                        if ($user) {
                            $user->role = $subAction;
                            $user->syncRoles([strtolower($subAction)]);
                            $user->save();
                        }
                    }

                    return __(':COUNT users role changed to :NEWROLE successfully', [
                        'COUNT' => count($ids),
                        'NEWROLE' => __($subAction),
                    ]);
                }

                return null;
            }
        );
    }

    public function show($item)
    {
        $user = $this->resolveUser($item);
        if ($user && method_exists($user, 'webUrl')) {
            return redirect($user->webUrl());
        }

        return redirect()->route('admin.user.edit', $user->{$user->getRouteKeyName()});
    }

    protected function resolveUser(User|string|int $item): User
    {
        if ($item instanceof User) {
            return $item;
        }

        return User::where('email', $item)->first()
            ?? User::where('id', $item)->firstOrFail();
    }
}
