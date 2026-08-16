<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_people(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_manage_people(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('users.index'))
            ->assertRedirect(route('loader.index'));
    }

    public function test_admin_can_create_a_person_with_operator_role(): void
    {
        $admin = User::factory()->create(['name' => 'Ana Costa']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Carlos Mendes',
                'email' => 'carlos@pedreira.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role_id' => Role::operator()->id,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Pessoa cadastrada.');

        $person = User::query()->where('email', 'carlos@pedreira.test')->first();

        $this->assertNotNull($person);
        $this->assertSame('Carlos Mendes', $person->name);
        $this->assertTrue($person->isOperator());
    }

    public function test_admin_can_rename_a_person_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $person = User::factory()->operator()->create([
            'name' => 'Operador Pá',
            'email' => 'operador@example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $person), [
                'name' => 'João Silva',
                'email' => 'operador@example.com',
                'role_id' => Role::operator()->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame('João Silva', $person->fresh()->name);
        $this->assertTrue($person->fresh()->isOperator());
    }

    public function test_cannot_remove_yourself(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertModelExists($admin);
    }

    public function test_cannot_demote_the_last_person_who_can_manage_roles(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => Role::operator()->id,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_remove_an_operator(): void
    {
        $admin = User::factory()->create();
        $operator = User::factory()->operator()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $operator))
            ->assertRedirect(route('users.index'));

        $this->assertModelMissing($operator);
    }

    public function test_admin_can_visit_people_index(): void
    {
        $admin = User::factory()->create(['name' => 'Ana Costa']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Ana Costa')
                ->where('users.data.0.role.slug', UserRole::Admin->value));
    }

    public function test_person_with_custom_role_can_manage_people(): void
    {
        $role = Role::factory()->withPermissions([
            Permission::Users->value,
            Permission::Roles->value,
        ])->create(['name' => 'Gestão de acesso']);

        $manager = User::factory()->withRole($role)->create();

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertOk();
    }
}
