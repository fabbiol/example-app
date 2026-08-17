<?php

namespace App\Actions;

use App\Enums\ProductUnit;
use App\Models\CaixaEntry;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
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
     *     caixa_id?: int|null,
     *     customer_id?: int|null,
     *     product_id?: int|null,
     *     items?: list<array{product_id?: int|null, input_unit?: string|null, quantity_input?: float|string|null}>,
     *     user_id?: int|null,
     *     vehicle_plate: string,
     *     input_unit?: string|null,
     *     quantity_input?: float|string|null,
     *     loaded_at?: string|null,
     *     notes?: string|null,
     *     confirmed_by_loader?: bool
     * }  $data
     */
    public function handle(array $data): EstimatedLoading
    {
        return DB::transaction(function () use ($data) {
            [$order, $customerId] = $this->resolveParties($data);
            $lines = $this->preparedLines($data, $order);

            $first = $lines[0];
            $totalM3 = '0.000';
            $totalTon = '0.000';

            foreach ($lines as $line) {
                $totalM3 = bcadd($totalM3, $line['quantity_m3'], 3);
                $totalTon = bcadd($totalTon, $line['quantity_ton'], 3);
            }

            $caixaId = ! empty($data['caixa_id']) ? (int) $data['caixa_id'] : null;
            $caixaEntry = $caixaId ? $this->assertCaixaAvailable($caixaId) : null;

            try {
                $loading = EstimatedLoading::query()->create([
                    'number' => $this->nextNumber(),
                    'order_id' => $order?->id,
                    'caixa_id' => $caixaId,
                    'caixa_number' => $caixaEntry instanceof CaixaEntry
                        ? $caixaEntry->orderNumber()
                        : null,
                    'customer_id' => $customerId,
                    'product_id' => $first['product']->id,
                    'user_id' => $data['user_id'] ?? null,
                    'vehicle_plate' => strtoupper($data['vehicle_plate']),
                    'buckets_count' => null,
                    'bucket_capacity_m3' => null,
                    'input_unit' => $first['input_unit'],
                    'quantity_m3' => $totalM3,
                    'quantity_ton' => $totalTon,
                    'quantity' => $first['quantity'],
                    'density' => $first['density'],
                    'loaded_at' => $data['loaded_at'] ?? now(),
                    'notes' => $data['notes'] ?? null,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($caixaId) {
                    throw ValidationException::withMessages([
                        'caixa_id' => 'Este número de pedido do caixa já foi usado.',
                    ]);
                }

                throw $exception;
            }

            foreach ($lines as $index => $line) {
                $loading->items()->create([
                    'product_id' => $line['product']->id,
                    'sort_order' => $index,
                    'input_unit' => $line['input_unit']->value,
                    'quantity_m3' => $line['quantity_m3'],
                    'quantity_ton' => $line['quantity_ton'],
                    'quantity' => $line['quantity'],
                    'density' => $line['density'],
                    'loader_loaded_at' => ! empty($data['confirmed_by_loader']) ? now() : null,
                ]);

                $this->applyOutbound->handle(
                    $line['product'],
                    $line['quantity'],
                    $order?->product_id === $line['product']->id ? $order : null,
                    $data['vehicle_plate'],
                );
            }

            return $loading->fresh(['customer', 'product', 'order', 'items.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: ?Order, 1: int}
     */
    private function resolveParties(array $data): array
    {
        $order = null;
        $customerId = $data['customer_id'] ?? null;

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
        }

        if (! $customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Informe um pedido ou selecione o cliente.',
            ]);
        }

        return [$order, (int) $customerId];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{
     *     product: Product,
     *     input_unit: ProductUnit,
     *     quantity_m3: string,
     *     quantity_ton: string,
     *     quantity: string,
     *     density: string
     * }>
     */
    private function preparedLines(array $data, ?Order $order): array
    {
        $rawItems = $data['items'] ?? [];

        if (! is_array($rawItems) || $rawItems === []) {
            $rawItems = [[
                'product_id' => $data['product_id'] ?? $order?->product_id,
                'input_unit' => $data['input_unit'] ?? null,
                'quantity_input' => $data['quantity_input'] ?? null,
            ]];
        }

        $lines = [];
        $seen = [];

        foreach (array_values($rawItems) as $index => $rawItem) {
            $productId = $rawItem['product_id'] ?? null;

            if (! $productId) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Selecione o produto.',
                ]);
            }

            $productId = (int) $productId;

            if (isset($seen[$productId])) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Este produto já foi informado neste carregamento.',
                ]);
            }

            $seen[$productId] = true;

            $product = Product::query()
                ->whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $quantityInput = $rawItem['quantity_input'] ?? null;

            if ($quantityInput === null || bccomp((string) $quantityInput, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity_input" => 'Informe a quantidade estimada.',
                ]);
            }

            $inputUnit = ProductUnit::from($rawItem['input_unit'] ?? $product->unit->value);
            $converter = $product->converter();
            $quantities = $converter->from($inputUnit, $quantityInput);

            $lines[] = [
                'product' => $product,
                'input_unit' => $inputUnit,
                'quantity_m3' => $quantities['quantity_m3'],
                'quantity_ton' => $quantities['quantity_ton'],
                'quantity' => $converter->forProductUnit($product->unit, $quantities),
                'density' => number_format($converter->density, 3, '.', ''),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Informe ao menos um produto com quantidade.',
            ]);
        }

        return $lines;
    }

    private function assertCaixaAvailable(int $caixaId): CaixaEntry
    {
        if (! CaixaEntry::isConfigured()) {
            throw ValidationException::withMessages([
                'caixa_id' => 'O banco do caixa não está configurado.',
            ]);
        }

        $entry = CaixaEntry::query()->withoutSaida()->whereKey($caixaId)->first();

        if (! $entry) {
            throw ValidationException::withMessages([
                'caixa_id' => 'Este número de pedido do caixa não está disponível.',
            ]);
        }

        $alreadyUsed = EstimatedLoading::query()
            ->where('caixa_id', $caixaId)
            ->lockForUpdate()
            ->exists();

        if ($alreadyUsed) {
            throw ValidationException::withMessages([
                'caixa_id' => 'Este número de pedido do caixa já foi usado.',
            ]);
        }

        return $entry;
    }

    private function nextNumber(): string
    {
        $sequence = EstimatedLoading::query()->lockForUpdate()->count() + 1;

        return 'EST-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
