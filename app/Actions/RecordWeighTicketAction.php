<?php

namespace App\Actions;

use App\Enums\ProductUnit;
use App\Models\Order;
use App\Models\Product;
use App\Models\WeighTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordWeighTicketAction
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
     *     tare_weight: float|string,
     *     gross_weight: float|string,
     *     weighed_at?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function handle(array $data): WeighTicket
    {
        return DB::transaction(function () use ($data) {
            $tare = number_format((float) $data['tare_weight'], 3, '.', '');
            $gross = number_format((float) $data['gross_weight'], 3, '.', '');
            $netTons = bcsub($gross, $tare, 3);

            if (bccomp($netTons, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    'gross_weight' => 'O peso bruto deve ser maior que a tara.',
                ]);
            }

            [$order, $customerId, $productId] = $this->resolveParties($data);

            $product = Product::query()
                ->whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $converter = $product->converter();
            $quantities = $converter->from(ProductUnit::Ton, $netTons);
            $quantity = $converter->forProductUnit($product->unit, $quantities);

            $ticket = WeighTicket::query()->create([
                'number' => $this->nextNumber(),
                'order_id' => $order?->id,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'user_id' => $data['user_id'] ?? null,
                'vehicle_plate' => strtoupper($data['vehicle_plate']),
                'tare_weight' => $tare,
                'gross_weight' => $gross,
                'net_weight' => $netTons,
                'quantity' => $quantity,
                'quantity_m3' => $quantities['quantity_m3'],
                'density' => number_format($converter->density, 3, '.', ''),
                'weighed_at' => $data['weighed_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->applyOutbound->handle(
                $product,
                $quantity,
                $order,
                $data['vehicle_plate'],
            );

            return $ticket->fresh(['customer', 'product', 'order']);
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
        $sequence = WeighTicket::query()->lockForUpdate()->count() + 1;

        return 'TK-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
