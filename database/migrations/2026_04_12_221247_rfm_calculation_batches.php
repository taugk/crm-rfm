<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfm_calculation_batches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('triggered_by')->constrained('users')->restrictOnDelete();
    $table->tinyInteger('k_clusters'); // Jumlah klaster yang dipilih
    $table->integer('max_iterations')->default(8); // Maksimal 8 iterasi
    $table->integer('actual_iterations')->default(0);
    $table->decimal('inertia', 15, 6)->nullable(); // WCSS Final
    $table->decimal('dbi_score', 10, 6)->nullable(); // Skor DBI Final
    $table->date('data_from')->nullable();
    $table->date('data_to')->nullable();
    $table->integer('total_customers');
    $table->enum('status', ['running', 'completed', 'failed'])->default('running');
    $table->text('error_message')->nullable();
    $table->integer('duration_ms')->nullable();
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