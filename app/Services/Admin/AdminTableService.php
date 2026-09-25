<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class AdminTableService
{
    protected Builder $query;
    protected array $cols = [];
    protected array $extraCols = ['id'];
    protected array $searchable = [];
    protected array $buttons = [];
    protected array $quickCountCallbacks = [];
    protected ?string $modelClass = null;

    public function for(Builder|string $queryOrModel): self
    {
        if (is_string($queryOrModel)) {
            $this->modelClass = $queryOrModel;
            $this->query = $queryOrModel::query();
        } else {
            $this->query = $queryOrModel;
            $this->modelClass = get_class($queryOrModel->getModel());
        }

        return $this;
    }

    public function columns(array $cols, array $extraCols = ['id']): self
    {
        $this->cols = $cols;
        $this->extraCols = $extraCols;

        return $this;
    }

    public function searchable(array $searchable): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function buttons(array $buttons): self
    {
        $this->buttons = $buttons;

        return $this;
    }

    public function withQuickCount(string $key, callable $counter): self
    {
        $this->quickCountCallbacks[$key] = $counter;

        return $this;
    }

    public function build(Request $request): array
    {
        $this->applySorting($request);
        $this->applyFilters($request);
        $this->applySearch($request);

        if (hasRoute('trashed') && ! in_array('deleted_at', $this->extraCols, true)) {
            $this->extraCols[] = 'deleted_at';
        }

        $perPage = (int) config('app.panel.page_count', 15);
        $selectCols = array_values(array_unique(array_merge($this->extraCols, $this->cols)));

        $items = $this->query->paginate($perPage, $selectCols);
        $quickCounts = $this->computeQuickCounts($request);

        return [
            'items' => $items,
            'cols' => $this->cols,
            'buttons' => $this->buttons,
            'quickCounts' => $quickCounts,
        ];
    }

    protected function applySorting(Request $request): void
    {
        $sort = $request->input('sort');
        $sortType = strtolower((string) $request->input('sortType', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! empty($sort) && in_array($sort, $this->cols, true)) {
            $this->query->orderBy($sort, $sortType);
        } else {
            $this->query->orderByDesc('id');
        }
    }

    protected function applyFilters(Request $request): void
    {
        $filters = $request->input('filter', []);
        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $col => $filter) {
            if (is_array($filter)) {
                $cleanFilter = array_filter($filter, fn ($v) => $v !== null && $v !== '');
                if (count($cleanFilter) > 0) {
                    $this->query->whereIn($col, $cleanFilter);
                }
            } elseif (is_string($filter) && isJson($filter)) {
                $vals = json_decode($filter, true);
                if (is_array($vals)) {
                    $cleanVals = array_filter($vals, fn ($v) => $v !== null && $v !== '');
                    if (count($cleanVals) > 0) {
                        $this->query->whereIn($col, $cleanVals);
                    }
                } elseif ($vals !== null && $vals !== '') {
                    $this->query->where($col, $vals);
                }
            } else {
                if ($filter !== null && $filter !== '') {
                    $this->query->where($col, $filter);
                }
            }
        }
    }

    protected function applySearch(Request $request): void
    {
        $search = trim((string) $request->input('q', ''));
        if (mb_strlen($search) === 0 || empty($this->searchable)) {
            return;
        }

        $searchable = $this->searchable;
        $this->query->where(function (Builder $sub) use ($search, $searchable) {
            foreach ($searchable as $index => $col) {
                if ($index === 0) {
                    $sub->where($col, 'LIKE', '%'.$search.'%');
                } else {
                    $sub->orWhere($col, 'LIKE', '%'.$search.'%');
                }
            }
        });
    }

    protected function computeQuickCounts(Request $request): array
    {
        $quickCounts = [];

        if (! $this->modelClass) {
            return $quickCounts;
        }

        try {
            $model = new ($this->modelClass);
            $allCols = array_merge($this->cols, $this->extraCols);

            $quickCounts['all'] = $this->modelClass::count();

            if (in_array('metal_type', $allCols, true)) {
                $quickCounts['gold'] = $this->modelClass::where('metal_type', 'gold')->count();
                $quickCounts['silver'] = $this->modelClass::where('metal_type', 'silver')->count();
            }

            if (in_array('min_stock_level', $allCols, true) && in_array('stock_quantity', $allCols, true)) {
                $quickCounts['low_stock'] = $this->modelClass::where('min_stock_level', '>', 0)
                    ->whereColumn('stock_quantity', '<', 'min_stock_level')
                    ->count();
            }

            if (in_array('buy_price', $allCols, true) && in_array('price', $allCols, true)) {
                $quickCounts['below_buy_price'] = $this->modelClass::where('buy_price', '>', 0)
                    ->whereColumn('price', '<', 'buy_price')
                    ->count();
            }

            if (in_array('status', $allCols, true)) {
                $quickCounts['published'] = $this->modelClass::whereIn('status', [1, '1', 'published'])->count();
                $quickCounts['draft'] = $this->modelClass::whereIn('status', [0, '0', 'draft'])->count();
            }

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $quickCounts['trashed'] = $this->modelClass::onlyTrashed()->count();
            }

            foreach ($this->quickCountCallbacks as $key => $callback) {
                $quickCounts[$key] = $callback($this->modelClass);
            }
        } catch (\Throwable) {
            $quickCounts = [];
        }

        return $quickCounts;
    }
}
