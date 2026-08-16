<?php

namespace App\Actions;

use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Enums\ProductUnit;
use App\Models\CrushingCircuit;
use App\Models\CrushingCircuitYield;
use App\Models\ProductionEntry;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplyCrushingCircuitDistribution
{
    /**
     * @return Collection<int, ProductionEntry>
     */
    public function handle(
        ProductionEntry $feedEntry,
        CrushingCircuit $circuit,
        ?int $userId = null,
    ): Collection {
        $circuit->loadMissing(['yields.product']);

        if ($circuit->yields->isEmpty()) {
            throw ValidationException::withMessages([
                'crushing_circuit_id' => 'Este circuito não possui percentuais configurados.',
            ]);
        }

        $totalPercent = (string) $circuit->yields->sum(fn (CrushingCircuitYield $yield) => (float) $yield->percent);

        if (bccomp($totalPercent, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'crushing_circuit_id' => 'A soma dos percentuais do circuito deve ser maior que zero.',
            ]);
        }

        $feedTons = (string) ($feedEntry->quantity_ton ?: $feedEntry->quantity);
        $children = collect();

        foreach ($circuit->yields as $yield) {
            /** @var CrushingCircuitYield $yield */
            $product = $yield->product;

            if (! $product) {
                continue;
            }

            $shareTons = bcmul($feedTons, bcdiv((string) $yield->percent, '100', 6), 3);
            $converter = $product->converter();
            $quantities = $converter->from(ProductUnit::Ton, $shareTons);
            $quantity = $converter->forProductUnit($product->unit, $quantities);

            $lockedProduct = $product->newQuery()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $child = ProductionEntry::query()->create([
                'parent_id' => $feedEntry->id,
                'crushing_circuit_id' => $circuit->id,
                'affects_stock' => true,
                'yield_percent' => $yield->percent,
                'product_id' => $lockedProduct->id,
                'user_id' => $userId ?? $feedEntry->user_id,
                'method' => ProductionMethod::Quantity,
                'truck_id' => null,
                'trips_count' => null,
                'truck_capacity_m3' => null,
                'input_unit' => ProductUnit::Ton,
                'quantity' => $quantity,
                'quantity_m3' => $quantities['quantity_m3'],
                'quantity_ton' => $quantities['quantity_ton'],
                'density' => number_format($converter->density, 3, '.', ''),
                'stage' => ProductionStage::Plant,
                'shift' => $feedEntry->shift instanceof ProductionShift
                    ? $feedEntry->shift
                    : ProductionShift::from((string) $feedEntry->shift),
                'produced_on' => $feedEntry->produced_on,
                'notes' => sprintf(
                    'Distribuição automática do circuito "%s" (%.3f%% da alimentação #%d).',
                    $circuit->name,
                    (float) $yield->percent,
                    $feedEntry->id,
                ),
            ]);

            $lockedProduct->update([
                'stock_quantity' => bcadd((string) $lockedProduct->stock_quantity, $quantity, 3),
            ]);

            $children->push($child);
        }

        return $children;
    }
}
