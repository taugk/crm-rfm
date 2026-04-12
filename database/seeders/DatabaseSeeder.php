<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager',
                'role' => 'manager',
                'password' => bcrypt('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@example.com'],
            [
                'name' => 'Kasir',
                'role' => 'kasir',
                'password' => bcrypt('password'),
            ]
        );
    }
}