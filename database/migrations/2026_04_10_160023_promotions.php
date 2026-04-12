<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            
           
            $table->string('promo_name');
            $table->string('promo_code')->unique()->nullable();
            $table->text('description')->nullable();
            
           
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('discount_value', 12, 2);

            
            $table->string('target_segment')->nullable(); 
            $table->decimal('min_spend', 12, 2)->default(0); 
            
            
            $table->integer('usage_limit')->nullable()->comment('Batas maksimal pemakaian promo');
            $table->integer('used_count')->default(0)->comment('Jumlah promo yang sudah terpakai');
            
            
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};