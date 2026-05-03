<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RfmCalculationBatch extends Model
{
    use HasFactory;

    protected $table = 'rfm_calculation_batches';

    protected $fillable = [
        'triggered_by',
        'k_clusters',
        'max_iterations',
        'actual_iterations',
        'inertia',
        'dbi_score',
        'data_from',
        'data_to',
        'total_customers',
        'status',
        'error_message',
        'duration_ms',
        'final_centroids',
        'cluster_labels',
    ];

    protected $casts = [
        'k_clusters'       => 'integer',
        'max_iterations'   => 'integer',
        'actual_iterations'=> 'integer',
        'inertia'          => 'decimal:6',
        'dbi_score'        => 'decimal:6',
        'data_from'        => 'date',
        'data_to'          => 'date',
        'total_customers'  => 'integer',
        'duration_ms'      => 'integer',
        'final_centroids'  => 'array',
        'cluster_labels'   => 'array',
    ];

    const STATUS_RUNNING   = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function rawCustomers(): HasMany
    {
        return $this->hasMany(RfmCustomerRaw::class, 'calculation_batch_id');
    }

    public function normalizedCustomers(): HasMany
    {
        return $this->hasMany(RfmCustomerNormalized::class, 'calculation_batch_id');
    }

    public function kmeansIterations(): HasMany
    {
        return $this->hasMany(RfmKmeansIteration::class, 'calculation_batch_id');
    }

    public function centroids(): HasMany
    {
        return $this->hasMany(RfmCentroid::class, 'calculation_batch_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RfmKmeansAssignment::class, 'calculation_batch_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RfmScore::class, 'calculation_batch_id');
    }

    public function dbiScore(): HasOne
    {
        return $this->hasOne(RfmDbiScore::class, 'calculation_batch_id');
    }

    public function segmentHistories(): HasMany
    {
        return $this->hasMany(RfmSegmentHistory::class, 'calculation_batch_id');
    }
}