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
        Schema::table('production_entries', function (Blueprint $table) {
            $table->string('method')->default('quantity')->after('user_id')->index();
            $table->foreignId('truck_id')->nullable()->after('method')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('trips_count')->nullable()->after('truck_id');
            $table->decimal('truck_capacity_m3', 8, 3)->nullable()->after('trips_count');
            $table->string('input_unit')->nullable()->after('truck_capacity_m3');
            $table->decimal('quantity_m3', 12, 3)->nullable()->after('quantity');
            $table->decimal('quantity_ton', 12, 3)->nullable()->after('quantity_m3');
            $table->decimal('density', 8, 3)->nullable()->after('quantity_ton');
            $table->string('stage')->default('plant')->after('density')->index();
        });

        $density = 1.45;

        DB::table('production_entries')->orderBy('id')->each(function (object $entry) use ($density): void {
            $quantity = (float) $entry->quantity;

            DB::table('production_entries')->where('id', $entry->id)->update([
                'method' => 'quantity',
                'input_unit' => 'ton',
                'quantity_ton' => number_format($quantity, 3, '.', ''),
                'quantity_m3' => number_format($quantity / $density, 3, '.', ''),
                'density' => number_format($density, 3, '.', ''),
                'stage' => 'plant',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_id');
            $table->dropColumn([
                'method',
                'trips_count',
                'truck_capacity_m3',
                'input_unit',
                'quantity_m3',
                'quantity_ton',
                'density',
                'stage',
            ]);
        });
    }
};
