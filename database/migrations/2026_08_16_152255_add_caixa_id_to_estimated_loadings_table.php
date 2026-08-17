<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estimated_loadings', function (Blueprint $table) {
            $table->unsignedInteger('caixa_id')->nullable()->unique()->after('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimated_loadings', function (Blueprint $table) {
            $table->dropUnique(['caixa_id']);
            $table->dropColumn('caixa_id');
        });
    }
};
