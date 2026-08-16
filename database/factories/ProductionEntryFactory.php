<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\User;
use App\Support\StoneQuantityConverter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionEntry>
 */
class ProductionEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $converter = StoneQuantityConverter::make();
        $quantities = $converter->from(ProductUnit::Ton, 50);

        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'method' => ProductionMethod::Quantity,
            'truck_id' => null,
            'trips_count' => null,
            'truck_capacity_m3' => null,
            'input_unit' => ProductUnit::Ton,
            'quantity' => $quantities['quantity_ton'],
            'quantity_m3' => $quantities['quantity_m3'],
            'quantity_ton' => $quantities['quantity_ton'],
            'density' => number_format($converter->density, 3, '.', ''),
            'stage' => ProductionStage::Plant,
            'shift' => fake()->randomElement(ProductionShift::cases()),
            'produced_on' => now()->toDateString(),
            'notes' => null,
            'affects_stock' => true,
            'parent_id' => null,
            'crushing_circuit_id' => null,
            'yield_percent' => null,
        ];
    }
}
