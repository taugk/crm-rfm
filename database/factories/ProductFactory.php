<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     * (Opsional jika nama file sudah sesuai standar Laravel)
     */
    protected $model = Product::class;

    /**
     * Mendefinisikan state default untuk model Product.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Gunakan fake() untuk menghindari error "Call to a member function on null"
            'sku'         => fake()->unique()->bothify('SKU-####'),
            'name'        => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'price'       => fake()->randomFloat(2, 10, 1000),
            'status'      => fake()->randomElement(['active', 'inactive']),
            
            // Menggunakan class reference langsung lebih bersih
            'category_id' => Category::factory(),
            
            'image'       => fake()->imageUrl(640, 480, 'products', true),
            
            // Di Laravel modern, created_at dan updated_at biasanya 
            // otomatis terisi, tapi tidak masalah jika ingin didefinisikan eksplisit.
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }
}