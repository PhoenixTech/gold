<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\Admin\AdminTableService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The admin list sorter must never emit a column name that is not a real
 * database column.
 *
 * Lists legitimately render computed cells ("payment_progress",
 * "items_summary"), but those names are not valid SQL. Letting one reach an
 * ORDER BY raises SQLSTATE[42S22] on MySQL -- while SQLite silently resolves an
 * unknown ORDER BY identifier to NULL, so an HTTP-status assertion gives false
 * confidence. These tests assert the generated SQL directly.
 */
class AdminTableServiceSortingTest extends TestCase
{
    public function test_subquery_aliases_are_treated_as_sortable(): void
    {
        $query = Invoice::query()
            ->withCount('paymentReceipts')
            ->withSum('paymentReceipts as receipts_amount', 'amount')
            ->addSelect('invoices.*')
            ->addSelect([
                'total_weight' => Order::query()
                    ->selectRaw('COALESCE(SUM(quantities.weight), 0)')
                    ->join('quantities', 'quantities.id', '=', 'orders.quantity_id')
                    ->whereColumn('orders.invoice_id', 'invoices.id'),
            ]);

        $service = new AdminTableService;
        $service->for($query)
            ->columns(['receipts_amount', 'total_weight', 'payment_receipts_count'])
            ->selectColumns(['*']);

        $request = Request::create('/', 'GET', ['sort' => 'receipts_amount']);
        $this->invokeApplySorting($service, $request);

        $order = $this->orderByClause($query);

        // The alias must be the thing being ordered by. Asserting against the
        // whole SQL would pass anyway, because the alias also appears in the
        // SELECT list.
        $this->assertStringContainsString('order by', $order);
        $this->assertMatchesRegularExpression(
            '/order by .*receipts_amount/',
            $order,
            'Sorting by the receipts_amount alias should order by that alias'
        );
    }

    public function test_total_weight_alias_is_sortable(): void
    {
        $query = Invoice::query()
            ->addSelect(['total_weight' => Order::query()
                ->selectRaw('COALESCE(SUM(quantities.weight), 0)')
                ->join('quantities', 'quantities.id', '=', 'orders.quantity_id')
                ->whereColumn('orders.invoice_id', 'invoices.id')])
            ->addSelect('invoices.*');

        $service = new AdminTableService;
        $service->for($query)->columns(['total_weight'])->selectColumns(['*']);

        $this->invokeApplySorting($service, Request::create('/', 'GET', ['sort' => 'total_weight']));

        $this->assertStringContainsString(
            'total_weight',
            $this->orderByClause($query),
            'The selectSub total_weight alias must be recognised as an orderable column'
        );
    }

    /**
     * The tail of the compiled SELECT starting at its ORDER BY clause.
     */
    private function orderByClause(EloquentBuilder $query): string
    {
        $sql = strtolower($query->toSql());
        $position = strpos($sql, 'order by');

        return $position === false ? '' : substr($sql, $position);
    }

    public function test_a_virtual_column_name_is_never_emitted_into_order_by(): void
    {
        $query = Invoice::query();

        $service = new AdminTableService;
        $service->for($query)
            ->columns(['payment_progress', 'items_summary', 'delivery_method'])
            ->selectColumns(['*']);

        $request = Request::create('/', 'GET', ['sort' => 'payment_progress']);
        $this->invokeApplySorting($service, $request);

        $sql = strtolower($query->toSql());

        foreach (['payment_progress', 'items_summary', 'delivery_method'] as $virtual) {
            $this->assertStringNotContainsString(
                'order by "'.$virtual.'"',
                $sql,
                "Virtual column \"{$virtual}\" leaked into ORDER BY; MySQL raises 42S22."
            );
        }

        // Falls back to the default ordering instead of erroring.
        $this->assertStringContainsString('order by', $sql);
    }

    public function test_expression_alias_helper_handles_mysql_backtick_quoting(): void
    {
        // Unit-level check on the helper: withAggregate()/selectSub() keep the
        // alias inside an Expression, so the helper must read it off that object
        // rather than off a plain string, tolerate MySQL backticks, and not
        // require the alias to be at the end of the statement.
        $grammar = \DB::connection()->getQueryGrammar();

        foreach ([
            '(select sum("amount")) as "receipts_amount"',
            '(select sum(`amount`)) as `receipts_amount`',
            '(select count(*)) as "payment_receipts_count" from "invoices" order by "id" desc',
        ] as $raw) {
            $alias = $this->aliasFrom(new Expression($raw), $grammar);

            $this->assertNotNull($alias, "No alias detected in: {$raw}");
            $this->assertStringNotContainsString('"', (string) $alias);
            $this->assertStringNotContainsString('`', (string) $alias);
        }

        $this->assertSame('receipts_amount', $this->aliasFrom(
            new Expression('(select sum(`amount`)) as `receipts_amount`'),
            $grammar
        ));
    }

    private function aliasFrom(Expression $expression, Grammar $grammar): ?string
    {
        $service = new AdminTableService;

        $method = (new \ReflectionClass($service))->getMethod('expressionValue');
        $method->setAccessible(true);

        $sql = $method->invoke($service, $expression, $grammar);

        return $sql !== null && preg_match('/\bas\s+[`"]?([^`"\s,)]+)[`"]?/i', $sql, $m) ? $m[1] : null;
    }

    private function invokeApplySorting(AdminTableService $service, Request $request): void
    {
        $method = (new \ReflectionClass(AdminTableService::class))->getMethod('applySorting');
        $method->setAccessible(true);
        $method->invoke($service, $request);
    }
}
