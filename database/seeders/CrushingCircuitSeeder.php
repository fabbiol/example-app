<?php

namespace Database\Seeders;

use App\Models\CrushingCircuit;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CrushingCircuitSeeder extends Seeder
{
    /**
     * Distribuição média típica do circuito secundário de agregados.
     */
    public function run(): void
    {
        $circuit = CrushingCircuit::query()->updateOrCreate(
            ['name' => 'Circuito secundário padrão'],
            [
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Distribuição proporcional média da rocha detonada após primário/secundário para agregados de construção civil.',
            ],
        );

        CrushingCircuit::query()
            ->whereKeyNot($circuit->id)
            ->update(['is_default' => false]);

        $yields = [
            // Brita 3 e 4 (pedra de mão residual): 15% a 25% → 20% total
            ['code' => 'BRITA-3', 'group_name' => 'Brita 3 e 4 (residual)', 'percent' => 10.000, 'percent_min' => 7.500, 'percent_max' => 12.500, 'sort_order' => 1],
            ['code' => 'BRITA-4', 'group_name' => 'Brita 3 e 4 (residual)', 'percent' => 10.000, 'percent_min' => 7.500, 'percent_max' => 12.500, 'sort_order' => 2],
            // Brita 1 e 2: 40% a 50% → 45% total
            ['code' => 'BRITA-1', 'group_name' => 'Brita 1 e 2 (principais)', 'percent' => 22.000, 'percent_min' => 20.000, 'percent_max' => 25.000, 'sort_order' => 3],
            ['code' => 'BRITA-2', 'group_name' => 'Brita 1 e 2 (principais)', 'percent' => 23.000, 'percent_min' => 20.000, 'percent_max' => 25.000, 'sort_order' => 4],
            // Pedrisco / Brita 0: 10% a 15% → 13% total
            ['code' => 'PEDRISCO', 'group_name' => 'Pedrisco / Brita 0', 'percent' => 6.000, 'percent_min' => 5.000, 'percent_max' => 7.500, 'sort_order' => 5],
            ['code' => 'BRITA-0', 'group_name' => 'Pedrisco / Brita 0', 'percent' => 7.000, 'percent_min' => 5.000, 'percent_max' => 7.500, 'sort_order' => 6],
            // Pó de pedra: 15% a 20% → 18%
            ['code' => 'PO-PEDRA', 'group_name' => 'Pó de pedra (fino)', 'percent' => 18.000, 'percent_min' => 15.000, 'percent_max' => 20.000, 'sort_order' => 7],
        ];

        foreach ($yields as $yield) {
            $product = Product::query()->where('code', $yield['code'])->first();

            if (! $product) {
                continue;
            }

            $circuit->yields()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'group_name' => $yield['group_name'],
                    'percent' => $yield['percent'],
                    'percent_min' => $yield['percent_min'],
                    'percent_max' => $yield['percent_max'],
                    'sort_order' => $yield['sort_order'],
                ],
            );
        }
    }
}
