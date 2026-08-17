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
            $table->string('caixa_number')->nullable()->after('caixa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimated_loadings', function (Blueprint $table) {
            $table->dropColumn('caixa_number');
        });
    }
};
