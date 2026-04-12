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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
           
            $table->string('invoice_number')->unique(); 
            
           
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');

            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->onDelete('set null');

           
            $table->decimal('subtotal', 15, 2); 
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2);

            
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])
                  ->default('pending');
            $table->timestamp('transaction_date')->useCurrent();

            
            $table->string('payment_method')->nullable(); 
            
            
            $table->text('notes')->nullable();
            
            $table->timestamps();

           
            $table->index(['customer_id', 'status', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
