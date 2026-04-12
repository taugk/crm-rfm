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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            $table->string('phone')->unique()->nullable(); 
            $table->string('email')->unique()->nullable();
            
            $table->string('profile_photo')->nullable();
            $table->string('password')->nullable();

            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();

            $table->enum('type', ['walk in', 'member'])->default('member')->nullable();
            

            $table->integer('total_points')->default(0); 
            $table->timestamp('last_purchase_at')->nullable();
            

            $table->enum('status', ['active', 'inactive', 'block'])->default('active');
            $table->string('role')->default('customer');
            $table->string('full_address')->nullable();

            $table->rememberToken();
            $table->timestamps(); 
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};