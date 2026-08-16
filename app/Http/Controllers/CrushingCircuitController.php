<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCrushingCircuitRequest;
use App\Models\CrushingCircuit;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CrushingCircuitController extends Controller
{
    public function edit(): Response
    {
        $circuit = CrushingCircuit::query()
            ->with(['yields.product:id,name,code'])
            ->where('is_default', true)
            ->first()
            ?? CrushingCircuit::query()->with(['yields.product:id,name,code'])->latest('id')->first();

        if (! $circuit) {
            $circuit = CrushingCircuit::query()->create([
                'name' => 'Circuito secundário padrão',
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Distribuição proporcional média da rocha detonada após primário/secundário.',
            ]);
            $circuit->load(['yields.product:id,name,code']);
        }

        return Inertia::render('crushing-circuits/edit', [
            'circuit' => $circuit,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateCrushingCircuitRequest $request, CrushingCircuit $crushingCircuit): RedirectResponse
    {
        DB::transaction(function () use ($request, $crushingCircuit): void {
            $crushingCircuit->update([
                'name' => $request->validated('name'),
                'notes' => $request->validated('notes'),
                'is_active' => $request->boolean('is_active'),
                'is_default' => true,
            ]);

            CrushingCircuit::query()
                ->whereKeyNot($crushingCircuit->id)
                ->update(['is_default' => false]);

            $keepIds = [];

            foreach ($request->validated('yields') as $index => $yieldData) {
                $yield = $crushingCircuit->yields()->updateOrCreate(
                    ['product_id' => $yieldData['product_id']],
                    [
                        'group_name' => $yieldData['group_name'] ?? null,
                        'percent' => $yieldData['percent'],
                        'percent_min' => $yieldData['percent_min'] ?? null,
                        'percent_max' => $yieldData['percent_max'] ?? null,
                        'sort_order' => $yieldData['sort_order'] ?? ($index + 1),
                    ],
                );

                $keepIds[] = $yield->id;
            }

            $crushingCircuit->yields()
                ->whereNotIn('id', $keepIds)
                ->delete();
        });

        return redirect()
            ->route('crushing-circuits.edit')
            ->with('success', 'Circuito de britagem atualizado.');
    }
}
