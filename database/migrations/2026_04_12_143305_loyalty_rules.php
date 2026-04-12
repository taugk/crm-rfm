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
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name'); // Misal: "Poin Reguler"
            $table->decimal('min_purchase', 15, 2); // Nominal belanja minimal
            $table->integer('points_earned'); // Jumlah poin yang didapat
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
    }
};
