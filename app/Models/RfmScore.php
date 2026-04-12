<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfmScore extends Model
{
    protected $fillable = [
        'customer_id', 'recency_days', 'frequency', 'monetary',
        'recency_norm', 'frequency_norm', 'monetary_norm',
        'r_score', 'f_score', 'm_score', 'rfm_score', 'rfm_label',
        'cluster_id', 'segment_name', 'distance_to_centroid',
        'calculation_batch_id',
    ];
 
    protected $casts = [
        'monetary'          => 'float',
        'recency_norm'      => 'float',
        'frequency_norm'    => 'float',
        'monetary_norm'     => 'float',
        'rfm_score'         => 'float',
        'distance_to_centroid' => 'float',
    ];
 
    public function customer()         { return $this->belongsTo(Customers::class); }
    public function calculationBatch() { return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id'); }
    public function segmentHistory()   { return $this->hasMany(RfmSegmentHistory::class); }
}
