<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoaderOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_is_redirected_to_loader_after_login(): void
    {
        $operator = User::factory()->operator()->create([
            'email' => 'operador@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $operator->email,
            'password' => 'password',
        ])->assertRedirect(route('loader.index'));
    }

    public function test_operator_cannot_access_admin_dashboard(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertRedirect(route('loader.index'));
    }

    public function test_operator_can_register_cubic_meters_from_loader(): void
    {
        $operator = User::factory()->operator()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 100,
        ]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 30,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'vehicle_plate' => 'ABC1D23',
        ]);

        $this->actingAs($operator)
            ->post(route('loader.store', $order), [
                'vehicle_plate' => 'ABC1D23',
                'quantity_m3' => 12,
            ])
            ->assertRedirect();

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->assertSame($operator->id, $loading->user_id);
        $this->assertNull($loading->buckets_count);
        $this->assertSame('12.000', $loading->quantity_m3);
        $this->assertSame('17.400', $loading->quantity_ton);
        $this->assertSame('17.400', $order->fresh()->quantity_loaded);
        $this->assertSame(OrderStatus::Loading, $order->fresh()->status);
        $this->assertTrue($operator->fresh()->isOperator());
    }

    public function test_loader_completes_order_when_remaining_m3_is_confirmed(): void
    {
        $operator = User::factory()->operator()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 100,
        ]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 20,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
            'vehicle_plate' => 'ABC1D23',
        ]);

        // 20 t / 1.45 = 13.793 m³ (arredondado) — antes deixava o pedido em "carregando"
        $this->actingAs($operator)
            ->post(route('loader.store', $order), [
                'vehicle_plate' => 'ABC1D23',
                'quantity_m3' => 13.793,
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame('20.000', $order->quantity_loaded);
    }

    public function test_loader_shows_insufficient_stock_error(): void
    {
        $operator = User::factory()->operator()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 0,
        ]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 10,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAs($operator)
            ->from(route('loader.show', $order))
            ->post(route('loader.store', $order), [
                'vehicle_plate' => 'ABC1D23',
                'quantity_m3' => 5,
            ])
            ->assertRedirect(route('loader.show', $order))
            ->assertSessionHasErrors('product_id');
    }

    public function test_loader_queue_lists_open_orders_for_operator(): void
    {
        $operator = User::factory()->operator()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Open,
            'quantity_requested' => 20,
            'quantity_loaded' => 5,
        ]);

        $this->actingAs($operator)
            ->get(route('loader.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('loader/index')
                ->has('orders', 1)
                ->where('orders.0.id', $order->id)
                ->where('orders.0.remaining', '15.000'));
    }

    public function test_loader_queue_empty_state_renders(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('loader.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('loader/index')
                ->has('orders', 0)
                ->has('recent', 0));
    }
}
