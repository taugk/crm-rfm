<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfmKmeansAssignment extends Model
{
    use HasFactory;

    protected $table = 'rfm_kmeans_assignments';

    protected $fillable = [
        'calculation_batch_id',
        'customer_id',
        'iteration_number',
        'cluster_id',
        'distances_to_all_centroids',
    ];

    protected $casts = [
        'iteration_number'           => 'integer',
        'cluster_id'                 => 'integer',
        'distances_to_all_centroids' => 'array',
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