<?php

namespace Tests\Feature;

use App\Enums\ProductUnit;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDensityTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_store_custom_density_and_bucket_capacity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Pó de pedra',
                'code' => 'PO-X',
                'unit' => ProductUnit::Ton->value,
                'density' => 1.40,
                'bucket_capacity_m3' => 1.40,
                'stock_quantity' => 100,
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('code', 'PO-X')->first();

        $this->assertNotNull($product);
        $this->assertSame('1.400', $product->density);
        $this->assertSame('1.400', $product->bucket_capacity_m3);
    }

    public function test_estimated_loading_uses_product_density(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'unit' => ProductUnit::Ton,
            'density' => 1.40,
            'bucket_capacity_m3' => 1.40,
            'stock_quantity' => 100,
        ]);

        $this->actingAs($user)
            ->post(route('estimated-loadings.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'vehicle_plate' => 'ABC1D23',
                'input_unit' => ProductUnit::CubicMeter->value,
                'quantity_input' => 14,
            ])
            ->assertRedirect();

        $loading = EstimatedLoading::query()->firstOrFail();

        $this->assertSame('14.000', $loading->quantity_m3);
        $this->assertSame('19.600', $loading->quantity_ton);
        $this->assertSame('1.400', $loading->density);
        $this->assertNull($loading->buckets_count);
        $this->assertNull($loading->bucket_capacity_m3);
        $this->assertSame('80.400', $product->fresh()->stock_quantity);
    }
}
