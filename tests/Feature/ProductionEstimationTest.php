<?php

namespace Tests\Feature;

use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionEstimationTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_by_truck_trips_increases_stock(): void
    {
        $user = User::factory()->create();
        $truck = Truck::factory()->create(['capacity_m3' => 10]);
        $product = Product::factory()->create([
            'name' => 'Rachão',
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 20,
        ]);

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $product->id,
                'method' => ProductionMethod::Trips->value,
                'stage' => ProductionStage::QuarryToPrimary->value,
                'truck_id' => $truck->id,
                'trips_count' => 5,
                'shift' => ProductionShift::Morning->value,
                'produced_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('production.index'));

        $entry = ProductionEntry::query()->firstOrFail();

        // 5 × 10 m³ = 50 m³ × 1.45 = 72.5 t
        $this->assertSame(ProductionMethod::Trips, $entry->method);
        $this->assertSame(ProductionStage::QuarryToPrimary, $entry->stage);
        $this->assertSame(5, $entry->trips_count);
        $this->assertSame('50.000', $entry->quantity_m3);
        $this->assertSame('72.500', $entry->quantity_ton);
        $this->assertSame('72.500', $entry->quantity);
        $this->assertSame('92.500', $product->fresh()->stock_quantity);
    }

    public function test_plant_product_can_be_estimated_by_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Pedrisco',
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $product->id,
                'method' => ProductionMethod::Quantity->value,
                'stage' => ProductionStage::Plant->value,
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => 20,
                'shift' => ProductionShift::Afternoon->value,
                'produced_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('production.index'));

        $entry = ProductionEntry::query()->firstOrFail();

        $this->assertSame('20.000', $entry->quantity_m3);
        $this->assertSame('29.000', $entry->quantity_ton);
        $this->assertSame('39.000', $product->fresh()->stock_quantity);
    }

    public function test_scale_method_is_blocked_until_available(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $product->id,
                'method' => ProductionMethod::Scale->value,
                'stage' => ProductionStage::Plant->value,
                'shift' => ProductionShift::Morning->value,
                'produced_on' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('method');

        $this->assertSame(0, ProductionEntry::query()->count());
    }

    public function test_user_can_manage_trucks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('trucks.store'), [
                'name' => 'Basculante 99',
                'plate' => 'abc1d23',
                'capacity_m3' => 12.5,
            ])
            ->assertRedirect(route('trucks.index'));

        $truck = Truck::query()->where('plate', 'ABC1D23')->first();

        $this->assertNotNull($truck);
        $this->assertSame('12.500', $truck->capacity_m3);
    }
}
