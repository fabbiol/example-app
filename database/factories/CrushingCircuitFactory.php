<?php

namespace Database\Factories;

use App\Models\CrushingCircuit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrushingCircuit>
 */
class CrushingCircuitFactory extends Factory
{
    protected $model = CrushingCircuit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Circuito '.fake()->unique()->word(),
            'is_default' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }
}
