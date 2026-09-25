<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class AdminTableService
{
    protected Builder $query;
    protected array $cols = [];
    protected array $extraCols = ['id'];
    protected ?array $selectColumns = null;
    protected array $searchable = [];
    protected array $buttons = [];
    protected array $quickCountCallbacks = [];
    protected bool $withStatusCounts = true;
    protected $customSortCallback = null;
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

    public function withoutStatusCounts(): self
    {
        $this->withStatusCounts = false;

        return $this;
    }

    public function columns(array $cols, array $extraCols = ['id']): self
    {
        $this->cols = $cols;
        $this->extraCols = $extraCols;

        return $this;
    }

    public function selectColumns(array $selectColumns): self
    {
        $this->selectColumns = $selectColumns;

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

    public function withCustomSort(callable $sorter): self
    {
        $this->customSortCallback = $sorter;

        return $this;
    }

    public function withQuickCount(string $key, callable $counter): self
    {
        $this->quickCountCallbacks[$key] = $counter;

        return $this;
    }

    public function withQuickCounts(array $callbacks): self
    {
        foreach ($callbacks as $key => $callback) {
            $this->quickCountCallbacks[$key] = $callback;
        }

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
        $selectCols = $this->selectColumns ?? array_values(array_unique(array_merge($this->extraCols, $this->cols)));

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

        if ($this->customSortCallback !== null) {
            $handled = ($this->customSortCallback)($this->query, $sort, $sortType, $request);
            if ($handled) {
                return;
            }
        }

        if (! empty($sort) && in_array($sort, $this->cols, true)) {
            $this->query->orderBy($sort, $sortType);
        } elseif (empty($this->query->getQuery()->orders)) {
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

            if ($this->withStatusCounts && in_array('status', $allCols, true)) {
                $quickCounts['published'] = $this->modelClass::whereIn('status', [1, '1', 'published'])->count();
                $quickCounts['draft'] = $this->modelClass::whereIn('status', [0, '0', 'draft'])->count();
            }

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $quickCounts['trashed'] = $this->modelClass::onlyTrashed()->count();
            }

            foreach ($this->quickCountCallbacks as $key => $callback) {
                $quickCounts[$key] = is_callable($callback) ? $callback($this->modelClass) : $callback;
            }
        } catch (\Throwable) {
            $quickCounts = [];
        }

        return $quickCounts;
    }
}
