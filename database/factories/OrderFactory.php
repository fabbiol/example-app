<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => fake()->randomFloat(3, 10, 40),
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'destination' => fake()->optional()->streetAddress(),
            'vehicle_plate' => strtoupper(fake()->bothify('???-####')),
            'scheduled_at' => null,
            'notes' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity_requested'] ?? 20;

            return [
                'status' => OrderStatus::Completed,
                'quantity_loaded' => $quantity,
            ];
        });
    }
}
