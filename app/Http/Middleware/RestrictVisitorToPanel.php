<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictVisitorToPanel
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $user instanceof \App\Models\User || ! $user->isVisitor()) {
            return $next($request);
        }

        if ($request->routeIs(
            'admin.home',
            'home',
            'admin.logout',
            'logout',
            'admin.shop-visit.step-one',
            'admin.shop-visit.step-two'
        )) {
            return $next($request);
        }

        return redirect()->route('admin.home');
    }
}
