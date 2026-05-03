<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmCentroid extends Model
{
    use HasFactory;

    protected $table = 'rfm_centroids';

    protected $fillable = [
        'calculation_batch_id',
        'iteration_number',
        'cluster_id',
        'recency_pos',
        'frequency_pos',
        'monetary_pos',
    ];

    protected $casts = [
        'iteration_number' => 'integer',
        'cluster_id'       => 'integer',
        'recency_pos'      => 'decimal:6',
        'frequency_pos'    => 'decimal:6',
        'monetary_pos'     => 'decimal:6',
    ];

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }
}