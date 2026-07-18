<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marriage extends Model
{
    protected $fillable = [
        'district_id',
        'year',
        'count',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}