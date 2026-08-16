<?php

namespace App\Actions;

use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Enums\ProductUnit;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartHaulageTripAction
{
    /**
     * @param  array{truck_id: int, product_id: int, user_id?: int|null}  $data
     */
    public function handle(array $data): ProductionEntry
    {
        return DB::transaction(function () use ($data) {
            $truck = Truck::query()
                ->whereKey($data['truck_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $truck->is_active) {
                throw ValidationException::withMessages([
                    'truck_id' => 'Este caminhão está inativo.',
                ]);
            }

            $openTrip = ProductionEntry::query()
                ->openHaulage()
                ->where('truck_id', $truck->id)
                ->exists();

            if ($openTrip) {
                throw ValidationException::withMessages([
                    'truck_id' => 'Este caminhão já está em viagem. Descarregue no primário primeiro.',
                ]);
            }

            $product = Product::query()
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'Este produto está inativo.',
                ]);
            }

            $capacity = number_format((float) $truck->capacity_m3, 3, '.', '');
            $converter = $product->converter();
            $quantities = $converter->fromBuckets(1, $capacity);
            $quantity = $converter->forProductUnit($product->unit, $quantities);

            return ProductionEntry::query()->create([
                'product_id' => $product->id,
                'user_id' => $data['user_id'] ?? null,
                'method' => ProductionMethod::Trips,
                'truck_id' => $truck->id,
                'trips_count' => 1,
                'truck_capacity_m3' => $capacity,
                'input_unit' => ProductUnit::CubicMeter,
                'quantity' => $quantity,
                'quantity_m3' => $quantities['quantity_m3'],
                'quantity_ton' => $quantities['quantity_ton'],
                'density' => number_format($converter->density, 3, '.', ''),
                'stage' => ProductionStage::QuarryToPrimary,
                'shift' => $this->shiftForNow(),
                'produced_on' => now()->toDateString(),
                'loaded_at' => now(),
                'unloaded_at' => null,
                'affects_stock' => false,
            ])->fresh(['product', 'truck', 'user']);
        });
    }

    private function shiftForNow(): ProductionShift
    {
        $hour = (int) now()->format('G');

        if ($hour < 12) {
            return ProductionShift::Morning;
        }

        if ($hour < 18) {
            return ProductionShift::Afternoon;
        }

        return ProductionShift::Night;
    }
}
