<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceRemoveOrderTest extends TestCase
{
    use RefreshDatabase;

    private const TRANSPORT_PRICE = 50_000;

    /**
     * @return array{0: Invoice, 1: list<Order>, 2: list<Quantity>, 3: Customer}
     */
    private function makeInvoice(string $status, int $orderCount): array
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $customer = Customer::factory()->create([
            'name' => 'مریم طلایی',
            'mobile' => '09001112233',
            'credit' => 0,
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، زعفرانیه، پلاک ۸';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پست';
        $transport->price = self::TRANSPORT_PRICE;
        $transport->requires_delivery_code = false;
        $transport->save();

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->transport_price = self::TRANSPORT_PRICE;
        $invoice->status = $status;
        $invoice->total_price = 0;
        $invoice->count = 0;
        $invoice->save();

        $orders = [];
        $quantities = [];

        for ($i = 0; $i < $orderCount; $i++) {
            $product = Product::factory()->create([
                'name' => 'انگشتر طلا '.($i + 1),
                'status' => 1,
            ]);

            $quantity = Quantity::factory()->create([
                'product_id' => $product->id,
                'weight' => 2.5,
                'count' => 0,
            ]);

            $order = new Order;
            $order->invoice_id = $invoice->id;
            $order->product_id = $product->id;
            $order->quantity_id = $quantity->id;
            $order->count = 1;
            $order->price_total = 1_000_000;
            $order->save();

            $orders[] = $order;
            $quantities[] = $quantity;
        }

        $invoice->recalculateTotals();

        return [$invoice->fresh(), $orders, $quantities, $customer];
    }

    public function test_removing_order_from_paid_invoice_refunds_credit_and_recalculates(): void
    {
        [$invoice, $orders, $quantities, $customer] = $this->makeInvoice(Invoice::PAID, 2);

        $this->get(route('admin.invoice.remove-order', $orders[0]))->assertRedirect();

        $customer->refresh();
        $this->assertSame(1_000_000, (int) $customer->credit);
        $this->assertDatabaseHas('credits', [
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 1_000_000,
        ]);

        $this->assertDatabaseMissing('orders', ['id' => $orders[0]->id]);

        $invoice->refresh();
        $this->assertSame(1_000_000 + self::TRANSPORT_PRICE, (int) $invoice->total_price);
        $this->assertSame(1, (int) $invoice->count);

        $quantities[0]->refresh();
        $this->assertSame(1, (int) $quantities[0]->count);
    }

    public function test_removing_order_from_unpaid_invoice_does_not_refund(): void
    {
        [$invoice, $orders, , $customer] = $this->makeInvoice(Invoice::AWAITING_PAYMENT, 2);

        $this->get(route('admin.invoice.remove-order', $orders[0]))->assertRedirect();

        $customer->refresh();
        $this->assertSame(0, (int) $customer->credit);
        $this->assertDatabaseCount('credits', 0);

        $invoice->refresh();
        $this->assertSame(1_000_000 + self::TRANSPORT_PRICE, (int) $invoice->total_price);
        $this->assertSame(1, (int) $invoice->count);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
    }

    public function test_removing_last_order_cancels_invoice(): void
    {
        [$invoice, $orders, , $customer] = $this->makeInvoice(Invoice::PAID, 1);

        $this->get(route('admin.invoice.remove-order', $orders[0]))->assertRedirect();

        $invoice->refresh();
        $this->assertSame(Invoice::CANCELED, $invoice->status);
        $this->assertSame(0, (int) $invoice->count);
        $this->assertSame(self::TRANSPORT_PRICE, (int) $invoice->total_price);

        $customer->refresh();
        $this->assertSame(1_000_000, (int) $customer->credit);
    }
}
