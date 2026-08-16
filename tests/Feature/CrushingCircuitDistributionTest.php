<?php

namespace Tests\Feature;

use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Models\CrushingCircuit;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\CrushingCircuitSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrushingCircuitDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quarry_feed_is_distributed_across_circuit_products(): void
    {
        $this->seed([ProductSeeder::class, CrushingCircuitSeeder::class]);

        $user = User::factory()->create();
        $truck = Truck::factory()->create(['capacity_m3' => 10]);
        $feed = Product::query()->where('code', 'ROCHA')->firstOrFail();
        $feed->update(['stock_quantity' => 0, 'density' => 1.45]);

        $circuit = CrushingCircuit::query()->where('is_default', true)->firstOrFail();

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $feed->id,
                'method' => ProductionMethod::Trips->value,
                'stage' => ProductionStage::QuarryToPrimary->value,
                'truck_id' => $truck->id,
                'trips_count' => 10,
                'shift' => ProductionShift::Morning->value,
                'produced_on' => now()->toDateString(),
                'apply_circuit' => true,
                'crushing_circuit_id' => $circuit->id,
            ])
            ->assertRedirect(route('production.index'));

        $parent = ProductionEntry::query()->whereNull('parent_id')->firstOrFail();

        $this->assertFalse($parent->affects_stock);
        $this->assertSame('0.000', $feed->fresh()->stock_quantity);
        $this->assertSame(7, $parent->children()->count());

        // 10 × 10 m³ = 100 m³ × 1.45 = 145 t de alimentação
        $this->assertSame('145.000', $parent->quantity_ton);

        $brita1 = Product::query()->where('code', 'BRITA-1')->firstOrFail();
        $poPedra = Product::query()->where('code', 'PO-PEDRA')->firstOrFail();

        // 22% de 145 t = 31.900 t · 18% = 26.100 t
        $this->assertSame('31.900', $brita1->fresh()->stock_quantity);
        $this->assertSame('26.100', $poPedra->fresh()->stock_quantity);
    }

    public function test_destroying_distributed_entry_reverses_children_stock(): void
    {
        $this->seed([ProductSeeder::class, CrushingCircuitSeeder::class]);

        $user = User::factory()->create();
        $feed = Product::query()->where('code', 'ROCHA')->firstOrFail();
        $circuit = CrushingCircuit::query()->where('is_default', true)->firstOrFail();

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $feed->id,
                'method' => ProductionMethod::Quantity->value,
                'stage' => ProductionStage::QuarryToPrimary->value,
                'input_unit' => ProductUnit::Ton->value,
                'quantity_input' => 100,
                'shift' => ProductionShift::Morning->value,
                'produced_on' => now()->toDateString(),
                'apply_circuit' => true,
                'crushing_circuit_id' => $circuit->id,
            ])
            ->assertRedirect(route('production.index'));

        $parent = ProductionEntry::query()->whereNull('parent_id')->firstOrFail();
        $brita2 = Product::query()->where('code', 'BRITA-2')->firstOrFail();

        $this->assertSame('23.000', $brita2->fresh()->stock_quantity);

        $this->actingAs($user)
            ->delete(route('production.destroy', $parent))
            ->assertRedirect(route('production.index'));

        $this->assertSame(0, ProductionEntry::query()->count());
        $this->assertSame('0.000', $brita2->fresh()->stock_quantity);
    }

    public function test_circuit_yields_must_sum_to_one_hundred_percent(): void
    {
        $this->seed([ProductSeeder::class, CrushingCircuitSeeder::class]);

        $user = User::factory()->create();
        $circuit = CrushingCircuit::query()->where('is_default', true)->firstOrFail();
        $products = Product::query()->whereIn('code', ['BRITA-1', 'BRITA-2'])->get();

        $this->actingAs($user)
            ->put(route('crushing-circuits.update', $circuit), [
                'name' => $circuit->name,
                'is_active' => true,
                'notes' => null,
                'yields' => [
                    [
                        'product_id' => $products[0]->id,
                        'group_name' => 'Teste',
                        'percent' => 40,
                        'sort_order' => 1,
                    ],
                    [
                        'product_id' => $products[1]->id,
                        'group_name' => 'Teste',
                        'percent' => 40,
                        'sort_order' => 2,
                    ],
                ],
            ])
            ->assertSessionHasErrors('yields');
    }
}
