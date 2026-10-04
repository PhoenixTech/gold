<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_update_delete_and_restore_a_supplier(): void
    {
        $admin = $this->actingAsAdmin();

        $this->actingAs($admin)
            ->post(route('admin.supplier.store'), [
                'first_name' => 'Sara',
                'last_name' => 'Ahmadi',
                'company_name' => 'Zhonella Metals',
                'account_number' => '1234567890',
                'iban' => 'IR120170000000123456789001',
            ])
            ->assertRedirect();

        $supplier = Supplier::query()->firstOrFail();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'first_name' => 'Sara',
            'last_name' => 'Ahmadi',
            'company_name' => 'Zhonella Metals',
            'account_number' => '1234567890',
            'iban' => 'IR120170000000123456789001',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.supplier.update', $supplier), [
                'first_name' => 'Sahar',
                'last_name' => 'Ahmadi',
                'company_name' => 'Zhonella Trading',
                'account_number' => '9876543210',
                'iban' => 'IR120170000000123456789001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'first_name' => 'Sahar',
            'company_name' => 'Zhonella Trading',
            'account_number' => '9876543210',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.supplier.destroy', $supplier))
            ->assertRedirect();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        $this->actingAs($admin)
            ->get(route('admin.supplier.restore', $supplier))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'deleted_at' => null]);
    }

    public function test_supplier_requires_first_and_last_names(): void
    {
        $admin = $this->actingAsAdmin();

        $this->actingAs($admin)
            ->from(route('admin.supplier.create'))
            ->post(route('admin.supplier.store'), [
                'company_name' => 'Zhonella Metals',
            ])
            ->assertRedirect(route('admin.supplier.create'))
            ->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_supplier_list_is_available_in_persian_under_shop_definitions(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        app()->setLocale('fa');
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin)->get(route('admin.supplier.index'));

        $response->assertOk();
        $response->assertSee('فهرست تأمین‌کنندگان', false);

        $html = view('components.panel-side-navbar')->render();
        $this->assertStringContainsString('تعاریف فروشگاه', $html);
        $this->assertStringContainsString('تأمین‌کنندگان', $html);
        $this->assertStringContainsString(route('admin.supplier.index'), $html);
    }
}
