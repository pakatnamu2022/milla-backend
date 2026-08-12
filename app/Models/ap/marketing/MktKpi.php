<?php

namespace App\Models\ap\marketing;

use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MktKpi extends Model
{
  protected $table = 'ap_mkt_kpis';

  protected $fillable = [
    'activity_id',
    'period_month',
    'period_year',
    'leads',
    'sales',
    'investment',
    'currency_id',
    'notes',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'period_month' => 'integer',
    'period_year'  => 'integer',
    'leads'        => 'integer',
    'sales'        => 'integer',
    'investment'   => 'decimal:2',
  ];

  const filters = [
    'activity_id'  => '=',
    'period_month' => '=',
    'period_year'  => '=',
    'currency_id'  => '=',
  ];

  const sorts = [
    'period_month',
    'period_year',
    'leads',
    'sales',
    'investment',
    'created_at',
  ];

  public function activity(): BelongsTo
  {
    return $this->belongsTo(MktActivity::class, 'activity_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
  }
}
