<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmKmeansIteration extends Model
{
    use HasFactory;

    protected $table = 'rfm_kmeans_iterations';

    protected $fillable = [
        'calculation_batch_id',
        'iteration_number',
        'wcss',
        'assignments_changed',
        'cluster_sizes',
        'is_converged',
    ];

    protected $casts = [
        'iteration_number'   => 'integer',
        'wcss'               => 'decimal:6',
        'assignments_changed'=> 'integer',
        'cluster_sizes'      => 'array',
        'is_converged'       => 'boolean',
    ];

    public function calculationBatch(): BelongsTo
    {
        return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id');
    }
}