<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotions extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database (opsional jika nama file jamak 'promotions')
     */
    protected $table = 'promotions';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'promo_name', 
        'promo_code', 
        'description', 
        'discount_type', 
        'discount_value', 
        'target_segment', 
        'min_spend', 
        'usage_limit', 
        'used_count', 
        'start_date', 
        'end_date', 
        'is_active'
    ];

    /**
     * Casting tipe data kolom.
     * Menggunakan datetime agar Carbon otomatis memproses waktu Indonesia.
     */
    protected $casts = [
        'start_date'     => 'datetime',
        'end_date'       => 'datetime',
        'is_active'      => 'boolean',
        'discount_value' => 'decimal:2',
        'min_spend'      => 'decimal:2',
        'usage_limit'    => 'integer',
        'used_count'     => 'integer',
    ];

    /**
     * Accessor untuk Badge Status Otomatis (Format Indonesia 24 Jam)
     * Digunakan di view dengan: {!! $promo->status_label !!}
     */
    public function getStatusLabelAttribute()
    {
        // Mengambil waktu sekarang berdasarkan timezone Asia/Jakarta di config/app.php
        $now = now();

        // 1. Cek jika admin menonaktifkan secara manual
        if (!$this->is_active) {
            return '<span class="badge bg-light-danger text-danger px-3">Non-Aktif</span>';
        }

        // 2. Cek jika kuota penggunaan sudah habis
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return '<span class="badge bg-light-warning text-dark px-3">Limit Habis</span>';
        }

        // 3. Cek jika waktu mulai belum tiba (Mendatang)
        if ($now->lt($this->start_date)) {
            return '<span class="badge bg-light-info text-info px-3">Mendatang</span>';
        }

        // 4. Cek jika sudah melewati waktu berakhir (Selesai/Expired)
        if ($now->gt($this->end_date)) {
            return '<span class="badge bg-light-secondary text-secondary px-3">Selesai</span>';
        }

        // 5. Jika lolos semua pengecekan, maka status Aktif
        return '<span class="badge bg-light-success text-success px-3">Aktif</span>';
    }

    /**
     * Helper untuk format rupiah (Opsional)
     * Digunakan dengan: $promo->formatted_min_spend
     */
    public function getFormattedMinSpendAttribute()
    {
        return 'Rp ' . number_format($this->min_spend, 0, ',', '.');
    }

    /**
     * Helper untuk format diskon (Opsional)
     */
    public function getFormattedDiscountAttribute()
    {
        if ($this->discount_type === 'percentage') {
            return number_format($this->discount_value, 0) . '%';
        }
        return 'Rp ' . number_format($this->discount_value, 0, ',', '.');
    }
    public function transactions()
{
    return $this->hasMany(Transaction::class, 'promotion_id');
}
}