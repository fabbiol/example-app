<?php

namespace Tests\Feature;

use App\Enums\ProductionStage;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\CrushingCircuitSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_is_redirected_to_driver_screen_after_login(): void
    {
        $driver = User::factory()->driver()->create([
            'email' => 'motorista@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $driver->email,
            'password' => 'password',
        ])->assertRedirect(route('driver.index'));
    }

    public function test_driver_cannot_access_admin_dashboard(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('dashboard'))
            ->assertRedirect(route('driver.index'));
    }

    public function test_driver_sees_active_trucks(): void
    {
        $driver = User::factory()->driver()->create();
        $truck = Truck::factory()->create([
            'name' => 'Basculante 01',
            'capacity_m3' => 12,
        ]);
        Truck::factory()->inactive()->create();

        $this->actingAs($driver)
            ->get(route('driver.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('driver/index')
                ->has('trucks', 1)
                ->where('trucks.0.id', $truck->id)
                ->where('trucks.0.in_transit', false));
    }

    public function test_loading_at_quarry_does_not_count_the_trip_yet(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create([
            'unit' => 'ton',
            'density' => 1.45,
            'stock_quantity' => 100,
        ]);
        $truck = Truck::factory()->create(['capacity_m3' => 10]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ])
            ->assertRedirect(route('driver.show', $truck));

        $entry = ProductionEntry::query()->firstOrFail();

        $this->assertTrue($driver->fresh()->isDriver());
        $this->assertSame($driver->id, $entry->user_id);
        $this->assertSame($truck->id, $entry->truck_id);
        $this->assertSame(1, $entry->trips_count);
        $this->assertSame('10.000', $entry->quantity_m3);
        $this->assertNotNull($entry->loaded_at);
        $this->assertNull($entry->unloaded_at);
        $this->assertFalse($entry->affects_stock);
        $this->assertSame('100.000', $product->fresh()->stock_quantity);
    }

    public function test_unloading_at_primary_counts_the_trip_and_updates_stock(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create([
            'unit' => 'ton',
            'density' => 1.45,
            'stock_quantity' => 100,
        ]);
        $truck = Truck::factory()->create(['capacity_m3' => 10]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.unload', $truck))
            ->assertRedirect(route('driver.show', $truck));

        $entry = ProductionEntry::query()->firstOrFail();

        $this->assertNotNull($entry->unloaded_at);
        $this->assertTrue($entry->affects_stock);
        $this->assertSame('14.500', $entry->quantity);
        $this->assertSame('114.500', $product->fresh()->stock_quantity);
    }

    public function test_two_completed_cycles_count_two_trips(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create([
            'unit' => 'm3',
            'density' => 1.45,
            'stock_quantity' => 0,
        ]);
        $truck = Truck::factory()->create(['capacity_m3' => 12]);

        $this->actingAs($driver);

        $this->post(route('driver.load', $truck), ['product_id' => $product->id]);
        $this->post(route('driver.unload', $truck));
        $this->post(route('driver.load', $truck), ['product_id' => $product->id]);
        $this->post(route('driver.unload', $truck));

        $this->assertSame(2, ProductionEntry::query()->completed()->count());
        $this->assertSame('24.000', $product->fresh()->stock_quantity);
    }

    public function test_cannot_start_a_second_trip_while_one_is_open(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create();
        $truck = Truck::factory()->create();

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->from(route('driver.show', $truck))
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ])
            ->assertRedirect(route('driver.show', $truck))
            ->assertSessionHasErrors('truck_id');

        $this->assertSame(1, ProductionEntry::query()->count());
    }

    public function test_cancel_open_trip_does_not_change_stock(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create(['stock_quantity' => 40]);
        $truck = Truck::factory()->create();

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.cancel', $truck))
            ->assertRedirect(route('driver.show', $truck));

        $this->assertSame(0, ProductionEntry::query()->count());
        $this->assertSame('40.000', $product->fresh()->stock_quantity);
    }

    public function test_open_trip_does_not_appear_in_office_production_list(): void
    {
        $driver = User::factory()->driver()->create();
        $admin = User::factory()->create();
        $product = Product::factory()->create();
        $truck = Truck::factory()->create();

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($admin)
            ->get(route('production.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('production/index')
                ->has('entries.data', 0)
                ->has('in_transit', 1)
                ->where('haulage_today.trips', 0)
                ->where('in_transit.0.truck_id', $truck->id));
    }

    public function test_completed_trip_appears_in_office_production(): void
    {
        $driver = User::factory()->driver()->create();
        $admin = User::factory()->create();
        $product = Product::factory()->create();
        $truck = Truck::factory()->create([
            'name' => 'Basculante 01',
            'plate' => 'ABC1D23',
            'capacity_m3' => 10,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.unload', $truck));

        $this->actingAs($admin)
            ->get(route('production.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('production/index')
                ->has('entries.data', 1)
                ->has('in_transit', 0)
                ->where('haulage_today.trips', 1)
                ->where('haulage_today.volume_m3', '10.000')
                ->where('entries.data.0.trips_count', 1)
                ->where('entries.data.0.method', 'trips')
                ->where('haulage_today.trucks.0.plate', 'ABC1D23'));

        $this->actingAs($admin)
            ->get(route('production.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('production/create')
                ->where('haulage_today.trips', 1)
                ->where('haulage_today.trucks.0.trips', 1));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('totals.haulage_trips_today', 1));
    }

    public function test_unloading_distributes_on_default_crushing_circuit(): void
    {
        $this->seed([ProductSeeder::class, CrushingCircuitSeeder::class]);

        $driver = User::factory()->driver()->create();
        $feed = Product::query()->where('code', 'ROCHA')->firstOrFail();
        $feed->update(['stock_quantity' => 0, 'density' => 1.45]);
        $truck = Truck::factory()->create(['capacity_m3' => 10]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $feed->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.unload', $truck))
            ->assertRedirect(route('driver.show', $truck));

        $parent = ProductionEntry::query()->whereNull('parent_id')->firstOrFail();
        $brita1 = Product::query()->where('code', 'BRITA-1')->firstOrFail();
        $poPedra = Product::query()->where('code', 'PO-PEDRA')->firstOrFail();

        $this->assertFalse($parent->affects_stock);
        $this->assertNotNull($parent->unloaded_at);
        $this->assertSame(7, $parent->children()->count());
        $this->assertSame('0.000', $feed->fresh()->stock_quantity);
        $this->assertSame('14.500', $parent->quantity_ton);
        $this->assertSame('3.190', $brita1->fresh()->stock_quantity);
        $this->assertSame('2.610', $poPedra->fresh()->stock_quantity);
    }

    public function test_unloading_at_plant_can_enter_stock_without_circuit(): void
    {
        $this->seed([ProductSeeder::class, CrushingCircuitSeeder::class]);

        $driver = User::factory()->driver()->create();
        $product = Product::query()->where('code', 'BRITA-1')->firstOrFail();
        $product->update(['stock_quantity' => 10, 'density' => 1.45]);
        $truck = Truck::factory()->create(['capacity_m3' => 10]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.unload', $truck), [
                'stage' => ProductionStage::Plant->value,
                'affects_stock' => true,
            ])
            ->assertRedirect(route('driver.show', $truck));

        $entry = ProductionEntry::query()->whereNull('parent_id')->firstOrFail();
        $feed = Product::query()->where('code', 'ROCHA')->firstOrFail();

        $this->assertSame(ProductionStage::Plant, $entry->stage);
        $this->assertTrue($entry->affects_stock);
        $this->assertNull($entry->crushing_circuit_id);
        $this->assertSame(0, $entry->children()->count());
        $this->assertSame('24.500', $product->fresh()->stock_quantity);
        $this->assertSame('0.000', $feed->fresh()->stock_quantity);
    }

    public function test_unloading_at_plant_can_skip_stock(): void
    {
        $driver = User::factory()->driver()->create();
        $product = Product::factory()->create([
            'unit' => 'ton',
            'density' => 1.45,
            'stock_quantity' => 50,
        ]);
        $truck = Truck::factory()->create(['capacity_m3' => 10]);

        $this->actingAs($driver)
            ->post(route('driver.load', $truck), [
                'product_id' => $product->id,
            ]);

        $this->actingAs($driver)
            ->post(route('driver.unload', $truck), [
                'stage' => ProductionStage::Plant->value,
                'affects_stock' => false,
            ])
            ->assertRedirect(route('driver.show', $truck));

        $entry = ProductionEntry::query()->firstOrFail();

        $this->assertSame(ProductionStage::Plant, $entry->stage);
        $this->assertFalse($entry->affects_stock);
        $this->assertNotNull($entry->unloaded_at);
        $this->assertSame('50.000', $product->fresh()->stock_quantity);
    }
}
