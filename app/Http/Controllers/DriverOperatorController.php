<?php

namespace App\Http\Controllers;

use App\Actions\CompleteHaulageTripAction;
use App\Actions\StartHaulageTripAction;
use App\Enums\ProductionStage;
use App\Http\Requests\CompleteHaulageTripRequest;
use App\Http\Requests\StartHaulageTripRequest;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DriverOperatorController extends Controller
{
    public function __construct(
        private StartHaulageTripAction $startHaulageTrip,
        private CompleteHaulageTripAction $completeHaulageTrip,
    ) {}

    public function index(): Response
    {
        $trucks = Truck::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'plate', 'capacity_m3']);

        $openTruckIds = ProductionEntry::query()
            ->openHaulage()
            ->pluck('truck_id')
            ->filter()
            ->all();

        return Inertia::render('driver/index', [
            'trucks' => $trucks->map(fn (Truck $truck) => [
                ...$truck->toArray(),
                'in_transit' => in_array($truck->id, $openTruckIds, true),
            ]),
            'driver' => [
                'name' => auth()->user()?->name,
            ],
        ]);
    }

    public function show(Truck $truck): Response|RedirectResponse
    {
        if (! $truck->is_active) {
            return redirect()
                ->route('driver.index')
                ->with('success', 'Este caminhão está inativo.');
        }

        $openTrip = ProductionEntry::query()
            ->with(['product:id,name,unit'])
            ->openHaulage()
            ->where('truck_id', $truck->id)
            ->first();

        $today = now()->toDateString();

        $completedToday = ProductionEntry::query()
            ->completed()
            ->where('truck_id', $truck->id)
            ->whereDate('unloaded_at', $today)
            ->get(['id', 'quantity_m3', 'quantity_ton', 'trips_count', 'unloaded_at']);

        $tripsToday = (int) $completedToday->sum('trips_count');
        $volumeM3 = $completedToday->reduce(
            fn (string $carry, ProductionEntry $entry): string => bcadd($carry, (string) ($entry->quantity_m3 ?? '0'), 3),
            '0.000',
        );

        return Inertia::render('driver/show', [
            'truck' => $truck->only(['id', 'name', 'plate', 'capacity_m3']),
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'unit', 'density']),
            'openTrip' => $openTrip,
            'summary' => [
                'trips_today' => $tripsToday,
                'volume_m3_today' => $volumeM3,
            ],
            'driver' => [
                'name' => auth()->user()?->name,
            ],
        ]);
    }

    public function load(
        StartHaulageTripRequest $request,
        Truck $truck,
    ): RedirectResponse {
        $this->startHaulageTrip->handle([
            'truck_id' => $truck->id,
            'product_id' => (int) $request->validated('product_id'),
            'user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('driver.show', $truck)
            ->with('success', 'Carregamento na lavra registrado. Siga para o primário ou para a usina.');
    }

    public function unload(CompleteHaulageTripRequest $request, Truck $truck): RedirectResponse
    {
        $stage = $request->destination();
        $entry = $this->completeHaulageTrip->handle(
            $truck,
            $stage,
            $request->shouldEnterStock(),
        );

        return redirect()
            ->route('driver.show', $truck)
            ->with('success', $this->unloadMessage($entry, $stage));
    }

    private function unloadMessage(ProductionEntry $entry, ProductionStage $stage): string
    {
        $volume = ' Viagem lançada na produção: '.$entry->quantity_m3.' m³.';

        if ($stage === ProductionStage::Plant) {
            $stockNote = $entry->affects_stock
                ? ' Estoque atualizado.'
                : ' Sem entrada no estoque.';

            return 'Descarga na usina registrada.'.$volume.$stockNote;
        }

        $message = 'Descarga no primário registrada.'.$volume;

        if ($entry->children->isNotEmpty()) {
            $message .= ' Distribuída no circuito secundário.';
        }

        return $message;
    }

    public function cancel(Truck $truck): RedirectResponse
    {
        DB::transaction(function () use ($truck): void {
            $entry = ProductionEntry::query()
                ->openHaulage()
                ->where('truck_id', $truck->id)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                throw ValidationException::withMessages([
                    'truck_id' => 'Não há viagem em andamento neste caminhão.',
                ]);
            }

            $entry->delete();
        });

        return redirect()
            ->route('driver.show', $truck)
            ->with('success', 'Carregamento na lavra cancelado.');
    }
}
