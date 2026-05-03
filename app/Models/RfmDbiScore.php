<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmDbiScore extends Model
{
    use HasFactory;

    protected $table = 'rfm_dbi_scores';

    protected $fillable = [
        'calculation_batch_id',
        'k',
        'dbi_score',
        'cluster_details',
    ];

    protected $casts = [
        'k'               => 'integer',
        'dbi_score'       => 'decimal:6',
        'cluster_details' => 'array',
    ];

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }
}