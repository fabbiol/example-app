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
        Schema::table('weigh_tickets', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(0)->after('net_weight');
            $table->decimal('quantity_m3', 12, 3)->nullable()->after('quantity');
            $table->decimal('density', 8, 3)->nullable()->after('quantity_m3');
        });

        $density = 1.45;

        DB::table('weigh_tickets')->orderBy('id')->each(function (object $ticket) use ($density): void {
            $net = (float) $ticket->net_weight;

            DB::table('weigh_tickets')->where('id', $ticket->id)->update([
                'quantity' => number_format($net, 3, '.', ''),
                'quantity_m3' => number_format($net / $density, 3, '.', ''),
                'density' => number_format($density, 3, '.', ''),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weigh_tickets', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'quantity_m3', 'density']);
        });
    }
};
