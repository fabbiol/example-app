<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\ProductionStage;
use App\Enums\ProductUnit;
use App\Models\CrushingCircuit;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_flow_page(): void
    {
        $this->get(route('flow'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_view_the_flow_page(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('flow'))
            ->assertRedirect(route('loader.index'));
    }

    public function test_admin_can_view_the_flow_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('flow'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('flow/index')
                ->where('filters.period', 'today')
                ->where('filters.from', now()->toDateString())
                ->where('filters.to', now()->toDateString())
                ->where('periods.0.value', 'today')
                ->where('expedition.open.orders', 0)
                ->where('yard.entries.entries', 0));
    }

    public function test_role_without_flow_permission_is_redirected_home(): void
    {
        $role = Role::factory()->withPermissions([
            Permission::Orders->value,
        ])->create();
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)
            ->get(route('flow'))
            ->assertRedirect(route('orders.index'));
    }

    public function test_expedition_diagram_shows_order_quantities_by_status(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
        ]);

        Order::factory()->count(2)->create([
            'product_id' => $product->id,
            'quantity_requested' => 10,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 30,
            'quantity_loaded' => 12,
            'status' => OrderStatus::Loading,
        ]);

        $this->actingAs($admin)
            ->get(route('flow'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('expedition.open.orders', 2)
                ->where('expedition.open.requested_ton', '20.000')
                ->where('expedition.open.remaining_ton', '20.000')
                ->where('expedition.loading.orders', 1)
                ->where('expedition.loading.loaded_ton', '12.000')
                ->where('expedition.loading.remaining_ton', '18.000'));
    }

    public function test_yard_diagram_shows_quantities_by_stage(): void
    {
        $admin = User::factory()->create();
        $circuit = CrushingCircuit::factory()->create();

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 50,
            'quantity_m3' => 34.483,
            'parent_id' => null,
            'crushing_circuit_id' => null,
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::QuarryToPrimary,
            'quantity_ton' => 12,
            'quantity_m3' => 8.276,
            'parent_id' => null,
            'crushing_circuit_id' => null,
        ]);

        $feed = ProductionEntry::factory()->create([
            'stage' => ProductionStage::QuarryToPrimary,
            'quantity_ton' => 30,
            'quantity_m3' => 20.690,
            'parent_id' => null,
            'crushing_circuit_id' => $circuit->id,
            'affects_stock' => false,
        ]);

        ProductionEntry::factory()->create([
            'parent_id' => $feed->id,
            'quantity_ton' => 18,
            'quantity_m3' => 12.414,
            'crushing_circuit_id' => $circuit->id,
        ]);

        $this->actingAs($admin)
            ->get(route('flow'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('yard.plant.entries', 1)
                ->where('yard.plant.ton', '50.000')
                ->where('yard.primary_plain.ton', '12.000')
                ->where('yard.primary_circuit.ton', '30.000')
                ->where('yard.circuit_products.ton', '18.000')
                ->where('yard.quarry.entries', 2)
                ->where('yard.entries.entries', 3));
    }

    public function test_today_filter_excludes_older_orders_and_production(): void
    {
        $this->freezeTime();

        $admin = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 10,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 8,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 50,
            'quantity_m3' => 34.483,
            'produced_on' => now()->subDay()->toDateString(),
            'parent_id' => null,
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 20,
            'quantity_m3' => 13.793,
            'produced_on' => now()->toDateString(),
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('flow'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.period', 'today')
                ->where('expedition.open.orders', 1)
                ->where('expedition.open.requested_ton', '8.000')
                ->where('yard.plant.ton', '20.000'));
    }

    public function test_all_period_includes_older_orders_and_production(): void
    {
        $this->freezeTime();

        $admin = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 10,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 50,
            'quantity_m3' => 34.483,
            'produced_on' => now()->subDays(10)->toDateString(),
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('flow', ['period' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.period', 'all')
                ->where('filters.from', null)
                ->where('filters.to', null)
                ->where('expedition.open.orders', 1)
                ->where('expedition.open.requested_ton', '10.000')
                ->where('yard.plant.ton', '50.000'));
    }

    public function test_custom_period_filters_by_date_range(): void
    {
        $this->freezeTime();

        $admin = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 7,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 9,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 11,
            'quantity_m3' => 7.586,
            'produced_on' => now()->subDays(5)->toDateString(),
            'parent_id' => null,
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 14,
            'quantity_m3' => 9.655,
            'produced_on' => now()->subDays(2)->toDateString(),
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('flow', [
                'period' => 'custom',
                'from' => now()->subDays(3)->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.period', 'custom')
                ->where('filters.from', now()->subDays(3)->toDateString())
                ->where('filters.to', now()->subDay()->toDateString())
                ->where('expedition.open.orders', 1)
                ->where('expedition.open.requested_ton', '9.000')
                ->where('yard.plant.ton', '14.000'));
    }

    public function test_week_filter_covers_monday_through_sunday(): void
    {
        $this->travelTo(now()->parse('2026-08-12 15:00:00'));

        $admin = User::factory()->create();

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 21,
            'quantity_m3' => 14.483,
            'produced_on' => '2026-08-09',
            'parent_id' => null,
        ]);

        ProductionEntry::factory()->create([
            'stage' => ProductionStage::Plant,
            'quantity_ton' => 17,
            'quantity_m3' => 11.724,
            'produced_on' => '2026-08-10',
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('flow', ['period' => 'week']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.period', 'week')
                ->where('filters.from', '2026-08-10')
                ->where('filters.to', '2026-08-16')
                ->where('yard.plant.ton', '17.000'));
    }
}
