<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmSegmentHistory extends Model
{
    use HasFactory;

    protected $table = 'rfm_segment_history';

    protected $fillable = [
        'customer_id',
        'rfm_score_id',
        'calculation_batch_id',
        'segment_from',
        'segment_to',
        'recency_days',
        'frequency',
        'monetary',
        'rfm_score',
        'is_segment_changed',
    ];

    protected $casts = [
        'recency_days'        => 'integer',
        'frequency'           => 'integer',
        'monetary'            => 'decimal:2',
        'rfm_score'           => 'decimal:2',
        'is_segment_changed'  => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function rfmScore(): BelongsTo
    {
        return $this->belongsTo(RfmScore::class, 'rfm_score_id');
    }

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }
}