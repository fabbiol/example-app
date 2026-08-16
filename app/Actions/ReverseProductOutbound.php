<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;

class ReverseProductOutbound
{
    public function handle(
        Product $product,
        string $quantity,
        ?Order $order = null,
    ): void {
        $product->update([
            'stock_quantity' => bcadd((string) $product->stock_quantity, $quantity, 3),
        ]);

        if (! $order) {
            return;
        }

        $loaded = bcsub((string) $order->quantity_loaded, $quantity, 3);

        if (bccomp($loaded, '0', 3) < 0) {
            $loaded = '0.000';
        }

        $order->update([
            'quantity_loaded' => $loaded,
            'status' => bccomp($loaded, '0', 3) <= 0
                ? OrderStatus::Open
                : OrderStatus::Loading,
        ]);
    }
}
