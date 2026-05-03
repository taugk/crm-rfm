<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmCustomerNormalized extends Model
{
    use HasFactory;

    protected $table = 'rfm_customer_normalized';

    protected $fillable = [
        'calculation_batch_id',
        'customer_id',
        'recency_norm',
        'frequency_norm',
        'monetary_norm',
    ];

    protected $casts = [
        'recency_norm'   => 'decimal:6',
        'frequency_norm' => 'decimal:6',
        'monetary_norm'  => 'decimal:6',
    ];

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
}