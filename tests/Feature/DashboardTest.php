<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductionShift;
use App\Models\Customer;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\User;
use App\Models\WeighTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('totals')
                ->has('stocks')
                ->has('queue')
                ->has('recent_tickets')
                ->has('recent_estimates')
                ->has('date'));
    }

    public function test_dashboard_shows_operational_totals_stocks_and_queue(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $brita1 = Product::factory()->create([
            'name' => 'Brita 1',
            'code' => 'BRITA-1',
            'stock_quantity' => 120,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Brita 2',
            'code' => 'BRITA-2',
            'stock_quantity' => 80,
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create([
            'stock_quantity' => 999,
        ]);

        $queueOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $brita1->id,
            'quantity_requested' => 30,
            'quantity_loaded' => 10,
            'status' => OrderStatus::Loading,
            'scheduled_at' => now(),
        ]);

        Order::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $brita1->id,
            'status' => OrderStatus::Scheduled,
            'scheduled_at' => now()->addDays(3),
        ]);

        WeighTicket::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $brita1->id,
            'user_id' => $user->id,
            'tare_weight' => 10,
            'gross_weight' => 28,
            'net_weight' => 18,
            'quantity' => 18,
            'quantity_m3' => 12.414,
            'density' => 1.45,
            'weighed_at' => now(),
        ]);

        EstimatedLoading::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $brita1->id,
            'user_id' => $user->id,
            'quantity_m3' => 12,
            'quantity_ton' => 17.4,
            'quantity' => 17.4,
            'loaded_at' => now(),
        ]);

        ProductionEntry::factory()->create([
            'product_id' => $brita1->id,
            'user_id' => $user->id,
            'quantity' => 50,
            'shift' => ProductionShift::Morning,
            'produced_on' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('totals.active_products', 2)
                ->where('totals.total_stock_ton', '200.000')
                ->where('totals.open_orders', 2)
                ->where('totals.queue_count', 1)
                ->where('totals.weighed_today_ton', '18.000')
                ->where('totals.weighed_today_m3', '12.414')
                ->where('totals.tickets_today', 1)
                ->where('totals.estimated_today_ton', '17.400')
                ->where('totals.estimated_today_m3', '12.000')
                ->where('totals.estimates_today', 1)
                ->where('totals.produced_today_ton', '50.000')
                ->has('stocks', 2)
                ->has('queue', 1)
                ->where('queue.0.id', $queueOrder->id)
                ->has('recent_tickets', 1)
                ->has('recent_estimates', 1));
    }
}
