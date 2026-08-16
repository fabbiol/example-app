<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->timestamp('loaded_at')->nullable()->after('notes');
            $table->timestamp('unloaded_at')->nullable()->after('loaded_at');
            $table->index('unloaded_at');
            $table->index(['truck_id', 'unloaded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropIndex(['truck_id', 'unloaded_at']);
            $table->dropIndex(['unloaded_at']);
            $table->dropColumn(['loaded_at', 'unloaded_at']);
        });
    }
};
