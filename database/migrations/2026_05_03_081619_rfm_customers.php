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
        // Tabel data mentah
        Schema::create('rfm_customer_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_batch_id')
                ->constrained('rfm_calculation_batches')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->integer('recency_days');
            $table->integer('frequency');
            $table->decimal('monetary', 20, 2);
            $table->timestamps();
        });

        // Tabel data normalisasi (skala 0-1)
        Schema::create('rfm_customer_normalized', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_batch_id')
                ->constrained('rfm_calculation_batches')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('recency_norm', 10, 6);
            $table->decimal('frequency_norm', 10, 6);
            $table->decimal('monetary_norm', 10, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfm_customer_normalized');
        Schema::dropIfExists('rfm_customer_raw');
    }
};