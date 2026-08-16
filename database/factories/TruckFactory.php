<?php

namespace Database\Factories;

use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Truck>
 */
class TruckFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Caminhão '.fake()->unique()->numberBetween(1, 99),
            'plate' => strtoupper(fake()->unique()->bothify('???-####')),
            'capacity_m3' => fake()->randomElement([8.0, 10.0, 12.0, 14.0]),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
