<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApReceivingInspectionDamage extends Model
{
    use SoftDeletes;

    protected $table = 'ap_receiving_inspection_damages';

    protected $fillable = [
        'receiving_inspection_id',
        'damage_type',
        'x_coordinate',
        'y_coordinate',
        'description',
        'photo_url',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(ApReceivingInspection::class, 'receiving_inspection_id');
    }
}
