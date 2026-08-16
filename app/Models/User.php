<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $role_id
 * @property-read Role|null $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        $this->loadMissing('role');

        return $this->role?->allows($permission) ?? false;
    }

    public function homeRouteName(): string
    {
        foreach (Permission::cases() as $permission) {
            if ($this->hasPermission($permission)) {
                return $permission->routeName();
            }
        }

        return 'profile.edit';
    }

    public function isAdmin(): bool
    {
        $this->loadMissing('role');

        return $this->role?->slug === UserRole::Admin->value;
    }

    public function isOperator(): bool
    {
        $this->loadMissing('role');

        return $this->role?->slug === UserRole::Operator->value;
    }

    public function isDriver(): bool
    {
        $this->loadMissing('role');

        return $this->role?->slug === UserRole::Driver->value;
    }

    public static function anotherCanManageRoles(?int $exceptUserId = null, ?int $exceptRoleId = null): bool
    {
        return static::query()
            ->when($exceptUserId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->when($exceptRoleId, fn ($query, int $id) => $query->where('role_id', '!=', $id))
            ->whereHas('role', function ($query) {
                $query->whereJsonContains('permissions', Permission::Roles->value);
            })
            ->exists();
    }
}
