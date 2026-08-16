<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Concerns\LogsActivity;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_system
 * @property list<string> $permissions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'is_system', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_system' => false,
        'permissions' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission->value, $this->permissions ?? [], true);
    }

    public static function administrative(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => UserRole::Admin->value],
            [
                'name' => UserRole::Admin->label(),
                'is_system' => true,
                'permissions' => Permission::values(),
            ],
        );
    }

    public static function operator(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => UserRole::Operator->value],
            [
                'name' => UserRole::Operator->label(),
                'is_system' => true,
                'permissions' => [Permission::Loader->value],
            ],
        );
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'papel';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
