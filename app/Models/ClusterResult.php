<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClusterResult extends Model
{
    protected $fillable = [
        'district_id',
        'cluster_id',
        'risk_level',
        'risk_score',
        'divorce_rate',
        'divorce_count',
        'marriage_count',
        'population_count',
        'factor_weight',
        'feat_divorce_rate',
        'feat_divorce_count',
        'feat_marriage_ratio',
        'feat_population',
        'feat_factor_weight',
        'iteration_ran',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}