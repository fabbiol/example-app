<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/index', [
            'users' => User::query()
                ->with('role')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => $this->roles(),
            'defaultRoleId' => (string) Role::operator()->id,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('password_confirmation');
        $data['email_verified_at'] = now();

        User::query()->create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pessoa cadastrada.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'user' => $user->loadMissing('role'),
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $this->guardLastRolesManager($user, (int) ($data['role_id'] ?? $user->role_id));

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pessoa atualizada.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(request()->user())) {
            throw ValidationException::withMessages([
                'user' => 'Você não pode remover a si mesmo.',
            ]);
        }

        $this->guardLastRolesManager($user, null);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Pessoa removida.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roles(): array
    {
        $roles = [];

        foreach (Role::query()->orderBy('name')->get() as $role) {
            $roles[] = [
                'value' => sprintf('%d', $role->id),
                'label' => $role->name,
            ];
        }

        return $roles;
    }

    private function guardLastRolesManager(User $user, ?int $newRoleId): void
    {
        if (! $user->hasPermission(Permission::Roles)) {
            return;
        }

        if ($newRoleId !== null) {
            $newRole = Role::query()->find($newRoleId);

            if ($newRole?->allows(Permission::Roles)) {
                return;
            }
        }

        if (! User::anotherCanManageRoles(exceptUserId: $user->id)) {
            throw ValidationException::withMessages([
                'role_id' => 'É preciso manter pelo menos uma pessoa com acesso a Papéis.',
            ]);
        }
    }
}
