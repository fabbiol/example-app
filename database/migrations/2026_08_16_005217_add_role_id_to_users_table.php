<?php

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained();
        });

        $now = now();

        $adminId = DB::table('roles')->where('slug', UserRole::Admin->value)->value('id');

        if ($adminId === null) {
            $adminId = DB::table('roles')->insertGetId([
                'name' => UserRole::Admin->label(),
                'slug' => UserRole::Admin->value,
                'is_system' => true,
                'permissions' => json_encode(Permission::values()),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $operatorId = DB::table('roles')->where('slug', UserRole::Operator->value)->value('id');

        if ($operatorId === null) {
            $operatorId = DB::table('roles')->insertGetId([
                'name' => UserRole::Operator->label(),
                'slug' => UserRole::Operator->value,
                'is_system' => true,
                'permissions' => json_encode([Permission::Loader->value]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->where('role', UserRole::Operator->value)->update(['role_id' => $operatorId]);
            DB::table('users')->whereNull('role_id')->update(['role_id' => $adminId]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        } else {
            DB::table('users')->whereNull('role_id')->update(['role_id' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email');
        });

        $adminId = DB::table('roles')->where('slug', UserRole::Admin->value)->value('id');
        $operatorId = DB::table('roles')->where('slug', UserRole::Operator->value)->value('id');

        if ($operatorId !== null) {
            DB::table('users')->where('role_id', $operatorId)->update(['role' => UserRole::Operator->value]);
        }

        if ($adminId !== null) {
            DB::table('users')->where('role_id', $adminId)->update(['role' => UserRole::Admin->value]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
