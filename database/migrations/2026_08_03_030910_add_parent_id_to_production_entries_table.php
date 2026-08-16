<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('production_entries')->nullOnDelete();
            $table->foreignId('crushing_circuit_id')->nullable()->after('parent_id')->constrained()->nullOnDelete();
            $table->boolean('affects_stock')->default(true)->after('crushing_circuit_id');
            $table->decimal('yield_percent', 6, 3)->nullable()->after('affects_stock');
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('crushing_circuit_id');
            $table->dropColumn(['affects_stock', 'yield_percent']);
        });
    }
};
