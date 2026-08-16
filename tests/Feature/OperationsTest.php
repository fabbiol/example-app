<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductionShift;
use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\User;
use App\Models\WeighTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_operations(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('weigh-tickets.index'))->assertRedirect(route('login'));
        $this->get(route('production.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_manage_products(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Brita 1',
                'code' => 'BRITA-1',
                'unit' => ProductUnit::Ton->value,
                'density' => 1.45,
                'bucket_capacity_m3' => 1.5,
                'stock_quantity' => 100,
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('code', 'BRITA-1')->first();

        $this->assertNotNull($product);
        $this->assertSame('100.000', $product->stock_quantity);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('products/index'));
    }

    public function test_authenticated_user_can_manage_customers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'name' => 'Concreteira Sul',
                'document' => '12.345.678/0001-90',
                'marketup_code' => 'MU-100',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertModelExists(Customer::query()->where('marketup_code', 'MU-100')->first());
    }

    public function test_authenticated_user_can_create_orders(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
        ]);

        $this->actingAs($user)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity_input' => 10,
                'input_unit' => ProductUnit::CubicMeter->value,
                'status' => OrderStatus::Open->value,
            ])
            ->assertRedirect(route('orders.index'));

        $order = Order::query()->firstOrFail();

        // 10 m³ × 1.45 = 14.500 t
        $this->assertSame('14.500', $order->quantity_requested);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(OrderStatus::Open, $order->status);
    }

    public function test_weigh_ticket_decreases_stock_and_updates_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity_requested' => 20,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('weigh-tickets.store'), [
                'order_id' => $order->id,
                'vehicle_plate' => 'ABC1D23',
                'tare_weight' => 10,
                'gross_weight' => 28,
            ])
            ->assertRedirect();

        $ticket = WeighTicket::query()->first();

        $this->assertNotNull($ticket);
        $this->assertSame('18.000', $ticket->net_weight);
        $this->assertSame('82.000', $product->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::Loading, $order->fresh()->status);
        $this->assertSame('18.000', $order->fresh()->quantity_loaded);
    }

    public function test_weigh_ticket_completes_order_when_quantity_is_fulfilled(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 15,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('weigh-tickets.store'), [
                'order_id' => $order->id,
                'vehicle_plate' => 'XYZ9A88',
                'tare_weight' => 12,
                'gross_weight' => 27,
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame('15.000', $order->fresh()->quantity_loaded);
    }

    public function test_weigh_ticket_rejects_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('weigh-tickets.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'ABC1D23',
                'tare_weight' => 10,
                'gross_weight' => 30,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame(0, WeighTicket::query()->count());
        $this->assertSame('5.000', $product->fresh()->stock_quantity);
    }

    public function test_production_entry_increases_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($user)
            ->post(route('production.store'), [
                'product_id' => $product->id,
                'method' => 'quantity',
                'stage' => 'plant',
                'input_unit' => ProductUnit::Ton->value,
                'quantity_input' => 50,
                'shift' => ProductionShift::Morning->value,
                'produced_on' => now()->toDateString(),
            ])
            ->assertRedirect(route('production.index'));

        $this->assertSame(1, ProductionEntry::query()->count());
        $this->assertSame('60.000', $product->fresh()->stock_quantity);
    }

    public function test_destroying_weigh_ticket_restores_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 20,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('weigh-tickets.store'), [
                'order_id' => $order->id,
                'vehicle_plate' => 'ABC1D23',
                'tare_weight' => 10,
                'gross_weight' => 25,
            ]);

        $ticket = WeighTicket::query()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('weigh-tickets.destroy', $ticket))
            ->assertRedirect(route('weigh-tickets.index'));

        $this->assertSame('100.000', $product->fresh()->stock_quantity);
        $this->assertSame('0.000', $order->fresh()->quantity_loaded);
        $this->assertSame(OrderStatus::Open, $order->fresh()->status);
    }

    public function test_weigh_ticket_converts_net_tons_to_cubic_meters_for_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::CubicMeter,
            'stock_quantity' => 50,
        ]);
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('weigh-tickets.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'ABC1D23',
                'tare_weight' => 10,
                'gross_weight' => 24.5,
            ])
            ->assertRedirect();

        $ticket = WeighTicket::query()->firstOrFail();

        $this->assertSame('14.500', $ticket->net_weight);
        $this->assertSame('10.000', $ticket->quantity);
        $this->assertSame('10.000', $ticket->quantity_m3);
        $this->assertSame('40.000', $product->fresh()->stock_quantity);
    }

    public function test_admin_can_visit_sidebar_destinations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        foreach ([
            'dashboard',
            'orders.index',
            'loader.index',
            'driver.index',
            'estimated-loadings.index',
            'weigh-tickets.index',
            'production.index',
            'crushing-circuits.edit',
            'products.index',
            'customers.index',
            'users.index',
            'trucks.index',
        ] as $name) {
            $this->get(route($name))->assertOk();
        }
    }
}
