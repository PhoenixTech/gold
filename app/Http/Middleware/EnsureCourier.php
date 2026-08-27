<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourier
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isCourier()) {
            abort(403, __('You don\'t have access this action'));
        }

        return $next($request);
    }
}
