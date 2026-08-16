<?php

namespace App\Http\Controllers;

use App\Actions\ResolveOrderRequestedQuantity;
use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('orders/index', [
            'orders' => Order::query()
                ->with(['customer:id,name', 'product:id,name,unit,density'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('orders/create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get([
                'id',
                'name',
                'unit',
                'density',
                'stock_quantity',
            ]),
            'units' => $this->units(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(
        StoreOrderRequest $request,
        ResolveOrderRequestedQuantity $resolveQuantity,
    ): RedirectResponse {
        $product = Product::query()->findOrFail($request->validated('product_id'));
        $quantityRequested = $resolveQuantity->handle(
            $product,
            $request->validated('input_unit'),
            $request->validated('quantity_input'),
        );

        Order::query()->create([
            ...collect($request->validated())->except(['quantity_input', 'input_unit'])->all(),
            'quantity_requested' => $quantityRequested,
            'quantity_loaded' => 0,
        ]);

        return redirect()
            ->route('orders.index')
            ->with('success', 'Pedido criado.');
    }

    public function show(Order $order): Response
    {
        $order->load([
            'customer',
            'product:id,name,unit,density',
            'weighTickets' => fn ($query) => $query->latest(),
        ]);

        return Inertia::render('orders/show', [
            'order' => $order,
            'remainingQuantity' => $order->remainingQuantity(),
        ]);
    }

    public function edit(Order $order): Response
    {
        return Inertia::render('orders/edit', [
            'order' => $order->load('product:id,name,unit,density'),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get([
                'id',
                'name',
                'unit',
                'density',
                'stock_quantity',
            ]),
            'units' => $this->units(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        ResolveOrderRequestedQuantity $resolveQuantity,
    ): RedirectResponse {
        $product = Product::query()->findOrFail($request->validated('product_id'));
        $quantityRequested = $resolveQuantity->handle(
            $product,
            $request->validated('input_unit'),
            $request->validated('quantity_input'),
        );

        $order->update([
            ...collect($request->validated())->except(['quantity_input', 'input_unit'])->all(),
            'quantity_requested' => $quantityRequested,
        ]);

        return redirect()
            ->route('orders.index')
            ->with('success', 'Pedido atualizado.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with('success', 'Pedido removido.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(OrderStatus::cases())
            ->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function units(): array
    {
        return collect(ProductUnit::cases())
            ->map(fn (ProductUnit $unit) => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ])
            ->values()
            ->all();
    }
}
