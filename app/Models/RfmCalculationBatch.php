<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfmCalculationBatch extends Model
{
    protected $fillable = [
        'triggered_by', 'k_clusters', 'max_iterations', 'actual_iterations',
        'inertia', 'data_from', 'data_to', 'total_customers',
        'status', 'error_message', 'duration_ms', 'final_centroids', 'cluster_labels',
    ];
 
    protected $casts = [
        'final_centroids' => 'array',
        'cluster_labels'  => 'array',
        'data_from'       => 'date',
        'data_to'         => 'date',
    ];
 
    public function triggeredBy()  { return $this->belongsTo(User::class, 'triggered_by'); }
    public function rfmScores()    { return $this->hasMany(RfmScore::class, 'calculation_batch_id'); }
    public function segmentHistory(){ return $this->hasMany(RfmSegmentHistory::class, 'calculation_batch_id'); }
}
