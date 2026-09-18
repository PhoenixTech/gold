<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HandlesAdminFilters
{
    /**
     * Build filtered and sorted query for list views.
     */
    protected function makeSortAndFilter()
    {
        if (! request()->has('sort') || ! in_array(request('sort'), $this->cols)) {
            $query = $this->_MODEL_::orderByDesc('id');
        } else {
            $query = $this->_MODEL_::orderBy(request('sort'), request('sortType', 'asc'));
        }

        foreach (request()->input('filter', []) as $col => $filter) {
            if (is_array($filter)) {
                $cleanFilter = array_filter($filter, fn ($v) => $v !== null && $v !== '');
                if (count($cleanFilter) > 0) {
                    $query->whereIn($col, $cleanFilter);
                }
            } elseif (is_string($filter) && isJson($filter)) {
                $vals = json_decode($filter, true);
                if (is_array($vals)) {
                    $cleanVals = array_filter($vals, fn ($v) => $v !== null && $v !== '');
                    if (count($cleanVals) > 0) {
                        $query->whereIn($col, $cleanVals);
                    }
                } else {
                    $query->where($col, $vals);
                }
            } else {
                if ($filter !== null && $filter !== '') {
                    $query->where($col, $filter);
                }
            }
        }

        $search = trim(request()->input('q', ''));
        if (mb_strlen($search) > 0 && ! empty($this->searchable)) {
            $searchable = $this->searchable;
            $query->where(function ($q) use ($search, $searchable) {
                foreach ($searchable as $key => $col) {
                    if ($key === 0) {
                        $q->where($col, 'LIKE', '%'.$search.'%');
                    } else {
                        $q->orWhere($col, 'LIKE', '%'.$search.'%');
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Compute quick counts for list header tabs without heavy schema lookups.
     */
    protected function getQuickCounts(): array
    {
        $quickCounts = [];

        try {
            $model = new ($this->_MODEL_);
            $allCols = array_merge($this->cols ?? [], $this->extra_cols ?? []);

            $quickCounts['all'] = $this->_MODEL_::count();

            if (in_array('metal_type', $allCols)) {
                $quickCounts['gold'] = $this->_MODEL_::where('metal_type', 'gold')->count();
                $quickCounts['silver'] = $this->_MODEL_::where('metal_type', 'silver')->count();
            }

            if (in_array('min_stock_level', $allCols) && in_array('stock_quantity', $allCols)) {
                $quickCounts['low_stock'] = $this->_MODEL_::where('min_stock_level', '>', 0)
                    ->whereColumn('stock_quantity', '<', 'min_stock_level')
                    ->count();
            }

            if (in_array('buy_price', $allCols) && in_array('price', $allCols)) {
                $quickCounts['below_buy_price'] = $this->_MODEL_::where('buy_price', '>', 0)
                    ->whereColumn('price', '<', 'buy_price')
                    ->count();
            }

            if (in_array('status', $allCols)) {
                $quickCounts['published'] = $this->_MODEL_::whereIn('status', [1, '1', 'published'])->count();
                $quickCounts['draft'] = $this->_MODEL_::whereIn('status', [0, '0', 'draft'])->count();
            }

            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $quickCounts['trashed'] = $this->_MODEL_::onlyTrashed()->count();
            }
        } catch (\Throwable $e) {
            $quickCounts = [];
        }

        return $quickCounts;
    }
}
