<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Models\Truck;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TruckController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('trucks/index', [
            'trucks' => Truck::query()
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('trucks/create');
    }

    public function store(StoreTruckRequest $request): RedirectResponse
    {
        Truck::query()->create($request->validated());

        return redirect()
            ->route('trucks.index')
            ->with('success', 'Caminhão cadastrado.');
    }

    public function edit(Truck $truck): Response
    {
        return Inertia::render('trucks/edit', [
            'truck' => $truck,
        ]);
    }

    public function update(UpdateTruckRequest $request, Truck $truck): RedirectResponse
    {
        $truck->update($request->validated());

        return redirect()
            ->route('trucks.index')
            ->with('success', 'Caminhão atualizado.');
    }

    public function destroy(Truck $truck): RedirectResponse
    {
        $truck->delete();

        return redirect()
            ->route('trucks.index')
            ->with('success', 'Caminhão removido.');
    }
}
