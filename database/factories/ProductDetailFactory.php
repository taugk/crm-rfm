<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductDetail;

class ProductDetailFactory extends Factory
{

    protected $model = ProductDetail::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'variant' => $this->faker->randomElement(['Size', 'Color', 'Material']),
            'stock' => $this->faker->numberBetween(1, 100),
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'date_in' => $this->faker->date(),
            'expired_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
