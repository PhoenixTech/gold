<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_bank_account_and_set_active(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin)->post(route('admin.bank-account.store'), [
            'bank_name' => 'Melli',
            'account_holder_name' => 'Zhonella Shop',
            'card_number' => '6037991234567890',
            'account_number' => '1234567890',
            'iban' => 'IR120170000000123456789001',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'Melli',
            'account_holder_name' => 'Zhonella Shop',
            'card_number' => '6037991234567890',
            'is_active' => 1,
        ]);
    }

    public function test_activating_one_bank_account_deactivates_others(): void
    {
        $admin = $this->actingAsAdmin();
        $first = BankAccount::factory()->active()->create(['bank_name' => 'First Bank']);
        $second = BankAccount::factory()->create(['bank_name' => 'Second Bank']);

        $response = $this->actingAs($admin)->get(route('admin.bank-account.activate', $second));

        $response->assertRedirect();
        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_saving_active_bank_account_clears_previous_active(): void
    {
        $admin = $this->actingAsAdmin();
        $first = BankAccount::factory()->active()->create();

        $response = $this->actingAs($admin)->post(route('admin.bank-account.store'), [
            'bank_name' => 'New Active',
            'account_holder_name' => 'Holder',
            'iban' => 'IR120170000000123456789001',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertFalse($first->fresh()->is_active);
        $this->assertSame(1, BankAccount::query()->where('is_active', true)->count());
    }

    public function test_admin_bank_account_list_uses_a_persian_breadcrumb(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        app()->setLocale('fa');
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin)->get(route('admin.bank-account.index'));

        $response->assertOk();
        $response->assertSee('حساب‌های بانکی', false);
        $response->assertDontSee('Bank-accounts', false);
    }
}
