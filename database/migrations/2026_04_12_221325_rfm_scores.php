<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfm_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            // --- Raw RFM values ---
            $table->integer('recency_days');           // Hari sejak transaksi terakhir
            $table->integer('frequency');              // Jumlah transaksi completed
            $table->decimal('monetary', 15, 2);        // Total belanja

            // --- Normalized values (0–1 min-max normalization) ---
            $table->decimal('recency_norm', 8, 6);
            $table->decimal('frequency_norm', 8, 6);
            $table->decimal('monetary_norm', 8, 6);

            // --- RFM scores (1–5 quintile) ---
            $table->tinyInteger('r_score');            // 5 = paling baru
            $table->tinyInteger('f_score');            // 5 = paling sering
            $table->tinyInteger('m_score');            // 5 = paling besar belanja

            // --- Composite ---
            $table->decimal('rfm_score', 5, 2);        // Rata-rata R+F+M score
            $table->string('rfm_label');               // misal: "555", "123"

            // --- K-Means ---
            $table->tinyInteger('cluster_id')->nullable();     // Nomor cluster (0-based)
            $table->string('segment_name')->nullable();        // Champions, Loyal, At Risk, dll
            $table->decimal('distance_to_centroid', 10, 6)->nullable(); // Jarak ke centroid cluster

            // --- Metadata kalkulasi ---
            $table->foreignId('calculation_batch_id')
                  ->constrained('rfm_calculation_batches')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['cluster_id']);
            $table->index(['calculation_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfm_scores');
    }
};