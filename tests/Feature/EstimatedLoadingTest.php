<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatedLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimated_loading_by_buckets_updates_stock_and_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'stock_quantity' => 100,
        ]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 20,
            'quantity_loaded' => 0,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'order_id' => $order->id,
                'vehicle_plate' => 'ABC1D23',
                'mode' => 'buckets',
                'buckets_count' => 8,
                'bucket_capacity_m3' => 1.5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Carregamento registrado.');

        $loading = EstimatedLoading::query()->first();

        $this->assertNotNull($loading);
        $this->assertSame('12.000', $loading->quantity_m3);
        $this->assertSame('17.400', $loading->quantity_ton);
        $this->assertSame('17.400', $loading->quantity);
        $this->assertSame('82.600', $product->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::Loading, $order->fresh()->status);
        $this->assertSame('17.400', $order->fresh()->quantity_loaded);
    }

    public function test_estimated_loading_converts_to_product_unit_in_cubic_meters(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::CubicMeter,
            'stock_quantity' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'XYZ9A88',
                'mode' => 'quantity',
                'input_unit' => ProductUnit::Ton->value,
                'quantity_input' => 14.5,
            ])
            ->assertRedirect();

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->assertSame('10.000', $loading->quantity_m3);
        $this->assertSame('14.500', $loading->quantity_ton);
        $this->assertSame('10.000', $loading->quantity);
        $this->assertSame('40.000', $product->fresh()->stock_quantity);
    }

    public function test_destroying_estimated_loading_restores_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'stock_quantity' => 100,
        ]);
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'ABC1D23',
                'mode' => 'quantity',
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => 10,
            ]);

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('estimated-loadings.destroy', $loading))
            ->assertRedirect(route('estimated-loadings.index'))
            ->assertSessionHas('success', 'Carregamento removido e estoque estornado.');

        $this->assertSame('100.000', $product->fresh()->stock_quantity);
        $this->assertSame(0, EstimatedLoading::query()->count());
    }

    public function test_create_page_includes_open_order_remaining_data(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'bucket_capacity_m3' => 1.5,
            'stock_quantity' => 80,
        ]);
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity_requested' => 30,
            'quantity_loaded' => 8.7,
            'status' => OrderStatus::Open,
            'vehicle_plate' => 'ABC1D23',
            'destination' => 'Obra Centro',
        ]);

        $this->actingAs($user)
            ->get(route('estimated-loadings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/create')
                ->has('orders', 1)
                ->where('orders.0.id', $order->id)
                ->where('orders.0.quantity_requested', '30.000')
                ->where('orders.0.quantity_loaded', '8.700')
                ->where('orders.0.destination', 'Obra Centro')
                ->where('orders.0.product.bucket_capacity_m3', '1.500')
                ->where('orders.0.product.stock_quantity', '80.000'));
    }

    public function test_index_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('estimated-loadings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('estimated-loadings/index'));
    }
}
