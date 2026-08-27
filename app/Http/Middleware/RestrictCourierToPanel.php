<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictCourierToPanel
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCourier()) {
            return $next($request);
        }

        if ($request->routeIs(
            'admin.home',
            'home',
            'admin.logout',
            'logout',
            'admin.delivery.*'
        )) {
            return $next($request);
        }

        return redirect()->route('admin.delivery.index');
    }
}
