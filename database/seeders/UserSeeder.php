<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $staffRole = Roles::where('name', Roles::ROLE_STAFF)->first();
        if (!$staffRole) {
            $staffRole = Roles::firstOrCreate(['name' => Roles::ROLE_STAFF]);
        }

        User::updateOrCreate(
            ['email' => 'ratha@example.com'],
            [
                'name' => 'ratha',
                'password' => Hash::make('password123'),
                'status' => User::STATUS_ACTIVE,
                'role_id' => $staffRole->id,
            ]
        );
    }
}
