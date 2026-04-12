<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointReward extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     */
    protected $table = 'point_rewards';

    /**
     * Atribut yang dapat diisi (Mass Assignment).
     */
    protected $fillable = [
        'name',
        'description',
        'reward_type',
        'points_required',
        'stock',
        'value_amount',
        'image',
        'is_active',
    ];

    /**
     * Casting tipe data atribut.
     * Ini memastikan data yang keluar dari database memiliki tipe yang benar di Laravel.
     */
    protected $casts = [
        'points_required' => 'integer',
        'stock'           => 'integer',
        'value_amount'    => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    /**
     * Relasi ke PointRedemption (Histori Penukaran)
     * Satu hadiah bisa ditukarkan berkali-kali oleh banyak pelanggan.
     */
    public function redemptions()
    {
        return $this->hasMany(PointRedemption::class, 'point_reward_id');
    }

    /**
     * Scope untuk mempermudah pengambilan hadiah yang hanya berstatus aktif.
     * Penggunaan: PointReward::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}