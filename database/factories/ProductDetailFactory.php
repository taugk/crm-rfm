<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDetail>
 */
class ProductDetailFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     */
    protected $model = ProductDetail::class;

    /**
     * Mendefinisikan state default untuk model ProductDetail.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Menggunakan class reference langsung untuk relasi
            'product_id'   => Product::factory(),
            
            // Menggunakan helper fake() agar stabil di production
            'variant'      => fake()->randomElement(['Size', 'Color', 'Material']),
            'stock'        => fake()->numberBetween(1, 100),
            'cost_price'   => fake()->randomFloat(2, 5, 500),
            'date_in'      => fake()->date(),
            
            // Penggunaan format Y-m-d sudah tepat untuk kolom tipe date
            'expired_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }
}