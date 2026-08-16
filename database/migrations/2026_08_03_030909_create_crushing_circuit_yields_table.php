<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crushing_circuit_yields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crushing_circuit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('group_name')->nullable();
            $table->decimal('percent', 6, 3);
            $table->decimal('percent_min', 6, 3)->nullable();
            $table->decimal('percent_max', 6, 3)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['crushing_circuit_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crushing_circuit_yields');
    }
};
