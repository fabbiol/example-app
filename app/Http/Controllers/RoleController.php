<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('roles/index', [
            'roles' => Role::query()
                ->withCount('users')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'permissionGroups' => Permission::grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Role::query()->create([
            'name' => $data['name'],
            'slug' => Role::uniqueSlug($data['name']),
            'is_system' => false,
            'permissions' => array_values($data['permissions']),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Papel cadastrado.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('roles/edit', [
            'role' => $role,
            'permissionGroups' => Permission::grouped(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $permissions = array_values($data['permissions']);

        $this->guardLastRolesPermission($role, $permissions);

        $payload = [
            'name' => $data['name'],
            'permissions' => $permissions,
        ];

        if (! $role->is_system) {
            $payload['slug'] = Role::uniqueSlug($data['name'], $role->id);
        }

        $role->update($payload);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Papel atualizado.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'Este papel faz parte do sistema e não pode ser excluído.',
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Há pessoas usando este papel. Mova-as antes de excluir.',
            ]);
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Papel removido.');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function guardLastRolesPermission(Role $role, array $permissions): void
    {
        if (in_array(Permission::Roles->value, $permissions, true)) {
            return;
        }

        if (! $role->allows(Permission::Roles)) {
            return;
        }

        if (! User::anotherCanManageRoles(exceptRoleId: $role->id)) {
            throw ValidationException::withMessages([
                'permissions' => 'É preciso manter pelo menos um papel com acesso a Papéis.',
            ]);
        }
    }
}
