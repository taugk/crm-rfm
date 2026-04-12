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
        Schema::create('point_redemptions', function (Blueprint $table) {
            $table->id();
            $table->string('redemption_code')->unique(); // Kode unik penukaran
            
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->foreignId('point_reward_id')
                  ->constrained('point_rewards')
                  ->cascadeOnDelete();

            $table->integer('points_used'); // Poin yang didebet saat itu
            
            // Status proses penukaran
            $table->enum('status', ['pending', 'process', 'completed', 'cancelled'])
                  ->default('pending');

            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_redemptions');
    }
};
