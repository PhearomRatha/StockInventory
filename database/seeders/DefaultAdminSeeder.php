<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run()
    {
        // Create default role
        $adminRole = Roles::firstOrCreate(
            ['name' => 'Admin'],
            ['permissions' => 'all']
        );

        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role_id' => $adminRole->id
            ]
        );
    }
}
