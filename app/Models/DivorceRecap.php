<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivorceCase extends Model
// {
//     protected $fillable = [
//         'case_number',
//         'district_id',
//         'category',
//         'year',
//     ];

//     public function district(): BelongsTo
//     {
//         return $this->belongsTo(District::class);
//     }
// }

{
    protected $table = 'divorce_recaps';
    protected $fillable = ['district_id', 'year', 'kategori', 'jenis_perkara', 'jumlah'];
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
}