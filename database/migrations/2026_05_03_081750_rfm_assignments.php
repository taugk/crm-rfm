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
        Schema::create('rfm_kmeans_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_batch_id')
                ->constrained('rfm_calculation_batches')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->integer('iteration_number');
            $table->integer('cluster_id'); // Klaster terdekat
            $table->json('distances_to_all_centroids'); // Simpan jarak ke C1, C2, dst dalam JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfm_kmeans_assignments');
    }
};