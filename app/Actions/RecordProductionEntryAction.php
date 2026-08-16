<?php

namespace App\Actions;

use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionStage;
use App\Models\CrushingCircuit;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordProductionEntryAction
{
    public function __construct(
        private ApplyCrushingCircuitDistribution $applyCircuitDistribution,
    ) {}

    /**
     * @param  array{
     *     product_id: int,
     *     user_id?: int|null,
     *     method: string,
     *     stage?: string|null,
     *     truck_id?: int|null,
     *     trips_count?: int|null,
     *     truck_capacity_m3?: float|string|null,
     *     input_unit?: string|null,
     *     quantity_input?: float|string|null,
     *     shift: string,
     *     produced_on: string,
     *     notes?: string|null,
     *     apply_circuit?: bool|null,
     *     crushing_circuit_id?: int|null
     * }  $data
     */
    public function handle(array $data): ProductionEntry
    {
        return DB::transaction(function () use ($data) {
            $method = ProductionMethod::from($data['method']);

            if ($method === ProductionMethod::Scale) {
                throw ValidationException::withMessages([
                    'method' => 'A balança de produção ainda não está ativa. Use viagens ou quantidade estimada.',
                ]);
            }

            $product = Product::query()
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $converter = $product->converter();
            $truck = null;
            $tripsCount = null;
            $truckCapacity = null;
            $inputUnit = null;

            if ($method === ProductionMethod::Trips) {
                $tripsCount = (int) ($data['trips_count'] ?? 0);

                if ($tripsCount <= 0) {
                    throw ValidationException::withMessages([
                        'trips_count' => 'Informe a quantidade de viagens.',
                    ]);
                }

                if (! empty($data['truck_id'])) {
                    $truck = Truck::query()->whereKey($data['truck_id'])->firstOrFail();
                    $truckCapacity = number_format((float) $truck->capacity_m3, 3, '.', '');
                } else {
                    $truckCapacity = number_format(
                        (float) ($data['truck_capacity_m3'] ?? $product->bucket_capacity_m3),
                        3,
                        '.',
                        '',
                    );
                }

                $quantities = $converter->fromBuckets($tripsCount, $truckCapacity);
                $inputUnit = ProductUnit::CubicMeter;
            } else {
                $inputUnit = ProductUnit::from($data['input_unit'] ?? $product->unit->value);
                $quantityInput = $data['quantity_input'] ?? null;

                if ($quantityInput === null || bccomp((string) $quantityInput, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'quantity_input' => 'Informe a quantidade estimada.',
                    ]);
                }

                $quantities = $converter->from($inputUnit, $quantityInput);
            }

            $quantity = $converter->forProductUnit($product->unit, $quantities);
            $stage = ProductionStage::from($data['stage'] ?? ProductionStage::Plant->value);
            $applyCircuit = (bool) ($data['apply_circuit'] ?? false)
                && $stage === ProductionStage::QuarryToPrimary;

            $circuit = null;

            if ($applyCircuit) {
                $circuit = ! empty($data['crushing_circuit_id'])
                    ? CrushingCircuit::query()->with('yields.product')->findOrFail($data['crushing_circuit_id'])
                    : CrushingCircuit::query()->with('yields.product')->where('is_default', true)->where('is_active', true)->first();

                if (! $circuit) {
                    throw ValidationException::withMessages([
                        'crushing_circuit_id' => 'Configure um circuito de britagem padrão antes de distribuir.',
                    ]);
                }
            }

            $entry = ProductionEntry::query()->create([
                'product_id' => $product->id,
                'user_id' => $data['user_id'] ?? null,
                'method' => $method,
                'truck_id' => $truck?->id,
                'trips_count' => $tripsCount,
                'truck_capacity_m3' => $truckCapacity,
                'input_unit' => $inputUnit,
                'quantity' => $quantity,
                'quantity_m3' => $quantities['quantity_m3'],
                'quantity_ton' => $quantities['quantity_ton'],
                'density' => number_format($converter->density, 3, '.', ''),
                'stage' => $stage,
                'shift' => $data['shift'],
                'produced_on' => $data['produced_on'],
                'notes' => $data['notes'] ?? null,
                'crushing_circuit_id' => $circuit?->id,
                'affects_stock' => ! $applyCircuit,
            ]);

            if ($applyCircuit && $circuit) {
                $this->applyCircuitDistribution->handle($entry, $circuit, $data['user_id'] ?? null);
            } else {
                $product->update([
                    'stock_quantity' => bcadd((string) $product->stock_quantity, $quantity, 3),
                ]);
            }

            return $entry->fresh(['product', 'truck', 'user', 'children.product']);
        });
    }
}
