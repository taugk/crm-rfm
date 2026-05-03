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
        // Statistik per iterasi
        Schema::create('rfm_kmeans_iterations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_batch_id')
                ->constrained('rfm_calculation_batches')
                ->cascadeOnDelete();
            $table->integer('iteration_number');
            $table->decimal('wcss', 20, 6);
            $table->integer('assignments_changed');
            $table->json('cluster_sizes')->nullable();
            $table->boolean('is_converged')->default(false);
            $table->timestamps();
        });

        // Posisi titik pusat per iterasi
        Schema::create('rfm_centroids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_batch_id')
                ->constrained('rfm_calculation_batches')
                ->cascadeOnDelete();
            $table->integer('iteration_number');
            $table->integer('cluster_id');
            $table->decimal('recency_pos', 10, 6);
            $table->decimal('frequency_pos', 10, 6);
            $table->decimal('monetary_pos', 10, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfm_centroids');
        Schema::dropIfExists('rfm_kmeans_iterations');
    }
};