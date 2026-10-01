<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards the admin receipt-review panel.
 *
 * Previously only the approval form was gated behind the "receipt can actually
 * be reviewed" precondition, while the decline and re-upload cards rendered
 * whenever the display status said WAITING_CONFIRMATION. An admin could be
 * shown two buttons that were guaranteed to be rejected server-side.
 */
class AdminInvoiceReceiptReviewTest extends TestCase
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

    private function createWaitingConfirmationInvoice(int $totalPrice = 1_000_000): array
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::AWAITING_PAYMENT,
            'total_price' => $totalPrice,
        ]);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'CARD-'.$invoice->hash;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = $totalPrice;
        $payment->save();

        Storage::fake('public');
        Storage::disk('public')->put('payment-receipts/'.$invoice->id.'/slip.jpg', 'fake');

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $invoice->id;
        $receipt->path = 'payment-receipts/'.$invoice->id.'/slip.jpg';
        $receipt->original_name = 'slip.jpg';
        $receipt->mime = 'image/jpeg';
        $receipt->size = 512;
        $receipt->amount = $totalPrice;
        $receipt->uploaded_by_customer_id = $customer->id;
        $receipt->save();

        return [$invoice, $payment, $receipt];
    }

    public function test_all_three_review_actions_render_when_the_receipt_can_be_reviewed(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice] = $this->createWaitingConfirmationInvoice();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));

        $response->assertOk();
        $response->assertSee(route('admin.invoice.confirm-payment', $invoice), false);
        $response->assertSee(route('admin.invoice.decline-payment', $invoice), false);
        $response->assertSee(route('admin.invoice.request-receipt-reupload', $invoice), false);
    }

    public function test_decline_and_reupload_are_hidden_when_the_payment_is_no_longer_pending(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, $payment] = $this->createWaitingConfirmationInvoice();

        // The invoice still displays WAITING_CONFIRMATION (a receipt is on file)
        // but the card payment is no longer PENDING, so every review action would
        // be rejected. The UI must explain that rather than offer dead buttons.
        $payment->status = Payment::CANCEL;
        $payment->save();

        $response = $this->get(route('admin.invoice.edit', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee(__('This receipt can no longer be reviewed here.'));
        $response->assertDontSee(route('admin.invoice.decline-payment', $invoice), false);
        $response->assertDontSee(route('admin.invoice.request-receipt-reupload', $invoice), false);
        $response->assertDontSee(route('admin.invoice.confirm-payment', $invoice), false);
    }

    public function test_review_actions_are_hidden_when_the_invoice_left_the_awaiting_payment_state(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice] = $this->createWaitingConfirmationInvoice();
        $invoice->status = Invoice::CANCELED;
        $invoice->save();

        $response = $this->get(route('admin.invoice.edit', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertDontSee(route('admin.invoice.decline-payment', $invoice), false);
        $response->assertDontSee(route('admin.invoice.request-receipt-reupload', $invoice), false);
    }

    public function test_receipt_review_lists_the_destination_account_declared_by_the_customer(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, , $receipt] = $this->createWaitingConfirmationInvoice();

        $bank = BankAccount::factory()->active()->create([
            'bank_name' => 'Saderat Destination',
            'card_number' => '6037999999999999',
        ]);
        $receipt->bank_account_id = $bank->id;
        $receipt->save();

        $response = $this->get(route('admin.invoice.edit', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee(__('Destination account'));
        $response->assertSee('Saderat Destination', false);
        $response->assertSee('6037999999999999', false);
    }

    public function test_receipt_review_uses_a_lightbox_instead_of_raw_thumbnail_links(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, , $receipt] = $this->createWaitingConfirmationInvoice();

        $response = $this->get(route('admin.invoice.edit', $invoice->fresh()->hash));

        $response->assertOk();
        $response->assertSee('id="receipt-preview-modal"', false);
        $response->assertSee('data-receipt-preview="'.$receipt->url().'"', false);
        $response->assertSee('receipt-zoom-btn', false);
    }

    public function test_receipt_reupload_keeps_customer_evidence_when_the_database_write_fails(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, , $receipt] = $this->createWaitingConfirmationInvoice();
        $path = $receipt->path;

        $this->assertTrue(Storage::disk('public')->exists($path));

        // Force the invoice write to fail after the receipts have been gathered
        // but before the transaction commits. A model event keeps the failure
        // scoped to this request; a throwing query listener would leak into
        // every later test in the process.
        Invoice::saving(function (): void {
            throw new \RuntimeException('simulated database failure');
        });

        // Let the exception surface instead of being rendered as a 500 page.
        $this->withoutExceptionHandling();

        $caught = null;
        try {
            $this->post(route('admin.invoice.request-receipt-reupload', $invoice), [
                'reason' => 'Unreadable slip',
            ]);
        } catch (\Throwable $exception) {
            $caught = $exception;
        }

        $this->assertNotNull($caught, 'The simulated failure should have aborted the re-upload');
        $this->assertSame('simulated database failure', $caught->getMessage());

        // The customer's evidence must survive a failed re-upload: previously the
        // files were deleted before anything else could fail, leaving nothing to
        // show for it.
        $this->assertTrue(
            Storage::disk('public')->exists($path),
            'The receipt file must not be deleted when the re-upload write fails'
        );
        $this->assertSame(
            1,
            PaymentReceipt::query()->where('invoice_id', $invoice->id)->count(),
            'The receipt row must survive a failed re-upload'
        );
    }

    public function test_successful_receipt_reupload_removes_the_files_and_extends_the_deadline(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, , $receipt] = $this->createWaitingConfirmationInvoice();
        $path = $receipt->path;
        $initialDeadline = $invoice->offlinePaymentDeadline();

        $this->post(route('admin.invoice.request-receipt-reupload', $invoice), [
            'reason' => 'Unreadable slip',
        ])->assertRedirect(route('admin.invoice.edit', $invoice));

        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertSame(0, PaymentReceipt::query()->where('invoice_id', $invoice->id)->count());
        $invoice->refresh();

        // The deadline was pushed forward explicitly rather than merely
        // recomputed from created_at.
        $this->assertArrayHasKey('offline_deadline_at', $invoice->meta);
        $this->assertGreaterThan(
            now(),
            $invoice->offlinePaymentDeadline(),
            'The re-upload must leave the customer a fresh payment window'
        );
        $this->assertGreaterThanOrEqual($initialDeadline, $invoice->offlinePaymentDeadline());
    }

    public function test_reupload_request_is_not_recorded_as_a_terminal_decline(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice] = $this->createWaitingConfirmationInvoice();

        $this->post(route('admin.invoice.request-receipt-reupload', $invoice), [
            'reason' => 'Amount does not match',
        ])->assertRedirect();

        $invoice->refresh();

        // Both actions used to write meta['decline_reason'], so the customer view
        // could not tell "send a clearer slip" from "we cancelled your order".
        $this->assertSame('Amount does not match', $invoice->reuploadRequestedReason());
        $this->assertNull($invoice->declinedReceiptReason());
        $this->assertNull($invoice->meta['declined_at'] ?? null);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
    }

    public function test_declining_a_receipt_records_a_terminal_decline_and_releases_stock(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice, $payment] = $this->createWaitingConfirmationInvoice();

        $this->post(route('admin.invoice.decline-payment', $invoice), [
            'reason' => 'Wrong amount',
        ])->assertRedirect(route('admin.invoice.edit', $invoice));

        $invoice->refresh();

        $this->assertSame(Invoice::CANCELED, $invoice->status);
        $this->assertSame('Wrong amount', $invoice->declinedReceiptReason());
        $this->assertNotNull($invoice->meta['declined_at'] ?? null);
        $this->assertNull($invoice->reuploadRequestedReason());
        $this->assertSame(Payment::CANCEL, $payment->fresh()->status);
    }

    public function test_stepper_shows_a_waiting_state_while_the_customer_has_not_paid_yet(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        // WAITING_RECEIPT: nothing for the admin to do, so the stepper must not
        // tick payment as "done".
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::AWAITING_PAYMENT,
        ]);

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));

        $response->assertOk();
        $response->assertSee(__('Waiting on customer'), false);

        // The current step is flagged is-waiting, and no step is ticked as done:
        // the customer has not paid yet, so payment is not complete.
        $this->assertSame(1, preg_match(
            '/<ol class="invoice-stepper.*?<\/ol>/s',
            $response->getContent(),
            $matches
        ), 'The stepper should render');

        $stepper = $matches[0];

        $this->assertMatchesRegularExpression(
            '/<li class="invoice-stepper__item is-current is-waiting"/',
            $stepper
        );
        $this->assertStringNotContainsString('is-done', $stepper);
        $this->assertStringContainsString('aria-current="step"', $stepper);
    }

    public function test_edit_page_exposes_print_shipping_label_and_order_board_shortcuts(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::PAID,
        ]);

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));

        $response->assertOk();
        $response->assertSee(route('admin.order-board.index'), false);
        $response->assertSee(route('admin.invoice.print', $invoice->hash), false);
        $response->assertSee(route('admin.invoice.shipping-label', $invoice->hash), false);
    }

    public function test_destructive_review_forms_use_a_declarative_confirmation(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice] = $this->createWaitingConfirmationInvoice();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));

        $response->assertOk();
        $response->assertSee('data-confirm="'.e(__('Request receipt re-upload from customer?')).'"', false);
        $response->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_edit_page_does_not_render_an_empty_multipart_form(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        [$invoice] = $this->createWaitingConfirmationInvoice();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));

        $response->assertOk();
        // The layout used to wrap nothing in a zero-field multipart form.
        $response->assertDontSee('id="model-form-edit"', false);
    }
}
