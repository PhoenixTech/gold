<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;

class AdminTableService
{
    protected Builder $query;

    protected array $cols = [];

    protected array $extraCols = ['id'];

    protected ?array $selectColumns = null;

    protected array $searchable = [];

    protected array $searchableRelations = [];

    protected array $colLabels = [];

    /** @var array<string, bool>|null Lazily resolved cache of real table columns. */
    private ?array $realColumns = null;

    protected array $buttons = [];

    protected array $quickCountCallbacks = [];

    protected bool $withStatusCounts = true;

    protected $customSortCallback = null;

    protected ?string $modelClass = null;

    protected ?int $perPage = null;

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

    /**
     * Extend the search box across related tables.
     *
     * ['customer' => ['name', 'mobile', 'code']] turns into OR-ed whereHas
     * clauses. Without this an admin list can only search its own columns,
     * while another screen in the same app searches the customer's name.
     *
     * @param  array<string, array<int, string>>  $relations
     */
    public function searchableRelations(array $relations): self
    {
        $this->searchableRelations = $relations;

        return $this;
    }

    public function perPage(?int $perPage): self
    {
        $this->perPage = $perPage;

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

    /**
     * Human-readable (and translatable) header labels for columns whose name is
     * not itself presentable.
     *
     * A list column may be a computed/virtual name like "payment_progress"; the
     * table header falls back to __($col), which would render the raw key.
     * Provide the label here instead.
     *
     * @param  array<string, string>  $labels  column => label
     */
    public function columnLabels(array $labels): self
    {
        $this->colLabels = $labels;

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

        $perPage = $this->perPage ?? (int) config('app.panel.page_count', 15);
        $selectCols = $this->selectColumns ?? array_values(array_unique(array_merge($this->extraCols, $this->cols)));

        $items = $this->query->paginate($perPage, $selectCols);
        $quickCounts = $this->computeQuickCounts($request);

        return [
            'items' => $items,
            'cols' => $this->cols,
            'colLabels' => $this->colLabels,
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

        if (! empty($sort) && $this->isRealColumn($sort)) {
            $this->query->orderBy($sort, $sortType);
        } elseif (empty($this->query->getQuery()->orders)) {
            $this->query->orderByDesc('id');
        }
    }

    /**
     * Whether a column name maps to a real column on the model's table.
     *
     * $cols may legitimately contain computed or virtual column names (a
     * withSum alias, a rendered cell such as "payment_progress"). Those are not
     * valid SQL, so ordering by them throws
     * "Unknown column ... in 'order clause'". Such columns have to be handled
     * explicitly through withCustomSort(); anything else is ignored and the
     * default ordering applies.
     */
    protected function isRealColumn(string $name): bool
    {
        if (! in_array($name, $this->cols, true)) {
            return false;
        }

        if ($this->realColumns === null) {
            $this->realColumns = $this->resolveRealColumns();
        }

        return $this->realColumns[$name] ?? false;
    }

    /**
     * @return array<string, bool>
     */
    private function resolveRealColumns(): array
    {
        if ($this->modelClass === null) {
            // Without a model we cannot introspect; fall back to trusting $cols.
            return array_fill_keys($this->cols, true);
        }

        try {
            $model = new $this->modelClass;
            $connection = $model->getConnection();
            $table = $model->getTable();

            // A column produced by a subquery alias (withCount/withSum/selectRaw)
            // is orderable but is not in the schema.
            //
            // withAggregate()/selectSub() store these as Expression objects
            // ("(<subquery>) as <alias>"), so the alias has to be read off the
            // expression rather than off a plain string. The match must not be
            // anchored to the end and must tolerate MySQL backticks, otherwise
            // computed sorts silently degrade to a no-op on that driver.
            $grammar = $connection->getQueryGrammar();
            $aliases = [];
            foreach ((array) ($this->query->getQuery()->columns ?? []) as $column) {
                $sql = is_string($column) ? $column : $this->expressionValue($column, $grammar);

                if ($sql !== null && preg_match('/\bas\s+[`"]?([^`"\s,)]+)[`"]?/i', $sql, $matches)) {
                    $aliases[$matches[1]] = true;
                }
            }

            $existing = array_fill_keys(
                $connection->getSchemaBuilder()->getColumnListing($table),
                true
            );

            return array_merge($existing, $aliases);
        } catch (\Throwable) {
            // Never let a schema lookup break the list; degrade to trusting $cols.
            return array_fill_keys($this->cols, true);
        }
    }

    /**
     * The raw SQL of a query Expression, or null when it cannot be read.
     */
    private function expressionValue(mixed $column, Grammar $grammar): ?string
    {
        try {
            if ($column instanceof Expression) {
                return (string) $column->getValue($grammar);
            }

            if (is_object($column) && method_exists($column, 'getValue')) {
                return (string) $column->getValue($grammar);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
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
        if (mb_strlen($search) === 0 || (empty($this->searchable) && empty($this->searchableRelations))) {
            return;
        }

        $searchable = $this->searchable;
        $relations = $this->searchableRelations;

        $this->query->where(function (Builder $sub) use ($search, $searchable, $relations) {
            foreach ($searchable as $index => $col) {
                if ($index === 0) {
                    $sub->where($col, 'LIKE', '%'.$search.'%');
                } else {
                    $sub->orWhere($col, 'LIKE', '%'.$search.'%');
                }
            }

            foreach ($relations as $relation => $columns) {
                $sub->orWhereHas($relation, function (Builder $relQuery) use ($columns, $search) {
                    foreach ($columns as $index => $col) {
                        if ($index === 0) {
                            $relQuery->where($col, 'LIKE', '%'.$search.'%');
                        } else {
                            $relQuery->orWhere($col, 'LIKE', '%'.$search.'%');
                        }
                    }
                });
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
