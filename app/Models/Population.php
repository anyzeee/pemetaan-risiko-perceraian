<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Population extends Model
// {
//     protected $fillable = [
//         'district_id',
//         'year',
//         'count',
//     ];

//     public function district(): BelongsTo
//     {
//         return $this->belongsTo(District::class);
//     }
// }

{
    protected $table = 'populations';
    protected $fillable = [
        'district_id', 'year',
        'jumlah_penduduk_ribu', 'laju_pertumbuhan',
        'persentase_penduduk', 'kepadatan_per_km2', 'rasio_jenis_kelamin',
    ];
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
}