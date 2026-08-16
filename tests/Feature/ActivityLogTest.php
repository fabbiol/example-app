<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityDomain;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\ProductUnit;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_activities(): void
    {
        $this->get(route('activities.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_view_activities(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('activities.index'))
            ->assertRedirect(route('loader.index'));
    }

    public function test_role_without_activities_permission_is_redirected_home(): void
    {
        $role = Role::factory()->withPermissions([
            Permission::Orders->value,
        ])->create();
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)
            ->get(route('activities.index'))
            ->assertRedirect(route('orders.index'));
    }

    public function test_admin_can_view_the_activities_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('activities/index')
                ->where('filters.domain', null)
                ->has('activities.data')
                ->has('domains'));
    }

    public function test_creating_an_order_logs_an_operational_activity(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity_input' => 10,
                'input_unit' => ProductUnit::CubicMeter->value,
                'status' => OrderStatus::Open->value,
            ])
            ->assertRedirect(route('orders.index'));

        $order = Order::query()->firstOrFail();
        $log = ActivityLog::query()
            ->where('subject_type', $order->getMorphClass())
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(ActivityDomain::Operational, $log->domain);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('pedido #'.$order->id, $log->description);
    }

    public function test_creating_a_product_logs_an_administrative_activity(): void
    {
        $product = Product::factory()->create(['name' => 'Brita 1']);

        $log = ActivityLog::query()
            ->where('subject_type', $product->getMorphClass())
            ->where('subject_id', $product->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(ActivityDomain::Administrative, $log->domain);
        $this->assertSame(ActivityAction::Created, $log->action);
        $this->assertStringContainsString('produto Brita 1', $log->description);
        $this->assertArrayNotHasKey('password', $log->properties ?? []);
    }

    public function test_stock_only_product_updates_are_not_logged(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $count = ActivityLog::query()->count();

        $product->update(['stock_quantity' => 40]);

        $this->assertSame($count, ActivityLog::query()->count());
    }

    public function test_user_password_is_not_stored_in_activity_properties(): void
    {
        $user = User::factory()->create();

        $log = ActivityLog::query()
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->id)
            ->where('action', ActivityAction::Created)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(ActivityDomain::Administrative, $log->domain);
        $this->assertArrayNotHasKey('password', $log->properties ?? []);
        $this->assertArrayNotHasKey('remember_token', $log->properties ?? []);
    }

    public function test_activities_can_be_filtered_by_domain(): void
    {
        $admin = User::factory()->create();

        ActivityLog::factory()->count(2)->operational()->create();
        ActivityLog::factory()->administrative()->create();

        $this->actingAs($admin)
            ->get(route('activities.index', ['domain' => ActivityDomain::Operational->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.domain', ActivityDomain::Operational->value)
                ->has('activities.data', 2)
                ->where('activities.data.0.domain', ActivityDomain::Operational->value)
                ->where('activities.data.1.domain', ActivityDomain::Operational->value));
    }

    public function test_login_and_logout_are_logged_as_administrative_activities(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $login = ActivityLog::query()
            ->where('action', ActivityAction::LoggedIn)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($login);
        $this->assertSame(ActivityDomain::Administrative, $login->domain);
        $this->assertSame('Entrou no sistema', $login->description);

        $this->actingAs($user)->post(route('logout'))->assertRedirect();

        $logout = ActivityLog::query()
            ->where('action', ActivityAction::LoggedOut)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($logout);
        $this->assertSame(ActivityDomain::Administrative, $logout->domain);
        $this->assertSame('Saiu do sistema', $logout->description);
    }
}
