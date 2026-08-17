<?php

namespace Tests\Feature;

use App\Enums\CaixaType;
use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\EstimatedLoadingItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatedLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimated_loading_by_quantity_updates_stock_and_order(): void
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
                'items' => [
                    [
                        'product_id' => $product->id,
                        'input_unit' => ProductUnit::CubicMeter->value,
                        'quantity_input' => 12,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Carregamento registrado.');

        $loading = EstimatedLoading::query()->first();

        $this->assertNotNull($loading);
        $this->assertSame('12.000', $loading->quantity_m3);
        $this->assertSame('17.400', $loading->quantity_ton);
        $this->assertSame('17.400', $loading->quantity);
        $this->assertNull($loading->buckets_count);
        $this->assertCount(1, $loading->items);
        $this->assertSame($product->id, $loading->items->first()?->product_id);
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

    public function test_estimated_loading_records_multiple_products_and_restores_each_stock(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $tonProduct = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.45,
            'stock_quantity' => 100,
        ]);
        $m3Product = Product::factory()->create([
            'unit' => ProductUnit::CubicMeter,
            'density' => 1.45,
            'stock_quantity' => 50,
        ]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'customer_id' => $customer->id,
                'vehicle_plate' => 'ABC1D23',
                'items' => [
                    [
                        'product_id' => $tonProduct->id,
                        'input_unit' => ProductUnit::CubicMeter->value,
                        'quantity_input' => 10,
                    ],
                    [
                        'product_id' => $m3Product->id,
                        'input_unit' => ProductUnit::CubicMeter->value,
                        'quantity_input' => 8,
                    ],
                ],
            ])
            ->assertRedirect();

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->assertSame('18.000', $loading->quantity_m3);
        $this->assertSame('26.100', $loading->quantity_ton);
        $this->assertCount(2, $loading->items);
        $this->assertSame('85.500', $tonProduct->fresh()->stock_quantity);
        $this->assertSame('42.000', $m3Product->fresh()->stock_quantity);

        $this->actingAs($user)
            ->get(route('estimated-loadings.show', $loading))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/show')
                ->has('loading.items', 2)
                ->where('loading.items.0.product_id', $tonProduct->id)
                ->where('loading.items.1.product_id', $m3Product->id));

        $this->actingAs($user)
            ->delete(route('estimated-loadings.destroy', $loading))
            ->assertRedirect(route('estimated-loadings.index'));

        $this->assertSame('100.000', $tonProduct->fresh()->stock_quantity);
        $this->assertSame('50.000', $m3Product->fresh()->stock_quantity);
        $this->assertSame(0, EstimatedLoading::query()->count());
    }

    public function test_estimated_loading_rejects_duplicate_products(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->from(route('estimated-loadings.create'))
            ->post(route('estimated-loadings.store'), [
                'customer_id' => $customer->id,
                'vehicle_plate' => 'ABC1D23',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'input_unit' => ProductUnit::CubicMeter->value,
                        'quantity_input' => 4,
                    ],
                    [
                        'product_id' => $product->id,
                        'input_unit' => ProductUnit::CubicMeter->value,
                        'quantity_input' => 6,
                    ],
                ],
            ])
            ->assertRedirect(route('estimated-loadings.create'))
            ->assertSessionHasErrors('items.1.product_id');

        $this->assertSame(0, EstimatedLoading::query()->count());
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
                ->where('orders.0.product.stock_quantity', '80.000')
                ->has('caixa_entries')
                ->where('caixa_error', null));
    }

    public function test_create_page_lists_unused_caixa_income_numbers(): void
    {
        $user = User::factory()->create();
        $newer = $this->createCaixaEntry([
            'id' => 1008,
            'tipo' => CaixaType::Deposito->value,
            'descricao' => '6920',
            'data' => now()->toDateString(),
        ]);
        $older = $this->createCaixaEntry([
            'id' => 1001,
            'tipo' => CaixaType::Pix->value,
            'descricao' => '6654',
            'data' => now()->subDay()->toDateString(),
        ]);
        $this->createCaixaEntry([
            'id' => 1002,
            'tipo' => CaixaType::Saida->value,
            'descricao' => '6640',
            'data' => now()->toDateString(),
        ]);
        $used = $this->createCaixaEntry([
            'id' => 1003,
            'tipo' => CaixaType::Cartao->value,
            'descricao' => '6888',
            'data' => now()->toDateString(),
        ]);

        EstimatedLoading::factory()->create([
            'caixa_id' => $used->id,
            'number' => $used->orderNumber(),
        ]);

        $this->actingAs($user)
            ->get(route('estimated-loadings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/create')
                ->has('caixa_entries', 2)
                ->where('caixa_entries.0.id', $newer->id)
                ->where('caixa_entries.0.descricao', '6920')
                ->where('caixa_entries.1.id', $older->id)
                ->where('caixa_entries.1.descricao', '6654')
                ->where('caixa_error', null));
    }

    public function test_loading_consumes_caixa_number_and_cannot_reuse_it(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::CubicMeter,
            'stock_quantity' => 50,
        ]);
        $caixa = $this->createCaixaEntry([
            'id' => 2,
            'tipo' => CaixaType::Boleto->value,
            'descricao' => '6654',
        ]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'caixa_id' => $caixa->id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'ABC1D23',
                'mode' => 'quantity',
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => 10,
            ])
            ->assertRedirect();

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->assertSame(2, $loading->caixa_id);
        $this->assertSame('6654', $loading->caixa_number);
        $this->assertStringStartsWith('EST-', $loading->number);

        $this->actingAs($user)
            ->get(route('estimated-loadings.show', $loading))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/show')
                ->where('loading.number', $loading->number)
                ->where('loading.caixa_number', '6654')
                ->where('loading.caixa_id', 2)
                ->where('loading.status', 'released')
                ->has('loading.items', 1));

        $this->actingAs($user)
            ->get(route('estimated-loadings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/index')
                ->where('loadings.data.0.number', $loading->number)
                ->where('loadings.data.0.caixa_number', '6654')
                ->where('loadings.data.0.status', 'released'));

        $this->actingAs($user)
            ->from(route('estimated-loadings.create'))
            ->post(route('estimated-loadings.store'), [
                'caixa_id' => $caixa->id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'XYZ9A88',
                'mode' => 'quantity',
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => 8,
            ])
            ->assertRedirect(route('estimated-loadings.create'))
            ->assertSessionHasErrors('caixa_id');

        $this->assertSame(1, EstimatedLoading::query()->count());
    }

    public function test_deleting_loading_makes_caixa_number_available_again(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'stock_quantity' => 100,
        ]);
        $caixa = $this->createCaixaEntry(['id' => 3001]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'caixa_id' => $caixa->id,
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
            ->assertRedirect(route('estimated-loadings.index'));

        $this->actingAs($user)
            ->get(route('estimated-loadings.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('caixa_entries', 1)
                ->where('caixa_entries.0.id', 3001));
    }

    public function test_index_classifies_status_from_loader_confirmation(): void
    {
        $user = User::factory()->create();
        $loaded = EstimatedLoading::factory()->create([
            'loaded_at' => now(),
        ]);
        $loaded->items()->update(['loader_loaded_at' => now()]);

        $partial = EstimatedLoading::factory()->create([
            'loaded_at' => now()->subMinute(),
        ]);
        $partial->items()->delete();
        EstimatedLoadingItem::factory()->create([
            'estimated_loading_id' => $partial->id,
            'sort_order' => 0,
            'loader_loaded_at' => null,
        ]);
        EstimatedLoadingItem::factory()->loaded()->create([
            'estimated_loading_id' => $partial->id,
            'sort_order' => 1,
        ]);

        EstimatedLoading::factory()->create([
            'loaded_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($user)
            ->get(route('estimated-loadings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/index')
                ->where('loadings.data.0.status', 'loaded')
                ->where('loadings.data.1.status', 'loading')
                ->where('loadings.data.2.status', 'released'));

        $this->actingAs($user)
            ->get(route('estimated-loadings.show', $partial))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('estimated-loadings/show')
                ->where('loading.status', 'loading')
                ->has('loading.items', 2)
                ->where('loading.items.0.loader_loaded_at', null)
                ->whereNotNull('loading.items.1.loader_loaded_at'));
    }

    public function test_index_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('estimated-loadings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('estimated-loadings/index'));
    }
}
