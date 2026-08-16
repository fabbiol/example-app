<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                continue;
            }

            if (! in_array('dashboard', $permissions, true)) {
                continue;
            }

            if (in_array('activities', $permissions, true)) {
                continue;
            }

            $permissions[] = 'activities';

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values($permissions)),
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
                fn (mixed $permission): bool => $permission !== 'activities',
            ));

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }
};
