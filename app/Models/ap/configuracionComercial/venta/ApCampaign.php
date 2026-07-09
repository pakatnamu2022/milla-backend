<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApCampaign extends Model
{
  use SoftDeletes;

  protected $table = 'ap_campaigns';

  protected $fillable = [
    'area_id',
    'code',
    'name',
    'description',
    'start_date',
    'end_date',
    'discount_type',
    'discount_value',
    'status',
  ];

  protected $casts = [
    'start_date'     => 'date',
    'end_date'       => 'date',
    'discount_value' => 'decimal:2',
    'status'         => 'boolean',
  ];

  const filters = [
    'search'        => ['code', 'name'],
    'area_id'       => '=',
    'discount_type' => '=',
    'status'        => '=',
  ];

  const sorts = [
    'code',
    'name',
    'start_date',
    'end_date',
    'discount_type',
    'discount_value',
    'status',
  ];

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function scopeActive($query)
  {
    $today = now()->toDateString();
    return $query->where('status', true)
      ->where('start_date', '<=', $today)
      ->where('end_date', '>=', $today);
  }
}
