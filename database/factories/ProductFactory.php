<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Brita 0',
            'Brita 1',
            'Brita 2',
            'Brita 3',
            'Pó de pedra',
            'Rachão',
            'Bica corrida',
        ]);

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'bucket_capacity_m3' => 1.5,
            'stock_quantity' => fake()->randomFloat(3, 0, 5000),
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
