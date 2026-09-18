<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLogTest extends TestCase
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

    public function test_adminlogs_clean_command_removes_logs_older_than_one_month(): void
    {
        $user = $this->actingAsAdmin();

        $oldLog = AdminLog::create([
            'user_id' => $user->id,
            'action' => 'update',
            'loggable_type' => User::class,
            'loggable_id' => $user->id,
            'created_at' => now()->subMonths(2),
        ]);

        $recentLog = AdminLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'loggable_type' => User::class,
            'loggable_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        Artisan::call('model:prune', ['--model' => [AdminLog::class]]);

        $this->assertDatabaseMissing('admin_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('admin_logs', ['id' => $recentLog->id]);
    }

    public function test_admin_can_trigger_monthly_cleanup_via_controller(): void
    {
        $user = $this->actingAsAdmin();

        $oldLog = AdminLog::create([
            'user_id' => $user->id,
            'action' => 'destroy',
            'loggable_type' => User::class,
            'loggable_id' => $user->id,
            'created_at' => now()->subMonths(3),
        ]);

        $recentLog = AdminLog::create([
            'user_id' => $user->id,
            'action' => 'show',
            'loggable_type' => User::class,
            'loggable_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->post(route('admin.adminlog.cleanup'));

        $response->assertRedirect(route('admin.adminlog.index'));
        $this->assertDatabaseMissing('admin_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('admin_logs', ['id' => $recentLog->id]);
    }
}
