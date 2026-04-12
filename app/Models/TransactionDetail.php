<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $table = 'transactions_details';

    protected $fillable = [
        'transaction_id',
        'product_detail_id',
        'quantity',
        'price_at_purchase',
        'subtotal',
    ];

    /**
     * Relasi ke ProductDetail (Detail Barang)
     * Memungkinkan kita memanggil: $detail->product_detail->name
     */
    public function product_detail()
    {
        return $this->belongsTo(ProductDetail::class, 'product_detail_id');
    }

    /**
     * Relasi balik ke Header Transaksi
     * Memungkinkan kita memanggil: $detail->transaction->invoice_number
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}