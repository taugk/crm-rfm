<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     * Secara default Laravel akan menganggap nama tabel adalah 'loyalty_rules'.
     */
    protected $table = 'loyalty_rules';

    /**
     * Kolom yang dapat diisi melalui mass assignment.
     * Digunakan saat memanggil LoyaltyRule::create([...]) atau $rule->update([...]).
     */
    protected $fillable = [
        'rule_name',      // Nama Aturan (misal: Poin Member Baru)
        'min_purchase',   // Minimal belanja (kelipatan)
        'points_earned',  // Jumlah poin yang didapat
        'is_active',      // Status aturan aktif/tidak
    ];

    /**
     * Casting atribut ke tipe data tertentu.
     * Ini memastikan is_active selalu dibaca sebagai true/false (boolean).
     */
    protected $casts = [
        'min_purchase' => 'decimal:2',
        'points_earned' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk mengambil hanya aturan yang sedang aktif.
     * Contoh penggunaan: LoyaltyRule::active()->first();
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}