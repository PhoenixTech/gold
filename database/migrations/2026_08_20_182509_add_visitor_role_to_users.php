<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            $roles = implode("','", User::$roles);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('{$roles}') NOT NULL DEFAULT 'USER'");
        }

        Role::findOrCreate('visitor', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $visitorIds = User::query()->where('role', 'VISITOR')->pluck('id');
        if ($visitorIds->isNotEmpty()) {
            User::query()->whereIn('id', $visitorIds)->update(['role' => 'USER']);
        }

        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('DEVELOPER','ADMIN','USER','SUSPENDED') NOT NULL DEFAULT 'USER'");
        }

        Role::findOrCreate('visitor', 'web')->delete();
    }
};
