<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Roles;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run()
    {
        // Create default role
        $adminRole = Roles::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrator with full access']
        );

        // Ensure all permissions exist
        if (Permission::count() === 0) {
            foreach (Permission::MODULES as $module) {
                foreach (Permission::ACTIONS as $action) {
                    Permission::create([
                        'module' => $module,
                        'action' => $action,
                        'description' => "{$module}.{$action}",
                    ]);
                }
            }
        }

        // Sync all permissions to admin role
        $allPermissionIds = Permission::pluck('id')->all();
        $adminRole->permissions()->sync($allPermissionIds);

        // Create default admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'status' => User::STATUS_ACTIVE,
                'role_id' => $adminRole->id
            ]
        );

        // Show debug info
        dump($adminRole->load('permissions')->toArray());
        dump($adminUser->load('role.permissions')->toArray());
    }
}
