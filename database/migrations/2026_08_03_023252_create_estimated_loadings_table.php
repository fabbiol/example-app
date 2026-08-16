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
        Schema::create('estimated_loadings', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_plate');
            $table->unsignedSmallInteger('buckets_count')->nullable();
            $table->decimal('bucket_capacity_m3', 8, 3)->nullable();
            $table->string('input_unit');
            $table->decimal('quantity_m3', 12, 3);
            $table->decimal('quantity_ton', 12, 3);
            $table->decimal('quantity', 12, 3);
            $table->decimal('density', 8, 3);
            $table->timestamp('loaded_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimated_loadings');
    }
};
