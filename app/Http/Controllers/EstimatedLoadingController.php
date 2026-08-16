<?php

namespace App\Http\Controllers;

use App\Actions\RecordEstimatedLoadingAction;
use App\Actions\ReverseProductOutbound;
use App\Enums\ProductUnit;
use App\Http\Requests\StoreEstimatedLoadingRequest;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EstimatedLoadingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('estimated-loadings/index', [
            'loadings' => EstimatedLoading::query()
                ->with(['customer', 'product', 'order'])
                ->latest('loaded_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('estimated-loadings/create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get([
                'id',
                'name',
                'unit',
                'stock_quantity',
                'density',
                'bucket_capacity_m3',
            ]),
            'orders' => Order::query()
                ->with(['customer:id,name', 'product:id,name,unit,stock_quantity,density,bucket_capacity_m3'])
                ->whereIn('status', ['open', 'scheduled', 'loading'])
                ->latest()
                ->get([
                    'id',
                    'customer_id',
                    'product_id',
                    'quantity_requested',
                    'quantity_loaded',
                    'vehicle_plate',
                    'status',
                    'destination',
                ]),
            'units' => collect(ProductUnit::cases())
                ->map(fn (ProductUnit $unit) => [
                    'value' => $unit->value,
                    'label' => $unit->label(),
                ])
                ->values()
                ->all(),
            'defaults' => [
                'density' => (float) config('operations.stone_density'),
                'bucket_capacity_m3' => (float) config('operations.default_bucket_capacity_m3'),
            ],
        ]);
    }

    public function store(StoreEstimatedLoadingRequest $request, RecordEstimatedLoadingAction $action): RedirectResponse
    {
        $loading = $action->handle([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('estimated-loadings.show', $loading)
            ->with('success', 'Carregamento registrado.');
    }

    public function show(EstimatedLoading $estimatedLoading): Response
    {
        $estimatedLoading->load(['customer', 'product', 'order', 'user']);

        return Inertia::render('estimated-loadings/show', [
            'loading' => $estimatedLoading,
        ]);
    }

    public function destroy(EstimatedLoading $estimatedLoading, ReverseProductOutbound $reverseOutbound): RedirectResponse
    {
        DB::transaction(function () use ($estimatedLoading, $reverseOutbound): void {
            $loading = EstimatedLoading::query()
                ->whereKey($estimatedLoading->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::query()
                ->whereKey($loading->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $order = $loading->order_id
                ? Order::query()->whereKey($loading->order_id)->lockForUpdate()->first()
                : null;

            $reverseOutbound->handle($product, (string) $loading->quantity, $order);
            $loading->delete();
        });

        return redirect()
            ->route('estimated-loadings.index')
            ->with('success', 'Carregamento removido e estoque estornado.');
    }
}
