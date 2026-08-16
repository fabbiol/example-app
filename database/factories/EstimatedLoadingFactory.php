<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\StoneQuantityConverter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimatedLoading>
 */
class EstimatedLoadingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $converter = StoneQuantityConverter::make();
        $quantities = $converter->from(ProductUnit::CubicMeter, 12);
        $product = Product::factory();

        return [
            'number' => 'EST-'.fake()->unique()->numerify('######'),
            'order_id' => null,
            'customer_id' => Customer::factory(),
            'product_id' => $product,
            'user_id' => User::factory(),
            'vehicle_plate' => strtoupper(fake()->bothify('???-####')),
            'buckets_count' => 8,
            'bucket_capacity_m3' => 1.5,
            'input_unit' => ProductUnit::CubicMeter,
            'quantity_m3' => $quantities['quantity_m3'],
            'quantity_ton' => $quantities['quantity_ton'],
            'quantity' => $quantities['quantity_ton'],
            'density' => number_format($converter->density, 3, '.', ''),
            'loaded_at' => now(),
            'notes' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'product_id' => $order->product_id,
            'vehicle_plate' => $order->vehicle_plate ?? strtoupper(fake()->bothify('???-####')),
        ]);
    }
}
