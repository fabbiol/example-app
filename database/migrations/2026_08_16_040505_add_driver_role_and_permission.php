<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                continue;
            }

            if ($role->slug !== 'admin' && ! in_array('production', $permissions, true)) {
                continue;
            }

            if (in_array('driver', $permissions, true)) {
                continue;
            }

            $permissions[] = 'driver';

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values($permissions)),
            ]);
        }

        $driverExists = DB::table('roles')->where('slug', 'driver')->exists();

        if (! $driverExists) {
            DB::table('roles')->insert([
                'name' => 'Motorista',
                'slug' => 'driver',
                'is_system' => true,
                'permissions' => json_encode(['driver']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                continue;
            }

            $permissions = array_values(array_filter(
                $permissions,
                fn (mixed $permission): bool => $permission !== 'driver',
            ));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        }

        DB::table('roles')
            ->where('slug', 'driver')
            ->where('is_system', true)
            ->delete();
    }
};
