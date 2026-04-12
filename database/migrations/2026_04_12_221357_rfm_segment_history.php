<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfm_segment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->foreignId('rfm_score_id')
                  ->constrained('rfm_scores')
                  ->cascadeOnDelete();
            $table->foreignId('calculation_batch_id')
                  ->constrained('rfm_calculation_batches')
                  ->cascadeOnDelete();

            $table->string('segment_from')->nullable();   // Segmen sebelumnya (null = pertama kali)
            $table->string('segment_to');                 // Segmen baru

            // Snapshot nilai RFM saat itu
            $table->integer('recency_days');
            $table->integer('frequency');
            $table->decimal('monetary', 15, 2);
            $table->decimal('rfm_score', 5, 2);

            $table->boolean('is_segment_changed');
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfm_segment_history');
    }
};