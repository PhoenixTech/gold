<?php

namespace App\Services;

use App\Models\Access;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Image\Image;

class UserService
{
    public function saveUser(User $user, Request $request): User
    {
        $role = User::normalizeRole($request->input('role'));

        if ($user->role === 'DEVELOPER' && ! auth()->user()?->hasRole('developer')) {
            abort(403);
        }
        if (! auth()->user()?->hasRole('developer') && $role === 'DEVELOPER') {
            abort(403);
        }

        $user->name = $request->input('name');
        if (! config('app.demo')) {
            $user->email = $request->input('email');
            if (trim((string) $request->input('password')) !== '') {
                $user->password = bcrypt($request->input('password'));
            }
        }
        $user->mobile = $request->input('mobile');
        $user->role = $role;
        $user->syncRoles([strtolower((string) $role)]);
        $user->save();

        if ($request->has('acl')) {
            $user->accesses()->delete();
            foreach ($request->input('acl', []) as $route) {
                $a = new Access;
                $a->route = $route;
                $a->user_id = $user->id;
                $a->save();

                $routes = explode('.', (string) $route);
                if (isset($routes[2]) && ($routes[2] === 'store' || $routes[2] === 'update')) {
                    $routes[2] = $routes[2] === 'store' ? 'create' : 'edit';
                    $a2 = new Access;
                    $a2->route = implode('.', $routes);
                    $a2->user_id = $user->id;
                    $a2->save();
                }
            }
        }

        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $name = time().'.'.$avatarFile->getClientOriginalExtension();
            $user->avatar = $name;
            $avatarFile->storeAs('public/users', $name);

            $img = Image::load($avatarFile->getPathname())
                ->optimize()
                ->width(500)
                ->height(500)
                ->crop(500, 500)
                ->format('webp');

            $img->save(storage_path('app/public/users/'.$user->avatar));
            $user->save();
        }

        return $user;
    }

    public function getAdminRoutesMatrix(): array
    {
        $routes = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getAction();
            if (array_key_exists('as', $action)) {
                $routeName = explode('.', (string) $action['as']);
                if (isset($routeName[2]) && $routeName[0] === 'admin') {
                    if (! isset($routes[$routeName[1]])) {
                        $routes[$routeName[1]] = [];
                    }
                    if ($routeName[2] !== 'edit' && $routeName[2] !== 'create') {
                        $routes[$routeName[1]][] = $routeName[2];
                    }
                }
            }
        }

        unset($routes['home'], $routes['user'], $routes['ckeditor'], $routes['area'], $routes['lang'], $routes['gfx']);

        return $routes;
    }
}
