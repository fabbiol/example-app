<?php

namespace App\Http\Controllers;

use App\Actions\RecordEstimatedLoadingAction;
use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Http\Requests\StoreLoaderEstimatedLoadingRequest;
use App\Models\EstimatedLoading;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LoaderOperatorController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()
            ->with(['customer:id,name', 'product:id,name,unit,density,bucket_capacity_m3,stock_quantity'])
            ->whereIn('status', ['open', 'scheduled', 'loading'])
            ->latest('scheduled_at')
            ->latest('id')
            ->get()
            ->map(function (Order $order) {
                [$remaining, $remainingM3, $remainingTon] = $this->remainingQuantities($order);

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'vehicle_plate' => $order->vehicle_plate,
                    'destination' => $order->destination,
                    'quantity_requested' => $order->quantity_requested,
                    'quantity_loaded' => $order->quantity_loaded,
                    'remaining' => number_format($remaining, 3, '.', ''),
                    'remaining_m3' => number_format($remainingM3, 3, '.', ''),
                    'remaining_ton' => number_format($remainingTon, 3, '.', ''),
                    'customer' => $order->customer,
                    'product' => $order->product,
                ];
            });

        $recent = EstimatedLoading::query()
            ->with(['customer:id,name', 'product:id,name,unit', 'order:id'])
            ->where('user_id', auth()->id())
            ->latest('loaded_at')
            ->limit(5)
            ->get();

        return Inertia::render('loader/index', [
            'orders' => $orders,
            'recent' => $recent,
            'operator' => [
                'name' => auth()->user()?->name,
            ],
        ]);
    }

    public function show(Order $order): Response|RedirectResponse
    {
        if (! $order->isOpenForLoading()) {
            return redirect()
                ->route('loader.index')
                ->with('success', 'Este pedido não está mais aberto para carregamento.');
        }

        $order->load(['customer:id,name', 'product:id,name,unit,density,bucket_capacity_m3,stock_quantity']);

        [$remaining, $remainingM3, $remainingTon] = $this->remainingQuantities($order);
        $density = (float) ($order->product?->density ?? config('operations.stone_density'));
        $suggestedM3 = $remainingM3 > 0 ? round($remainingM3, 3) : 1.0;

        return Inertia::render('loader/show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'vehicle_plate' => $order->vehicle_plate,
                'destination' => $order->destination,
                'quantity_requested' => $order->quantity_requested,
                'quantity_loaded' => $order->quantity_loaded,
                'remaining' => number_format($remaining, 3, '.', ''),
                'remaining_m3' => number_format($remainingM3, 3, '.', ''),
                'remaining_ton' => number_format($remainingTon, 3, '.', ''),
                'suggested_m3' => number_format($suggestedM3, 3, '.', ''),
                'density' => number_format($density, 3, '.', ''),
                'customer' => $order->customer,
                'product' => $order->product,
            ],
        ]);
    }

    public function store(
        StoreLoaderEstimatedLoadingRequest $request,
        Order $order,
        RecordEstimatedLoadingAction $action,
    ): RedirectResponse {
        if (! $order->isOpenForLoading()) {
            return redirect()
                ->route('loader.index')
                ->with('success', 'Este pedido não está mais aberto para carregamento.');
        }

        $order->loadMissing('product');

        $product = $order->product;
        $inputM3 = number_format((float) $request->validated('quantity_m3'), 3, '.', '');
        [$remaining, $remainingM3] = $this->remainingQuantities($order);
        $remainingProduct = number_format($remaining, 3, '.', '');
        $remainingM3Formatted = number_format($remainingM3, 3, '.', '');

        $coversRemaining = bccomp($inputM3, $remainingM3Formatted, 3) >= 0
            || ($product && bccomp(
                $product->converter()->forProductUnit(
                    $product->unit,
                    $product->converter()->from(ProductUnit::CubicMeter, $inputM3),
                ),
                $remainingProduct,
                3,
            ) >= 0);

        // Ao cobrir o restante, baixa exatamente o saldo do pedido (evita ficar em "carregando" por arredondamento).
        if ($coversRemaining && $product && bccomp($remainingProduct, '0', 3) > 0) {
            $loading = $action->handle([
                'order_id' => $order->id,
                'vehicle_plate' => $request->validated('vehicle_plate'),
                'mode' => 'quantity',
                'input_unit' => $product->unit->value,
                'quantity_input' => $remainingProduct,
                'notes' => $request->validated('notes'),
                'user_id' => $request->user()?->id,
            ]);
        } else {
            $loading = $action->handle([
                'order_id' => $order->id,
                'vehicle_plate' => $request->validated('vehicle_plate'),
                'mode' => 'quantity',
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => $inputM3,
                'notes' => $request->validated('notes'),
                'user_id' => $request->user()?->id,
            ]);
        }

        $order->refresh();

        $message = $order->status === OrderStatus::Completed
            ? 'Carregamento registrado. Pedido concluído.'
            : 'Carregamento registrado.';

        return redirect()
            ->route('loader.done', $loading)
            ->with('success', $message);
    }

    public function done(EstimatedLoading $estimatedLoading): Response
    {
        $estimatedLoading->load(['customer:id,name', 'product:id,name,unit', 'order:id,status']);

        return Inertia::render('loader/done', [
            'loading' => $estimatedLoading,
        ]);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function remainingQuantities(Order $order): array
    {
        $requested = (float) $order->quantity_requested;
        $loaded = (float) $order->quantity_loaded;
        $remaining = max(0, $requested - $loaded);
        $product = $order->product;
        $density = (float) ($product?->density ?? config('operations.stone_density'));

        $remainingM3 = $product?->unit === ProductUnit::CubicMeter
            ? $remaining
            : ($density > 0 ? $remaining / $density : 0);

        $remainingTon = $product?->unit === ProductUnit::Ton
            ? $remaining
            : $remaining * $density;

        return [$remaining, $remainingM3, $remainingTon];
    }
}
