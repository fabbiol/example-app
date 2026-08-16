<?php

namespace App\Actions;

use App\Enums\ProductUnit;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordEstimatedLoadingAction
{
    public function __construct(
        private ApplyProductOutbound $applyOutbound,
    ) {}

    /**
     * @param  array{
     *     order_id?: int|null,
     *     customer_id?: int|null,
     *     product_id?: int|null,
     *     user_id?: int|null,
     *     vehicle_plate: string,
     *     mode: 'quantity'|'buckets',
     *     input_unit?: string|null,
     *     quantity_input?: float|string|null,
     *     buckets_count?: int|null,
     *     bucket_capacity_m3?: float|string|null,
     *     loaded_at?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function handle(array $data): EstimatedLoading
    {
        return DB::transaction(function () use ($data) {
            [$order, $customerId, $productId] = $this->resolveParties($data);

            $product = Product::query()
                ->whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $converter = $product->converter();
            $bucketsCount = null;
            $bucketCapacity = null;

            if (($data['mode'] ?? 'quantity') === 'buckets') {
                $bucketsCount = (int) ($data['buckets_count'] ?? 0);
                $bucketCapacity = number_format(
                    (float) ($data['bucket_capacity_m3'] ?? $product->bucket_capacity_m3),
                    3,
                    '.',
                    '',
                );

                if ($bucketsCount <= 0) {
                    throw ValidationException::withMessages([
                        'buckets_count' => 'Informe a quantidade de conchas.',
                    ]);
                }

                $quantities = $converter->fromBuckets($bucketsCount, $bucketCapacity);
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

            $loading = EstimatedLoading::query()->create([
                'number' => $this->nextNumber(),
                'order_id' => $order?->id,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'user_id' => $data['user_id'] ?? null,
                'vehicle_plate' => strtoupper($data['vehicle_plate']),
                'buckets_count' => $bucketsCount,
                'bucket_capacity_m3' => $bucketCapacity,
                'input_unit' => $inputUnit,
                'quantity_m3' => $quantities['quantity_m3'],
                'quantity_ton' => $quantities['quantity_ton'],
                'quantity' => $quantity,
                'density' => number_format($converter->density, 3, '.', ''),
                'loaded_at' => $data['loaded_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->applyOutbound->handle(
                $product,
                $quantity,
                $order,
                $data['vehicle_plate'],
            );

            return $loading->fresh(['customer', 'product', 'order']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: ?Order, 1: int, 2: int}
     */
    private function resolveParties(array $data): array
    {
        $order = null;
        $customerId = $data['customer_id'] ?? null;
        $productId = $data['product_id'] ?? null;

        if (! empty($data['order_id'])) {
            $order = Order::query()
                ->whereKey($data['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $order->isOpenForLoading()) {
                throw ValidationException::withMessages([
                    'order_id' => 'Este pedido não está aberto para carregamento.',
                ]);
            }

            $customerId = $order->customer_id;
            $productId = $order->product_id;
        }

        if (! $customerId || ! $productId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Informe um pedido ou selecione cliente e produto.',
            ]);
        }

        return [$order, (int) $customerId, (int) $productId];
    }

    private function nextNumber(): string
    {
        $sequence = EstimatedLoading::query()->lockForUpdate()->count() + 1;

        return 'EST-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
