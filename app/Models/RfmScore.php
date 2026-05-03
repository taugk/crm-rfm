<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfmScore extends Model
{
    use HasFactory;

    protected $table = 'rfm_scores';

    protected $fillable = [
        'customer_id',
        'calculation_batch_id',
        'recency_days',
        'frequency',
        'monetary',
        'recency_norm',
        'frequency_norm',
        'monetary_norm',
        'rfm_score',
        'cluster_id',
        'segment_name',
        'distance_to_centroid',
    ];

    protected $casts = [
        'recency_days'        => 'integer',
        'frequency'           => 'integer',
        'monetary'            => 'decimal:2',
        'recency_norm'        => 'decimal:6',
        'frequency_norm'      => 'decimal:6',
        'monetary_norm'       => 'decimal:6',
        'rfm_score'           => 'decimal:6',
        'cluster_id'          => 'integer',
        'distance_to_centroid'=> 'decimal:6',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }

    public function segmentHistories(): HasMany
    {
        return $this->hasMany(RfmSegmentHistory::class, 'rfm_score_id');
    }
}