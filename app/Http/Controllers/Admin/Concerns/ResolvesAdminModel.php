<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesAdminModel
{
    protected function resolveModel(string $modelClass, Model|string|int $item, bool $withTrashed = false): Model
    {
        if ($item instanceof $modelClass) {
            return $item;
        }

        $dummy = new $modelClass;
        $routeKey = $dummy->getRouteKeyName() ?? 'id';
        $query = $withTrashed
            ? $modelClass::withTrashed()
            : $modelClass::query();

        if ($routeKey !== 'id') {
            $found = (clone $query)->where($routeKey, $item)->first();
            if ($found) {
                return $found;
            }
        }

        if (is_numeric($item)) {
            $found = (clone $query)->find($item);
            if ($found) {
                return $found;
            }
        }

        return $query->where($dummy->getKeyName() ?? 'id', $item)->firstOrFail();
    }
}
