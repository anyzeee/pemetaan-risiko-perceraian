<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'program',
        'description',
        'risk_level',
        'priority',
        'implementing_agency',
        'target_population',
        'estimated_duration',
        'cause_factors',
        'target_districts',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'cause_factors'    => 'array',
        'target_districts' => 'array',
        'is_completed'     => 'boolean',
        'completed_at'     => 'date',
    ];
}