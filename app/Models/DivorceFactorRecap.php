<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivorceFactor extends Model
// {
//     protected $fillable = [
//         'name',
//         'category',
//         'weight',
//     ];
// }

{
    protected $table = 'divorce_factor_recaps';
    protected $fillable = ['district_id', 'year', 'faktor', 'jumlah_kasus'];
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
}