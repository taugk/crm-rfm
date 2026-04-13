<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfmSegmentHistory extends Model
{
    protected $table = "rfm_segment_history";
     protected $fillable = [
        'customer_id', 'rfm_score_id', 'calculation_batch_id',
        'segment_from', 'segment_to',
        'recency_days', 'frequency', 'monetary', 'rfm_score',
        'is_segment_changed',
    ];
 
    protected $casts = [
        'monetary'           => 'float',
        'rfm_score'          => 'float',
        'is_segment_changed' => 'boolean',
    ];
 
    public function customer()         { return $this->belongsTo(Customers::class); }
    public function rfmScore()         { return $this->belongsTo(RfmScore::class); }
    public function calculationBatch() { return $this->belongsTo(RfmCalculationBatch::class, 'calculation_batch_id'); }
}
