<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WeighTicket;
use App\Support\StoneQuantityConverter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeighTicket>
 */
class WeighTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tare = fake()->randomFloat(3, 8, 16);
        $net = fake()->randomFloat(3, 10, 30);
        $gross = $tare + $net;
        $converter = StoneQuantityConverter::make();
        $quantities = $converter->from(ProductUnit::Ton, $net);

        return [
            'number' => 'TK-'.fake()->unique()->numerify('######'),
            'order_id' => null,
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'vehicle_plate' => strtoupper(fake()->bothify('???-####')),
            'tare_weight' => $tare,
            'gross_weight' => $gross,
            'net_weight' => $net,
            'quantity' => $quantities['quantity_ton'],
            'quantity_m3' => $quantities['quantity_m3'],
            'density' => number_format($converter->density, 3, '.', ''),
            'weighed_at' => now(),
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
