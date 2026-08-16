<?php

namespace App\Http\Controllers;

use App\Actions\RecordProductionEntryAction;
use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Http\Requests\StoreProductionEntryRequest;
use App\Models\CrushingCircuit;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductionEntryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('production/index', [
            'entries' => ProductionEntry::query()
                ->with(['product', 'truck', 'user', 'children.product'])
                ->whereNull('parent_id')
                ->latest('produced_on')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $defaultCircuit = CrushingCircuit::query()
            ->with(['yields.product:id,name,code'])
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        return Inertia::render('production/create', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get([
                'id',
                'name',
                'code',
                'unit',
                'density',
                'bucket_capacity_m3',
            ]),
            'trucks' => Truck::query()->where('is_active', true)->orderBy('name')->get([
                'id',
                'name',
                'plate',
                'capacity_m3',
            ]),
            'circuits' => CrushingCircuit::query()
                ->with(['yields.product:id,name,code'])
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'defaultCircuitId' => $defaultCircuit?->id,
            'methods' => collect(ProductionMethod::cases())
                ->map(fn (ProductionMethod $method) => [
                    'value' => $method->value,
                    'label' => $method->label(),
                    'available' => $method->isAvailable(),
                ])
                ->values()
                ->all(),
            'stages' => collect(ProductionStage::cases())
                ->map(fn (ProductionStage $stage) => [
                    'value' => $stage->value,
                    'label' => $stage->label(),
                ])
                ->values()
                ->all(),
            'units' => collect(ProductUnit::cases())
                ->map(fn (ProductUnit $unit) => [
                    'value' => $unit->value,
                    'label' => $unit->label(),
                ])
                ->values()
                ->all(),
            'shifts' => collect(ProductionShift::cases())
                ->map(fn (ProductionShift $shift) => [
                    'value' => $shift->value,
                    'label' => $shift->label(),
                ])
                ->values()
                ->all(),
            'defaults' => [
                'density' => (float) config('operations.stone_density'),
                'truck_capacity_m3' => (float) config('operations.default_bucket_capacity_m3'),
            ],
        ]);
    }

    public function store(StoreProductionEntryRequest $request, RecordProductionEntryAction $action): RedirectResponse
    {
        $entry = $action->handle([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
        ]);

        $message = $entry->children()->exists()
            ? 'Alimentação registrada e distribuída no circuito secundário.'
            : 'Produção registrada e estoque atualizado.';

        return redirect()
            ->route('production.index')
            ->with('success', $message);
    }

    public function destroy(ProductionEntry $productionEntry): RedirectResponse
    {
        DB::transaction(function () use ($productionEntry): void {
            $entry = ProductionEntry::query()
                ->with('children')
                ->whereKey($productionEntry->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($entry->children as $child) {
                $this->reverseStock($child);
                $child->delete();
            }

            $this->reverseStock($entry);
            $entry->delete();
        });

        return redirect()
            ->route('production.index')
            ->with('success', 'Apontamento removido e estoque ajustado.');
    }

    private function reverseStock(ProductionEntry $entry): void
    {
        if (! $entry->affects_stock) {
            return;
        }

        $product = Product::query()
            ->whereKey($entry->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $newStock = bcsub((string) $product->stock_quantity, (string) $entry->quantity, 3);

        if (bccomp($newStock, '0', 3) < 0) {
            $newStock = '0.000';
        }

        $product->update([
            'stock_quantity' => $newStock,
        ]);
    }
}
