<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Models\EstimatedLoading;
use App\Models\EstimatedLoadingItem;
use App\Models\Product;
use App\Support\StoneQuantityConverter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimatedLoadingItem>
 */
class EstimatedLoadingItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $converter = StoneQuantityConverter::make();
        $quantities = $converter->from(ProductUnit::CubicMeter, 6);

        return [
            'estimated_loading_id' => EstimatedLoading::factory(),
            'product_id' => Product::factory(),
            'sort_order' => 0,
            'input_unit' => ProductUnit::CubicMeter,
            'quantity_m3' => $quantities['quantity_m3'],
            'quantity_ton' => $quantities['quantity_ton'],
            'quantity' => $quantities['quantity_ton'],
            'density' => number_format($converter->density, 3, '.', ''),
            'loader_loaded_at' => null,
        ];
    }

    public function loaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'loader_loaded_at' => now(),
        ]);
    }
}
