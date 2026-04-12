<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customers;
use App\Models\ProductDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. SEED USERS (Admin, Manager, Kasir)
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Kasir',
            'email' => 'kasir@example.com',
            'role' => 'kasir',
            'password' => bcrypt('password'),
        ]);

        // 2. SEED PRODUK & KATEGORI
        Category::factory(5)->create();
        Product::factory(20)->create();
        ProductDetail::factory(20)->create();
        Customers::factory(50)->create();

       
    }
}