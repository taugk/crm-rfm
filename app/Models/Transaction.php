<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'promotion_id',
        'subtotal',
        'discount_amount',
        'tax_total',
        'total_price',
        'status',
        'transaction_date',
        'payment_method',
    ];

    protected $casts = [
    'transaction_date' => 'datetime',
    ];

    /**
     * Relasi ke Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    /**
     * Relasi ke TransactionDetail
     */
    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotions::class, 'promotion_id');
    }

}