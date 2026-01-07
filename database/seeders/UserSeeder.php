<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'ratha',
            'email' => 'ratha@example.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
