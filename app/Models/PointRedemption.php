<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRedemption extends Model
{
    use HasFactory;

    protected $table = 'point_redemptions';

    protected $fillable = [
        'redemption_code',
        'customer_id',
        'point_reward_id',
        'points_used',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'points_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    /**
     * Relasi ke PointReward
     */
    public function reward()
    {
        return $this->belongsTo(PointReward::class, 'point_reward_id');
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcess($query)
    {
        return $query->where('status', 'process');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Generate redemption code unik
     */
    public static function generateRedemptionCode()
    {
        do {
            $code = 'RDM-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
        } while (self::where('redemption_code', $code)->exists());
        
        return $code;
    }

    /**
     * Update status redemption
     */
    public function updateStatus($status, $notes = null)
    {
        $this->status = $status;
        if ($notes) {
            $this->admin_notes = $notes;
        }
        $this->save();
        
        return $this;
    }
}