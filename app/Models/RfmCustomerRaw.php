<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmCustomerRaw extends Model
{
    use HasFactory;

    protected $table = 'rfm_customer_raw';

    protected $fillable = [
        'calculation_batch_id',
        'customer_id',
        'recency_days',
        'frequency',
        'monetary',
    ];

    protected $casts = [
        'recency_days' => 'integer',
        'frequency'    => 'integer',
        'monetary'     => 'decimal:2',
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