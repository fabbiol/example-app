<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\ProductionStage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class BuildFlowDiagram
{
    /**
     * @return array{
     *     expedition: array<string, array{orders: int, requested_ton: numeric-string, requested_m3: numeric-string, loaded_ton: numeric-string, loaded_m3: numeric-string, remaining_ton: numeric-string, remaining_m3: numeric-string}>,
     *     yard: array<string, array{entries: int, ton: numeric-string, m3: numeric-string}>
     * }
     */
    public function handle(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        return [
            'expedition' => $this->expedition($from, $to),
            'yard' => $this->yard($from, $to),
        ];
    }

    /**
     * @return array<string, array{orders: int, requested_ton: numeric-string, requested_m3: numeric-string, loaded_ton: numeric-string, loaded_m3: numeric-string, remaining_ton: numeric-string, remaining_m3: numeric-string}>
     */
    private function expedition(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $ordersTable = (new Order)->getTable();
        $productsTable = (new Product)->getTable();

        $query = Order::query()
            ->join($productsTable, $productsTable.'.id', '=', $ordersTable.'.product_id');

        $this->constrainDate($query, $ordersTable.'.created_at', $from, $to);

        $rows = $query
            ->toBase()
            ->selectRaw('orders.status as status')
            ->selectRaw('count(*) as orders_count')
            ->selectRaw("coalesce(sum(case when products.unit = 'ton' then orders.quantity_requested else orders.quantity_requested * coalesce(nullif(products.density, 0), 1.45) end), 0) as requested_ton")
            ->selectRaw("coalesce(sum(case when products.unit = 'm3' then orders.quantity_requested else orders.quantity_requested / coalesce(nullif(products.density, 0), 1.45) end), 0) as requested_m3")
            ->selectRaw("coalesce(sum(case when products.unit = 'ton' then orders.quantity_loaded else orders.quantity_loaded * coalesce(nullif(products.density, 0), 1.45) end), 0) as loaded_ton")
            ->selectRaw("coalesce(sum(case when products.unit = 'm3' then orders.quantity_loaded else orders.quantity_loaded / coalesce(nullif(products.density, 0), 1.45) end), 0) as loaded_m3")
            ->groupBy('orders.status')
            ->get()
            ->keyBy('status');

        $phases = [];

        foreach (OrderStatus::cases() as $status) {
            $row = (array) $rows->get($status->value);
            $requestedTonQty = $this->decimal($row['requested_ton'] ?? 0);
            $requestedM3Qty = $this->decimal($row['requested_m3'] ?? 0);
            $loadedTonQty = $this->decimal($row['loaded_ton'] ?? 0);
            $loadedM3Qty = $this->decimal($row['loaded_m3'] ?? 0);

            $phases[$status->value] = [
                'orders' => (int) ($row['orders_count'] ?? 0),
                'requested_ton' => $requestedTonQty,
                'requested_m3' => $requestedM3Qty,
                'loaded_ton' => $loadedTonQty,
                'loaded_m3' => $loadedM3Qty,
                'remaining_ton' => $this->nonNegative(bcsub($requestedTonQty, $loadedTonQty, 3)),
                'remaining_m3' => $this->nonNegative(bcsub($requestedM3Qty, $loadedM3Qty, 3)),
            ];
        }

        return $phases;
    }

    /**
     * @return array<string, array{entries: int, ton: numeric-string, m3: numeric-string}>
     */
    private function yard(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $parentsQuery = ProductionEntry::query()->whereNull('parent_id')->completed();
        $this->constrainDate($parentsQuery, 'produced_on', $from, $to);

        $parents = $parentsQuery
            ->toBase()
            ->selectRaw('stage')
            ->selectRaw('case when crushing_circuit_id is null then 0 else 1 end as with_circuit')
            ->selectRaw('count(*) as entries_count')
            ->selectRaw('coalesce(sum(quantity_ton), 0) as ton')
            ->selectRaw('coalesce(sum(quantity_m3), 0) as m3')
            ->groupByRaw('stage, case when crushing_circuit_id is null then 0 else 1 end')
            ->get();

        $plant = $this->emptyYardPhase();
        $primaryPlain = $this->emptyYardPhase();
        $primaryCircuit = $this->emptyYardPhase();
        $quarry = $this->emptyYardPhase();
        $entries = $this->emptyYardPhase();

        foreach ($parents as $row) {
            $data = (array) $row;
            $phase = [
                'entries' => (int) ($data['entries_count'] ?? 0),
                'ton' => $this->decimal($data['ton'] ?? 0),
                'm3' => $this->decimal($data['m3'] ?? 0),
            ];

            $entries = $this->addYardPhases($entries, $phase);
            $stage = ProductionStage::from((string) ($data['stage'] ?? ProductionStage::Plant->value));

            if ($stage === ProductionStage::Plant) {
                $plant = $this->addYardPhases($plant, $phase);

                continue;
            }

            $quarry = $this->addYardPhases($quarry, $phase);

            if ((int) ($data['with_circuit'] ?? 0) === 1) {
                $primaryCircuit = $this->addYardPhases($primaryCircuit, $phase);

                continue;
            }

            $primaryPlain = $this->addYardPhases($primaryPlain, $phase);
        }

        $childrenQuery = ProductionEntry::query()->whereNotNull('parent_id');
        $this->constrainDate($childrenQuery, 'produced_on', $from, $to);

        $children = $childrenQuery
            ->toBase()
            ->selectRaw('count(*) as entries_count')
            ->selectRaw('coalesce(sum(quantity_ton), 0) as ton')
            ->selectRaw('coalesce(sum(quantity_m3), 0) as m3')
            ->first();

        $childData = $children === null ? [] : (array) $children;

        return [
            'quarry' => $quarry,
            'entries' => $entries,
            'plant' => $plant,
            'primary_plain' => $primaryPlain,
            'primary_circuit' => $primaryCircuit,
            'circuit_products' => [
                'entries' => (int) ($childData['entries_count'] ?? 0),
                'ton' => $this->decimal($childData['ton'] ?? 0),
                'm3' => $this->decimal($childData['m3'] ?? 0),
            ],
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function constrainDate(Builder $query, string $column, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from->toDateString());
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to->toDateString());
        }
    }

    /**
     * @param  array{entries: int, ton: numeric-string, m3: numeric-string}  $left
     * @param  array{entries: int, ton: numeric-string, m3: numeric-string}  $right
     * @return array{entries: int, ton: numeric-string, m3: numeric-string}
     */
    private function addYardPhases(array $left, array $right): array
    {
        return [
            'entries' => $left['entries'] + $right['entries'],
            'ton' => $this->decimal(bcadd($left['ton'], $right['ton'], 3)),
            'm3' => $this->decimal(bcadd($left['m3'], $right['m3'], 3)),
        ];
    }

    /**
     * @return array{entries: int, ton: numeric-string, m3: numeric-string}
     */
    private function emptyYardPhase(): array
    {
        return [
            'entries' => 0,
            'ton' => '0.000',
            'm3' => '0.000',
        ];
    }

    /**
     * @return numeric-string
     */
    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function nonNegative(string $value): string
    {
        if (bccomp($value, '0', 3) < 0) {
            return '0.000';
        }

        return $this->decimal($value);
    }
}
