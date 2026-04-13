<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPoints extends Model
{
    use HasFactory;

    protected $table = 'loyalty_points';

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'amount',
        'description',
        'type',
    ];

    /**
     * Relasi ke Customer
     * Mengetahui siapa pemilik poin ini.
     */
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    /**
     * Relasi ke Transaction
     * Jika poin didapat atau digunakan melalui transaksi tertentu.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Scope untuk mempermudah filter tipe poin
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earn');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeem');
    }
}