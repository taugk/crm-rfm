<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class CustomersFactory extends Factory
{
    protected $model = \App\Models\Customers::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'profile_photo' => null,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->dateTimeBetween('-40 years', '-17 years')->format('Y-m-d'),
            'password' => null,            
            'type' => $this->faker->randomElement(['member', 'member', 'member', 'walk in']),
            // Mengisi data awal untuk simulasi RFM
            'total_points' => $this->faker->numberBetween(0, 5000),
            'last_purchase_at' => $this->faker->dateTimeBetween('-6 months', 'now'),

            'full_address' => $this->faker->address(),
            
            'status' => 'active',
            'role' => 'customer',
        ];
    }

    /**
     * State untuk membuat customer yang tidak aktif (At Risk/Lost)
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    
}
