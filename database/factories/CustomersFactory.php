<?php

namespace Database\Factories;

use App\Models\Customers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customers>
 */
class CustomersFactory extends Factory
{
    /**
     * Nama model yang terkait.
     */
    protected $model = Customers::class;

    /**
     * Mendefinisikan state default untuk model Customers.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'             => fake()->name(),
            'phone'            => fake()->unique()->phoneNumber(),
            'email'            => fake()->unique()->safeEmail(),
            'profile_photo'    => null,
            'gender'           => fake()->randomElement(['male', 'female']),
            'date_of_birth'    => fake()->dateTimeBetween('-40 years', '-17 years')->format('Y-m-d'),
            
            // Memberikan password default agar tidak error jika kolom NOT NULL
            'password'         => Hash::make('password'), 
            
            'type'             => fake()->randomElement(['member', 'member', 'member', 'walk in']),
            
            // Data untuk simulasi RFM & Clustering
            'total_points'     => fake()->numberBetween(0, 5000),
            'last_purchase_at' => fake()->dateTimeBetween('-6 months', 'now'),

            'full_address'     => fake()->address(),
            'status'           => 'active',
            'role'             => 'customer',
            
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    /**
     * State untuk membuat customer yang tidak aktif (At Risk/Lost)
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}