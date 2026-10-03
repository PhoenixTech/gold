<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceTransitionGuardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function makeInvoice(string $status, string $deliveryType = 'pickup'): Invoice
    {
        $invoice = new Invoice;
        $invoice->customer_id = Customer::factory()->create()->id;
        $invoice->status = $status;
        $invoice->delivery_type = $deliveryType;
        $invoice->total_price = 1_000_000;
        $invoice->count = 1;
        $invoice->save();

        return $invoice->fresh();
    }

    public function test_form_cannot_mark_an_unpaid_invoice_as_paid(): void
    {
        $this->actingAsAdmin();
        $invoice = $this->makeInvoice(Invoice::AWAITING_PAYMENT);

        $this->post(route('admin.invoice.update', $invoice), ['status' => Invoice::PAID])
            ->assertSessionHasErrors('status');

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }

    public function test_form_cannot_cancel_or_fail_an_invoice(): void
    {
        $this->actingAsAdmin();
        $invoice = $this->makeInvoice(Invoice::PAID);

        foreach ([Invoice::CANCELED, Invoice::FAILED] as $status) {
            $this->post(route('admin.invoice.update', $invoice), ['status' => $status])
                ->assertSessionHasErrors('status');
        }

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
    }

    public function test_awaiting_payment_cannot_jump_to_fulfillment(): void
    {
        $this->actingAsAdmin();
        $invoice = $this->makeInvoice(Invoice::AWAITING_PAYMENT);

        $this->post(route('admin.invoice.update', $invoice), ['status' => Invoice::READY_FOR_PICKUP])
            ->assertSessionHasErrors('status');

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
    }

    public function test_closed_invoice_cannot_be_reopened(): void
    {
        $this->actingAsAdmin();

        foreach ([Invoice::CANCELED, Invoice::FAILED, Invoice::COMPLETED] as $closed) {
            $invoice = $this->makeInvoice($closed);

            $this->post(route('admin.invoice.update', $invoice), ['status' => Invoice::PROCESSING])
                ->assertSessionHasErrors('status');

            $this->assertSame($closed, $invoice->fresh()->status);
        }
    }

    public function test_workflow_steps_follow_the_invoice_state(): void
    {
        $workflow = new InvoiceWorkflow;

        $waiting = $this->makeInvoice(Invoice::AWAITING_PAYMENT);
        $this->assertSame(InvoiceWorkflow::STEP_PAYMENT, $workflow->currentStep($waiting));
        $this->assertSame('waiting', $workflow->steps($waiting)[0]['state']);

        $paid = $this->makeInvoice(Invoice::PAID);
        $steps = $workflow->steps($paid);
        $this->assertSame(['done', 'done', 'current', 'todo'], array_column($steps, 'state'));

        $out = $this->makeInvoice(Invoice::OUT_FOR_DELIVERY, 'address');
        $this->assertSame(InvoiceWorkflow::STEP_HANDOVER, $workflow->currentStep($out));
        $this->assertSame('waiting', $workflow->steps($out)[3]['state']);

        $done = $this->makeInvoice(Invoice::COMPLETED);
        $this->assertSame(['done', 'done', 'done', 'done'], array_column($workflow->steps($done), 'state'));
    }

    public function test_workflow_transition_map(): void
    {
        $workflow = new InvoiceWorkflow;
        $pickupPaid = $this->makeInvoice(Invoice::PAID);
        $courierPaid = $this->makeInvoice(Invoice::PAID, 'address');

        $this->assertTrue($workflow->canMove($pickupPaid, Invoice::READY_FOR_PICKUP));
        $this->assertFalse($workflow->canMove($pickupPaid, Invoice::OUT_FOR_DELIVERY));
        $this->assertTrue($workflow->canMove($courierPaid, Invoice::OUT_FOR_DELIVERY));
        $this->assertFalse($workflow->canMove($courierPaid, Invoice::AWAITING_PAYMENT));
        $this->assertFalse($workflow->canMove($courierPaid, Invoice::PENDING));
        $this->assertTrue($workflow->canMove($courierPaid, Invoice::CANCELED));
    }
}
