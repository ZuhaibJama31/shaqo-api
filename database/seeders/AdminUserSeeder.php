<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], // unique check
            [
                'name' => 'Admin',
                'phone' => '+255754304110',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'city' => 'Garowe',
            ]
        );
    }
}