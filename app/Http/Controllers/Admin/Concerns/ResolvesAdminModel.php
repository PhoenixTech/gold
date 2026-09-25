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

        $query = $withTrashed
            ? $modelClass::withTrashed()
            : $modelClass::query();

        if (is_numeric($item)) {
            $found = $query->find($item);
            if ($found) {
                return $found;
            }
        }

        $dummy = new $modelClass;
        $routeKey = $dummy->getRouteKeyName() ?? 'id';

        if ($routeKey !== 'id') {
            $found = $query->where($routeKey, $item)->first();
            if ($found) {
                return $found;
            }
        }

        return $query->where($dummy->getKeyName() ?? 'id', $item)->firstOrFail();
    }
}
