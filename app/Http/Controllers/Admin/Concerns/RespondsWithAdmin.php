<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsWithAdmin
{
    protected function respondAfterSave(Request $request, Model $item, string $message, ?string $redirectRoute = null): JsonResponse|RedirectResponse
    {
        $targetUrl = $redirectRoute !== null
            ? route($redirectRoute, $item->{$item->getRouteKeyName()})
            : getRoute('edit', $item->{$item->getRouteKeyName()});

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'OK' => true,
                'message' => $message,
                'id' => $item->getKey(),
                'data' => modelWithCustomAttrs($item),
                'url' => $targetUrl,
            ]);
        }

        return redirect($targetUrl)->with(['message' => $message]);
    }
}
