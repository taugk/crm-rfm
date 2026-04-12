<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Nama model yang terkait.
     */
    protected $model = Category::class;

    /**
     * Mendefinisikan state default untuk model Category.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Menggunakan fake() untuk stabilitas di lingkungan production
            'kd_category' => fake()->unique()->bothify('CAT-###'),
            'name'        => fake()->unique()->word(),
            'description' => fake()->sentence(),
            
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }
}