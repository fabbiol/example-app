<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estimated_loading_items', function (Blueprint $table) {
            $table->timestamp('loader_loaded_at')->nullable()->index()->after('density');
        });

        DB::table('estimated_loadings')
            ->whereNotNull('order_id')
            ->orderBy('id')
            ->get(['id', 'loaded_at'])
            ->each(function (object $loading): void {
                DB::table('estimated_loading_items')
                    ->where('estimated_loading_id', $loading->id)
                    ->whereNull('loader_loaded_at')
                    ->update(['loader_loaded_at' => $loading->loaded_at]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimated_loading_items', function (Blueprint $table) {
            $table->dropColumn('loader_loaded_at');
        });
    }
};
