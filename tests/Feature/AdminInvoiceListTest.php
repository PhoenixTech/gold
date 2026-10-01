<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function createTransport(string $title, bool $requiresCode): Transport
    {
        $transport = new Transport;
        $transport->title = $title;
        $transport->price = 45000;
        $transport->requires_delivery_code = $requiresCode;
        $transport->save();

        return $transport;
    }

    public function test_list_renders_the_traditional_column_set(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(3)->create();

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();

        $content = $response->getContent();

        foreach (['hash', 'created_at', 'customer_id', 'customer_mobile', 'items_summary', 'total_price', 'payment_progress', 'delivery_method', 'status'] as $column) {
            $this->assertStringContainsString(
                'sort='.$column,
                $content,
                "The {$column} column should be present and sortable"
            );
        }

        foreach ([__('Invoice number'), __('Items / weight'), __('Fulfillment'), __('Phone number')] as $label) {
            $this->assertStringContainsString(e($label), $content);
        }
    }

    public function test_list_shows_invoice_number_and_customer_phone(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $customer = Customer::factory()->create(['name' => 'Sara Mohammadi', 'mobile' => '09121234567']);
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();
        $response->assertSee('#'.$invoice->hash, false);
        $response->assertSee('ID '.$invoice->id, false);
        $response->assertSee('Sara Mohammadi', false);
        $response->assertSee('09121234567', false);
        $response->assertSee('tel:09121234567', false);
    }

    public function test_list_sums_piece_weight_across_ordered_quantities(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = Invoice::factory()->create(['count' => 3]);

        foreach ([[2.450, 'A'], [1.125, 'B'], [0.750, 'C']] as [$weight, $code]) {
            $quantity = Quantity::factory()->create(['weight' => $weight, 'code' => $code]);
            Order::factory()->create([
                'invoice_id' => $invoice->id,
                'product_id' => $quantity->product_id,
                'quantity_id' => $quantity->id,
                'count' => 1,
                'price_total' => 100000,
            ]);
        }

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();
        // 2.450 + 1.125 + 0.750
        $response->assertSee('4.325', false);
    }

    public function test_payment_progress_renders_each_state_without_contradicting_itself(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        // 1. Settled: verified card payment, receipts cover the total.
        $settled = Invoice::factory()->create(['status' => Invoice::PAID, 'total_price' => 1000000]);
        $settledPayment = $this->createCardPayment($settled, Payment::SUCCESS);
        $this->createReceipt($settledPayment, $settled, 1000000);

        // 2. Short: a receipt is on file but does not cover the total, and the
        //    payment is NOT confirmed. This must never read as "Paid".
        $short = Invoice::factory()->create(['status' => Invoice::AWAITING_PAYMENT, 'total_price' => 1000000]);
        $shortPayment = $this->createCardPayment($short, Payment::PENDING);
        $this->createReceipt($shortPayment, $short, 400000);

        // 3. Unpaid offline card payment, no receipt at all.
        $unpaid = Invoice::factory()->create(['status' => Invoice::AWAITING_PAYMENT, 'total_price' => 1000000]);
        $this->createCardPayment($unpaid, Payment::PENDING);

        // 4. Online gateway invoice: receipts are not part of that flow.
        $online = Invoice::factory()->create(['status' => Invoice::PAID, 'total_price' => 1000000]);
        $this->createOnlinePayment($online);

        $content = $this->get(route('admin.invoice.index'))->getContent();

        $this->assertStringContainsString(__('Settled'), $content);
        $this->assertStringContainsString(__('Unpaid'), $content);
        $this->assertStringContainsString(__('Online gateway'), $content);

        // The short invoice shows its coverage percentage and remaining balance.
        $this->assertStringContainsString('40%', $content);
        $this->assertStringContainsString(number_format(600000), $content);
    }

    public function test_delivery_method_distinguishes_pickup_courier_and_post(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $post = $this->createTransport('Post Express', false);
        $courierTransport = $this->createTransport('Motorcycle Courier', true);

        $pickupInvoice = Invoice::factory()->pickup()->create();
        $postInvoice = Invoice::factory()->create(['transport_id' => $post->id, 'transport_price' => $post->price]);
        $courierInvoice = Invoice::factory()->create(['transport_id' => $courierTransport->id]);

        $content = $this->get(route('admin.invoice.index'))->getContent();

        $this->assertStringContainsString(__('Store pickup'), $content);
        $this->assertStringContainsString('Post Express', $content);
        $this->assertStringContainsString(__('Pending courier'), $content);
        $this->assertStringContainsString(__('Courier'), $content);
    }

    public function test_search_matches_hash_customer_name_and_phone(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $match = Customer::factory()->create([
            'name' => 'Negin Distinctive',
            'mobile' => '09355550000',
            'code' => 'ZK-7777',
        ]);
        Invoice::factory()->create(['customer_id' => $match->id]);

        $other = Customer::factory()->create(['name' => 'Someone Else', 'mobile' => '09110000000']);
        Invoice::factory()->create(['customer_id' => $other->id]);

        // By customer name
        $this->get(route('admin.invoice.index', ['q' => 'Distinctive']))
            ->assertOk()
            ->assertSee('Negin Distinctive', false)
            ->assertDontSee('Someone Else', false);

        // By phone
        $this->get(route('admin.invoice.index', ['q' => '09355550000']))
            ->assertOk()
            ->assertSee('Negin Distinctive', false)
            ->assertDontSee('Someone Else', false);

        // By customer code
        $this->get(route('admin.invoice.index', ['q' => 'ZK-7777']))
            ->assertOk()
            ->assertSee('Negin Distinctive', false)
            ->assertDontSee('Someone Else', false);

        // By invoice hash
        $hash = $match->invoices()->firstOrFail()->hash;
        $this->get(route('admin.invoice.index', ['q' => $hash]))->assertOk()->assertSee('#'.$hash, false);
    }

    public function test_status_chips_show_live_counts(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(2)->waitingReceipt()->create();
        Invoice::factory()->count(3)->waitingConfirmation()->create();
        Invoice::factory()->count(4)->paid()->create();

        $content = $this->get(route('admin.invoice.index'))->getContent();

        // Each chip renders its own count next to the label.
        foreach ([__('Waiting receipt') => 2, __('Review receipt') => 3, __('Paid') => 4] as $label => $count) {
            $this->assertMatchesRegularExpression(
                '/'.preg_quote($label, '/').'\s*<span[^>]*>\s*'.preg_quote((string) $count, '/').'/u',
                $content,
                "Chip \"{$label}\" should show a count of {$count}"
            );
        }
    }

    public function test_delivery_type_filter_narrows_the_result_set(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $pickup = Invoice::factory()->pickup()->create();
        $shipped = Invoice::factory()->create(['delivery_type' => 'address']);

        $pickupOnly = $this->get(route('admin.invoice.index', ['filter' => ['delivery_type' => 'pickup']]));
        $pickupOnly->assertOk()->assertSee('#'.$pickup->hash, false)->assertDontSee('#'.$shipped->hash, false);

        $addressOnly = $this->get(route('admin.invoice.index', ['filter' => ['delivery_type' => 'address']]));
        $addressOnly->assertOk()->assertSee('#'.$shipped->hash, false)->assertDontSee('#'.$pickup->hash, false);
    }

    public function test_list_reports_page_totals(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->create(['total_price' => 3000000]);
        Invoice::factory()->create(['total_price' => 2000000]);

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();
        $response->assertSee(__('Amount on this page'), false);
        $response->assertSee('5,000,000', false);
    }

    public function test_list_paginates_instead_of_loading_every_invoice(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(20)->create();

        $page = $this->get(route('admin.invoice.index', ['per_page' => 5]));

        $page->assertOk();
        $page->assertViewHas('items', function ($items): bool {
            return $items->count() === 5 && $items->total() === 20;
        });
    }

    public function test_list_does_not_load_receipt_rows_eagerly(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(3)->waitingConfirmation()->create();

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->get(route('admin.invoice.index'))->assertOk();

        // The virtual-status badge must be satisfied by a withCount subquery,
        // not by hydrating every payment_receipts row for every listed order.
        $this->assertNotEmpty(
            array_filter($queries, fn ($sql) => str_contains($sql, 'as "payment_receipts_count"')),
            'Expected a withCount subquery providing payment_receipts_count'
        );
        $this->assertNotEmpty(
            array_filter($queries, fn ($sql) => str_contains($sql, 'as "receipts_amount"')),
            'Expected a withSum subquery providing receipts_amount'
        );

        // The only remaining payment_receipts queries may be aggregate or
        // exists() subqueries -- never a hydration of the receipt rows.
        $this->assertEmpty(
            array_filter($queries, fn ($sql) => preg_match('/select .*?from "?payment_receipts"?/i', $sql)
                && ! str_contains($sql, 'count(*)')
                && ! str_contains($sql, 'sum(')
                && ! str_contains($sql, 'exists(')),
            'The list must not hydrate receipt rows; it only needs the count and the amount sum'
        );
    }

    public function test_sorting_by_weight_and_amount_orders_the_rows(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $light = Invoice::factory()->create(['total_price' => 900000]);
        $lightQuantity = Quantity::factory()->create(['weight' => 1.000]);
        Order::factory()->create(['invoice_id' => $light->id, 'product_id' => $lightQuantity->product_id, 'quantity_id' => $lightQuantity->id, 'count' => 1, 'price_total' => 900000]);

        $heavy = Invoice::factory()->create(['total_price' => 800000]);
        $heavyQuantity = Quantity::factory()->create(['weight' => 9.000]);
        Order::factory()->create(['invoice_id' => $heavy->id, 'product_id' => $heavyQuantity->product_id, 'quantity_id' => $heavyQuantity->id, 'count' => 1, 'price_total' => 800000]);

        $byWeight = $this->get(route('admin.invoice.index', ['sort' => 'total_weight', 'sortType' => 'desc']));
        $byWeight->assertOk();
        $this->assertSame(
            [$heavy->hash, $light->hash],
            $this->renderedRowHashes($byWeight->getContent()),
            'Sorting by weight descending should place the heavier order first'
        );

        $byAmount = $this->get(route('admin.invoice.index', ['sort' => 'total_price', 'sortType' => 'desc']));
        $byAmount->assertOk();
        $this->assertSame(
            [$light->hash, $heavy->hash],
            $this->renderedRowHashes($byAmount->getContent()),
            'Sorting by amount descending should place the larger order first'
        );
    }

    public function test_column_headers_are_translated_instead_of_showing_raw_keys(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        Invoice::factory()->create();

        $content = $this->get(route('admin.invoice.index'))->getContent();

        // Every header must render a presentable label. The computed column
        // names are internal identifiers, not something to put in front of an
        // admin, so the controller supplies labels for them.
        foreach ([
            'hash' => __('Invoice number'),
            'created_at' => __('created_at'),
            'customer_id' => __('Customer'),
            'customer_mobile' => __('Phone number'),
            'items_summary' => __('Items / weight'),
            'total_price' => __('total_price'),
            'payment_progress' => __('Payment'),
            'delivery_method' => __('Fulfillment'),
            'status' => __('status'),
        ] as $column => $label) {
            $this->assertStringContainsString(
                'sort='.$column,
                $content,
                "The {$column} column should still be sortable"
            );
            $this->assertStringContainsString(
                e($label),
                $content,
                "The {$column} header should render as \"{$label}\""
            );
        }

        // No header may leak a raw snake_case identifier.
        foreach (['payment_progress', 'items_summary', 'delivery_method', 'customer_mobile'] as $raw) {
            $this->assertStringNotContainsString(
                '>'.e(__($raw)).'<',
                $content,
                "The raw column name \"{$raw}\" must not appear as a header label"
            );
        }
    }

    /**
     * Column names that are rendered cells rather than database columns.
     *
     * They are perfectly legal in $cols, but they are NOT valid SQL, so they
     * must never reach an ORDER BY clause.
     *
     * @var list<string>
     */
    private const VIRTUAL_COLUMNS = [
        'customer_mobile', 'customer_code', 'items_summary', 'payment_progress', 'delivery_method',
    ];

    public function test_sorting_never_emits_a_virtual_column_name_in_the_order_clause(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(3)->create();

        $columns = [
            'hash', 'created_at', 'customer_id', 'customer_mobile', 'customer_code',
            'items_summary', 'total_price', 'payment_progress', 'delivery_method', 'status',
        ];

        foreach ($columns as $column) {
            foreach (['asc', 'desc'] as $direction) {
                $sql = $this->orderByClauseFor($column, $direction);

                $this->assertNotNull($sql, "Sorting by {$column} produced no ORDER BY clause");

                foreach (self::VIRTUAL_COLUMNS as $virtual) {
                    $this->assertStringNotContainsString(
                        '"'.$virtual.'"',
                        $sql,
                        "Sorting by {$column} put the virtual column \"{$virtual}\" in ORDER BY. "
                            .'It is not a database column, which raises 42S22 on MySQL. '
                            .'Map it to a real column or select alias in withCustomSort().'
                    );
                }
            }
        }
    }

    public function test_sorting_by_every_column_returns_a_successful_response(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(3)->create();

        $columns = [
            'hash', 'created_at', 'customer_id', 'customer_mobile', 'customer_code',
            'items_summary', 'total_price', 'payment_progress', 'delivery_method', 'status',
        ];

        foreach ($columns as $column) {
            foreach (['asc', 'desc'] as $direction) {
                $this->get(route('admin.invoice.index', ['sort' => $column, 'sortType' => $direction]))
                    ->assertOk();
            }
        }
    }

    /**
     * The tail of the generated SELECT starting at its ORDER BY clause.
     */
    private function orderByClauseFor(string $sort, string $direction): ?string
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->get(route('admin.invoice.index', ['sort' => $sort, 'sortType' => $direction]))->assertOk();

        foreach ($queries as $sql) {
            if (str_contains($sql, 'from "invoices"') && str_contains($sql, 'order by')) {
                $position = strpos($sql, 'order by');

                return substr($sql, $position);
            }
        }

        return null;
    }

    public function test_unknown_sort_column_is_ignored_instead_of_erroring(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->count(2)->create();

        // A column that is not in $cols and not a real column must never reach
        // the ORDER BY clause.
        $this->get(route('admin.invoice.index', ['sort' => 'does_not_exist']))
            ->assertOk();

        // ...and neither must a real column that the list does not expose.
        $this->get(route('admin.invoice.index', ['sort' => 'transport_price']))
            ->assertOk();
    }

    public function test_sorting_payment_progress_orders_by_money_received(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $noReceipts = Invoice::factory()->create(['status' => Invoice::AWAITING_PAYMENT, 'total_price' => 900000]);
        $this->createCardPayment($noReceipts, Payment::PENDING);

        $partial = Invoice::factory()->create(['status' => Invoice::AWAITING_PAYMENT, 'total_price' => 900000]);
        $partialPayment = $this->createCardPayment($partial, Payment::PENDING);
        $this->createReceipt($partialPayment, $partial, 300000);

        // Assert both directions so the test cannot pass by accident: if the sort
        // were silently dropped, the default "id desc" would produce the same
        // order for both.
        $desc = $this->get(route('admin.invoice.index', ['sort' => 'payment_progress', 'sortType' => 'desc']));
        $desc->assertOk();
        $this->assertSame(
            [$partial->hash, $noReceipts->hash],
            $this->renderedRowHashes($desc->getContent()),
            'Descending by received amount should put the funded order first'
        );

        $asc = $this->get(route('admin.invoice.index', ['sort' => 'payment_progress', 'sortType' => 'asc']));
        $asc->assertOk();
        $this->assertSame(
            [$noReceipts->hash, $partial->hash],
            $this->renderedRowHashes($asc->getContent()),
            'Ascending by received amount should put the unfunded order first'
        );
    }

    public function test_sorting_customer_columns_uses_the_joined_customer_data(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $zzz = Customer::factory()->create(['name' => 'Zzz Last', 'mobile' => '08110000000']);
        $aaa = Customer::factory()->create(['name' => 'Aaa First', 'mobile' => '09990000000']);

        $zInvoice = Invoice::factory()->create(['customer_id' => $zzz->id]);
        $aInvoice = Invoice::factory()->create(['customer_id' => $aaa->id]);

        // Both directions, so a silently-dropped sort (which would yield the
        // same default "id desc" order twice) cannot pass.
        $byNameAsc = $this->get(route('admin.invoice.index', ['sort' => 'customer_id', 'sortType' => 'asc']));
        $byNameAsc->assertOk();
        $this->assertSame(
            [$aInvoice->hash, $zInvoice->hash],
            $this->renderedRowHashes($byNameAsc->getContent()),
            'Ascending by customer name should put Aaa First first'
        );

        $byNameDesc = $this->get(route('admin.invoice.index', ['sort' => 'customer_id', 'sortType' => 'desc']));
        $byNameDesc->assertOk();
        $this->assertSame(
            [$zInvoice->hash, $aInvoice->hash],
            $this->renderedRowHashes($byNameDesc->getContent())
        );

        // The phone column sorts by the customer's mobile, not by invoice id.
        $byMobileAsc = $this->get(route('admin.invoice.index', ['sort' => 'customer_mobile', 'sortType' => 'asc']));
        $byMobileAsc->assertOk();
        $this->assertSame(
            [$zInvoice->hash, $aInvoice->hash],
            $this->renderedRowHashes($byMobileAsc->getContent()),
            'Ascending by mobile should put 0811… first'
        );

        $byMobileDesc = $this->get(route('admin.invoice.index', ['sort' => 'customer_mobile', 'sortType' => 'desc']));
        $byMobileDesc->assertOk();
        $this->assertSame(
            [$aInvoice->hash, $zInvoice->hash],
            $this->renderedRowHashes($byMobileDesc->getContent())
        );
    }

    public function test_sorting_items_summary_orders_by_piece_count(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $many = Invoice::factory()->create(['count' => 9]);
        $few = Invoice::factory()->create(['count' => 1]);

        $desc = $this->get(route('admin.invoice.index', ['sort' => 'items_summary', 'sortType' => 'desc']));
        $desc->assertOk();
        $this->assertSame(
            [$many->hash, $few->hash],
            $this->renderedRowHashes($desc->getContent())
        );

        $asc = $this->get(route('admin.invoice.index', ['sort' => 'items_summary', 'sortType' => 'asc']));
        $asc->assertOk();
        $this->assertSame(
            [$few->hash, $many->hash],
            $this->renderedRowHashes($asc->getContent()),
            'Ascending by item count should reverse the default order'
        );
    }

    private function createCardPayment(Invoice $invoice, string $status): Payment
    {
        // Payment has no factory definition; orders are required by the schema.
        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'CARD-'.$invoice->hash;
        $payment->type = 'CARD';
        $payment->status = $status;
        $payment->amount = $invoice->total_price;
        $payment->save();

        return $payment;
    }

    private function createOnlinePayment(Invoice $invoice): Payment
    {
        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'GW-'.$invoice->hash;
        $payment->type = 'ONLINE';
        $payment->status = Payment::SUCCESS;
        $payment->amount = $invoice->total_price;
        $payment->save();

        return $payment;
    }

    private function createReceipt(Payment $payment, Invoice $invoice, int $amount): PaymentReceipt
    {
        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $invoice->id;
        $receipt->path = 'payment-receipts/'.$invoice->id.'/slip.jpg';
        $receipt->original_name = 'slip.jpg';
        $receipt->mime = 'image/jpeg';
        $receipt->size = 1024;
        $receipt->amount = $amount;
        $receipt->uploaded_by_customer_id = $invoice->customer_id;
        $receipt->save();

        return $receipt;
    }

    /**
     * The invoice numbers in the order they are rendered as table rows.
     *
     * @return list<string>
     */
    private function renderedRowHashes(string $content): array
    {
        preg_match_all('/#([A-Za-z0-9]+)<\/span>/', $content, $matches);

        return $matches[1];
    }
}
