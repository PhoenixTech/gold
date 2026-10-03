<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceCancellationRefundTest extends TestCase
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

    /**
     * @return array{0: Invoice, 1: Customer, 2: Quantity}
     */
    private function makeInvoice(string $status, int $total = 5_000_000, string $deliveryType = 'pickup'): array
    {
        $customer = Customer::factory()->create(['credit' => 1000]);
        $product = Product::factory()->create(['status' => 1]);
        $quantity = Quantity::factory()->create(['product_id' => $product->id, 'count' => 0]);
        $quantity->markSold();

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->status = $status;
        $invoice->delivery_type = $deliveryType;
        $invoice->total_price = $total;
        $invoice->count = 1;
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = $total;
        $order->save();

        return [$invoice->fresh(), $customer, $quantity];
    }

    public function test_cancel_before_payment_does_not_refund_and_releases_stock(): void
    {
        $this->actingAsAdmin();
        [$invoice, $customer, $quantity] = $this->makeInvoice(Invoice::AWAITING_PAYMENT);

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'مشتری منصرف شد'])
            ->assertRedirect();

        $this->assertSame(Invoice::CANCELED, $invoice->fresh()->status);
        $this->assertSame(1000, (int) $customer->fresh()->credit);
        $this->assertSame(0, Credit::query()->count());
        $this->assertTrue($quantity->fresh()->isAvailable());
    }

    #[DataProvider('paidStatuses')]
    public function test_cancel_after_payment_refunds_full_amount_once(string $status): void
    {
        $this->actingAsAdmin();
        [$invoice, $customer, $quantity] = $this->makeInvoice($status);

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'خطا در موجودی']);

        $invoice->refresh();
        $this->assertSame(Invoice::CANCELED, $invoice->status);
        $this->assertSame(5_001_000, (int) $customer->fresh()->credit);
        $this->assertSame(1, Credit::query()->where('invoice_id', $invoice->id)->count());
        $this->assertSame(5_000_000, (int) $invoice->meta['refunded_amount']);
        $this->assertSame('خطا در موجودی', $invoice->meta['cancel_reason']);
        $this->assertTrue($quantity->fresh()->isAvailable());

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'دوباره'])
            ->assertSessionHasErrors('status');
        $this->assertSame(5_001_000, (int) $customer->fresh()->credit);
        $this->assertSame(1, Credit::query()->count());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function paidStatuses(): array
    {
        return [
            'paid' => [Invoice::PAID],
            'processing' => [Invoice::PROCESSING],
            'ready for pickup' => [Invoice::READY_FOR_PICKUP],
            'out for delivery' => [Invoice::OUT_FOR_DELIVERY],
        ];
    }

    public function test_cancel_out_for_delivery_closes_the_open_delivery(): void
    {
        $this->actingAsAdmin();
        [$invoice] = $this->makeInvoice(Invoice::OUT_FOR_DELIVERY, 3_000_000, 'address');

        $courier = User::factory()->courier()->create();
        $delivery = new Delivery;
        $delivery->invoice_id = $invoice->id;
        $delivery->courier_id = $courier->id;
        $delivery->code_hash = bcrypt('1234');
        $delivery->status = DeliveryStatus::Accepted;
        $delivery->failed_attempts = 0;
        $delivery->save();

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'مشتری در دسترس نیست']);

        $this->assertSame(DeliveryStatus::Rejected, $delivery->fresh()->status);
        $this->assertFalse($invoice->deliveries()->open()->exists());
    }

    public function test_completed_invoice_cannot_be_canceled(): void
    {
        $this->actingAsAdmin();
        [$invoice, $customer] = $this->makeInvoice(Invoice::COMPLETED);

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => 'x'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
        $this->assertSame(1000, (int) $customer->fresh()->credit);
    }

    public function test_reason_is_required(): void
    {
        $this->actingAsAdmin();
        [$invoice, $customer] = $this->makeInvoice(Invoice::PAID);

        $this->post(route('admin.invoice.cancel', $invoice), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(1000, (int) $customer->fresh()->credit);
    }
}
