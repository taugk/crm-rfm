<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harus dibuat SEBELUM rfm_scores karena rfm_scores ber-FK ke sini
        Schema::create('rfm_calculation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triggered_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // --- Parameter K-Means yang digunakan ---
            $table->tinyInteger('k_clusters');                 // Jumlah cluster yg dipilih admin
            $table->integer('max_iterations')->default(100);
            $table->integer('actual_iterations');              // Berapa iterasi yg benar-benar terjadi
            $table->decimal('inertia', 15, 6)->nullable();     // Sum of squared distances (SSE)

            // --- Rentang data yang dihitung ---
            $table->date('data_from')->nullable();             // Tanggal transaksi awal
            $table->date('data_to')->nullable();               // Tanggal transaksi akhir
            $table->integer('total_customers');                // Jumlah customer yang dihitung

            // --- Status eksekusi ---
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();        // Lama proses dalam ms

           
            $table->json('final_centroids')->nullable();

            
            $table->json('cluster_labels')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfm_calculation_batches');
    }
};