<?php

namespace App\Actions;

use App\Enums\ProductionStage;
use App\Models\CrushingCircuit;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteHaulageTripAction
{
    public function __construct(
        private ApplyCrushingCircuitDistribution $applyCircuitDistribution,
    ) {}

    public function handle(
        Truck $truck,
        ProductionStage $stage = ProductionStage::QuarryToPrimary,
        bool $affectsStock = true,
    ): ProductionEntry {
        return DB::transaction(function () use ($truck, $stage, $affectsStock) {
            $entry = ProductionEntry::query()
                ->openHaulage()
                ->where('truck_id', $truck->id)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                throw ValidationException::withMessages([
                    'truck_id' => 'Não há viagem em andamento neste caminhão.',
                ]);
            }

            $product = Product::query()
                ->whereKey($entry->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $unloadedAt = now();

            if ($stage === ProductionStage::Plant) {
                $entry->update([
                    'stage' => ProductionStage::Plant,
                    'unloaded_at' => $unloadedAt,
                    'produced_on' => $unloadedAt->toDateString(),
                    'crushing_circuit_id' => null,
                    'affects_stock' => $affectsStock,
                ]);

                if ($affectsStock) {
                    $product->update([
                        'stock_quantity' => bcadd((string) $product->stock_quantity, (string) $entry->quantity, 3),
                    ]);
                }

                return $entry->fresh(['product', 'truck', 'user', 'children.product']);
            }

            $circuit = CrushingCircuit::query()
                ->with('yields.product')
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            $applyCircuit = $circuit !== null && $circuit->yields->isNotEmpty();

            $entry->update([
                'stage' => ProductionStage::QuarryToPrimary,
                'unloaded_at' => $unloadedAt,
                'produced_on' => $unloadedAt->toDateString(),
                'crushing_circuit_id' => $applyCircuit ? $circuit->id : null,
                'affects_stock' => ! $applyCircuit,
            ]);

            if ($applyCircuit && $circuit) {
                $this->applyCircuitDistribution->handle($entry, $circuit, $entry->user_id);
            } else {
                $product->update([
                    'stock_quantity' => bcadd((string) $product->stock_quantity, (string) $entry->quantity, 3),
                ]);
            }

            return $entry->fresh(['product', 'truck', 'user', 'children.product']);
        });
    }
}
