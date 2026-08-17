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
        Schema::create('estimated_loading_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimated_loading_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->index();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('input_unit');
            $table->decimal('quantity_m3', 12, 3);
            $table->decimal('quantity_ton', 12, 3);
            $table->decimal('quantity', 12, 3);
            $table->decimal('density', 8, 3);
            $table->timestamps();

            $table->unique(['estimated_loading_id', 'product_id']);
        });

        $now = now();

        DB::table('estimated_loadings')->orderBy('id')->get()->each(function (object $loading) use ($now): void {
            DB::table('estimated_loading_items')->insert([
                'estimated_loading_id' => $loading->id,
                'product_id' => $loading->product_id,
                'sort_order' => 0,
                'input_unit' => $loading->input_unit,
                'quantity_m3' => $loading->quantity_m3,
                'quantity_ton' => $loading->quantity_ton,
                'quantity' => $loading->quantity,
                'density' => $loading->density,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimated_loading_items');
    }
};
