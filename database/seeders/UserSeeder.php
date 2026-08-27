<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // developer
        User::factory()->create(
            [
                'name' => 'WebDeveloper',
                'email' => 'developer@example.com',
                'role' => 'DEVELOPER',
            ]
        );
        // website admin
        User::factory()->create(
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'ADMIN',
            ]
        );
        // website user
        User::factory()->create(
            [
                'name' => 'ManagerUser',
                'email' => 'user@example.com',
            ]
        );

        // website visitor
        User::factory()->create(
            [
                'name' => 'Visitor',
                'email' => 'visitor@example.com',
                'role' => 'VISITOR',
            ]
        );

        // add roles
        foreach (User::$roles as $role) {
            Role::findOrCreate(strtolower($role), 'web');
        }

        $developer = User::whereId(1)->first();
        $developer->assignRole('developer');
        $developer->save();

        $admin = User::whereId(2)->first();
        $admin->assignRole('admin');
        $admin->save();

        $user = User::whereId(3)->first();
        $user->assignRole('user');
        $user->save();

        $visitor = User::where('email', 'visitor@example.com')->first();
        $visitor->assignRole('visitor');
        $visitor->save();

        User::factory()->create(
            [
                'name' => 'Courier',
                'email' => 'courier@example.com',
                'role' => 'COURIER',
            ]
        );
        $courier = User::where('email', 'courier@example.com')->first();
        $courier->assignRole('courier');
        $courier->save();

    }
}
