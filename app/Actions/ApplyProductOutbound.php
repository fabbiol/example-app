<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ApplyProductOutbound
{
    /**
     * Tolerância para arredondamento de conversão m³ ↔ unidade do produto.
     */
    private const COMPLETION_TOLERANCE = '0.050';

    public function handle(
        Product $product,
        string $quantity,
        ?Order $order = null,
        ?string $vehiclePlate = null,
    ): void {
        if (bccomp($quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade deve ser maior que zero.',
            ]);
        }

        if (bccomp((string) $product->stock_quantity, $quantity, 3) < 0) {
            throw ValidationException::withMessages([
                'product_id' => 'Estoque insuficiente para esta expedição. Estoque atual: '.$product->stock_quantity.' '.$product->unit->label().'.',
            ]);
        }

        $product->update([
            'stock_quantity' => bcsub((string) $product->stock_quantity, $quantity, 3),
        ]);

        if (! $order) {
            return;
        }

        $requested = (string) $order->quantity_requested;
        $loaded = bcadd((string) $order->quantity_loaded, $quantity, 3);
        $remaining = bcsub($requested, $loaded, 3);

        $fulfilled = bccomp($remaining, '0', 3) <= 0
            || bccomp($remaining, self::COMPLETION_TOLERANCE, 3) <= 0;

        if ($fulfilled) {
            $loaded = $requested;
            $status = OrderStatus::Completed;
        } else {
            $status = OrderStatus::Loading;
        }

        $order->update([
            'quantity_loaded' => $loaded,
            'status' => $status,
            'vehicle_plate' => $order->vehicle_plate ?: ($vehiclePlate ? strtoupper($vehiclePlate) : $order->vehicle_plate),
        ]);
    }
}
