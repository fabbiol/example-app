<?php

namespace App\Actions;

use App\Models\ProductionEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildHaulageProductionSummary
{
    /**
     * @return array{
     *     today: array{
     *         trips: int,
     *         volume_m3: string,
     *         volume_ton: string,
     *         trucks: list<array{truck_id: int, name: string|null, plate: string|null, trips: int, volume_m3: string}>
     *     },
     *     in_transit: Collection<int, ProductionEntry>
     * }
     */
    public function handle(?Carbon $date = null): array
    {
        $day = ($date ?? now())->toDateString();

        $completedToday = ProductionEntry::query()
            ->completed()
            ->driverHaulage()
            ->whereNull('parent_id')
            ->whereDate('unloaded_at', $day)
            ->with(['truck:id,name,plate'])
            ->orderBy('unloaded_at')
            ->get(['id', 'truck_id', 'trips_count', 'quantity_m3', 'quantity_ton', 'unloaded_at']);

        $volumeM3 = $completedToday->reduce(
            fn (string $carry, ProductionEntry $entry): string => bcadd($carry, (string) ($entry->quantity_m3 ?? '0'), 3),
            '0.000',
        );

        $volumeTon = $completedToday->reduce(
            fn (string $carry, ProductionEntry $entry): string => bcadd($carry, (string) ($entry->quantity_ton ?? '0'), 3),
            '0.000',
        );

        $trucks = $completedToday
            ->groupBy('truck_id')
            ->map(function (Collection $entries): array {
                /** @var ProductionEntry $first */
                $first = $entries->first();

                $volume = $entries->reduce(
                    fn (string $carry, ProductionEntry $entry): string => bcadd($carry, (string) ($entry->quantity_m3 ?? '0'), 3),
                    '0.000',
                );

                return [
                    'truck_id' => (int) $first->truck_id,
                    'name' => $first->truck?->name,
                    'plate' => $first->truck?->plate,
                    'trips' => (int) $entries->sum('trips_count'),
                    'volume_m3' => $volume,
                ];
            })
            ->values()
            ->all();

        $inTransit = ProductionEntry::query()
            ->openHaulage()
            ->with([
                'truck:id,name,plate',
                'product:id,name',
                'user:id,name',
            ])
            ->latest('loaded_at')
            ->get();

        return [
            'today' => [
                'trips' => (int) $completedToday->sum('trips_count'),
                'volume_m3' => $volumeM3,
                'volume_ton' => $volumeTon,
                'trucks' => $trucks,
            ],
            'in_transit' => $inTransit,
        ];
    }
}
