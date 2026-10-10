<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_access_invoice_list(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $response = $this->get(route('admin.invoice.index'));
        $response->assertOk();
    }

    public function test_admin_can_view_invoice_edit_page(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = Invoice::factory()->create();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));
        $response->assertOk();
    }

    public function test_admin_can_soft_delete_and_restore_invoice(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = Invoice::factory()->create();

        $deleteResponse = $this->get(route('admin.invoice.destroy', $invoice->hash));
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);

        $restoreResponse = $this->get(route('admin.invoice.restore', $invoice->hash));
        $restoreResponse->assertRedirect();
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_admin_can_bulk_delete_and_restore_invoices(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $i1 = Invoice::factory()->create();
        $i2 = Invoice::factory()->create();

        $deleteResponse = $this->post(route('admin.invoice.bulk'), [
            'action' => 'delete',
            'id' => [$i1->id, $i2->id],
        ]);
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('invoices', ['id' => $i1->id]);
        $this->assertSoftDeleted('invoices', ['id' => $i2->id]);

        $restoreResponse = $this->post(route('admin.invoice.bulk'), [
            'action' => 'restore',
            'id' => [$i1->id, $i2->id],
        ]);
        $restoreResponse->assertRedirect();
        $this->assertNotSoftDeleted('invoices', ['id' => $i1->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $i2->id]);
    }

    public function test_invoice_list_omits_published_and_draft_filters(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Invoice::factory()->create(['status' => Invoice::AWAITING_PAYMENT]);

        $response = $this->get(route('admin.invoice.index'));
        $response->assertOk();
        $response->assertDontSee('filter[status]=1');
        $response->assertDontSee('filter[status]=0');
    }

    public function test_invoice_edit_omits_paid_and_failed_invoices_row_in_customer_card(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = Invoice::factory()->create();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));
        $response->assertOk();
        $response->assertDontSee(__('Paid invoices'), false);
        $response->assertDontSee(__('Failed invoices'), false);
        $response->assertDontSee('فاکتورهای پرداخت‌شده', false);
        $response->assertDontSee('سفارش‌های ناموفق', false);
    }

    public function test_invoice_edit_ui_matches_creation_screen_layout(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = Invoice::factory()->create();

        $response = $this->get(route('admin.invoice.edit', $invoice->hash));
        $response->assertOk();
        $response->assertSee(route('admin.invoice.index'), false);
        $response->assertSee(__('Website sales'), false);
        $response->assertSee('ri-arrow-right-line', false);
        $response->assertSee(__('Invoice summary'), false);
        $response->assertDontSee('action-btn circle-btn', false);
    }
}

