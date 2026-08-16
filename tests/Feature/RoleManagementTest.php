<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_roles(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_manage_roles(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('roles.index'))
            ->assertRedirect(route('loader.index'));
    }

    public function test_admin_can_visit_roles_index(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('roles/index')
                ->has('roles.data'));
    }

    public function test_role_form_uses_intuitive_menu_labels(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('roles/create')
                ->where(
                    'permissionGroups.Expedição',
                    fn ($items): bool => collect($items)->pluck('label')->all() === [
                        'Pedidos',
                        'Pá',
                        'Carregamentos',
                        'Balança',
                    ],
                )
                ->where(
                    'permissionGroups.Pátio',
                    fn ($items): bool => collect($items)->pluck('label')->all() === [
                        'Produção',
                        'Motorista',
                        'Circuito',
                    ],
                ));
    }

    public function test_admin_can_create_a_role_from_menu_permissions(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('roles.store'), [
                'name' => 'Expedição',
                'permissions' => [
                    Permission::Orders->value,
                    Permission::WeighTickets->value,
                ],
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Papel cadastrado.');

        $role = Role::query()->where('name', 'Expedição')->first();

        $this->assertNotNull($role);
        $this->assertFalse($role->is_system);
        $this->assertEqualsCanonicalizing(
            [Permission::Orders->value, Permission::WeighTickets->value],
            $role->permissions,
        );
    }

    public function test_custom_role_only_accesses_selected_menus(): void
    {
        $role = Role::factory()->withPermissions([
            Permission::Orders->value,
        ])->create(['name' => 'Pedidos']);
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('orders.index'));

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertRedirect(route('orders.index'));
    }

    public function test_shared_permissions_match_the_user_role(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.permissions', Permission::values()));
    }

    public function test_cannot_delete_a_system_role(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('roles.destroy', Role::administrative()))
            ->assertSessionHasErrors('role');

        $this->assertModelExists(Role::administrative());
    }

    public function test_cannot_remove_roles_permission_from_the_last_managing_role(): void
    {
        $admin = User::factory()->create();
        $role = Role::administrative();

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'name' => $role->name,
                'permissions' => [Permission::Dashboard->value],
            ])
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($role->fresh()->allows(Permission::Roles));
    }

    public function test_cannot_delete_a_role_that_has_people(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->withPermissions([Permission::Orders->value])->create();
        User::factory()->withRole($role)->create();

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertModelExists($role);
    }

    public function test_admin_can_update_and_delete_a_custom_role(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->withPermissions([Permission::Orders->value])->create([
            'name' => 'Somente pedidos',
        ]);

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'name' => 'Pátio',
                'permissions' => [
                    Permission::Production->value,
                    Permission::CrushingCircuits->value,
                ],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertSame('Pátio', $role->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertModelMissing($role);
    }
}
