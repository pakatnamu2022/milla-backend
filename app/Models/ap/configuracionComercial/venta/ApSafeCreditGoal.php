<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApSafeCreditGoal extends Model
{
  use SoftDeletes;

  protected $table = 'ap_safe_credit_goal';

  protected $fillable = [
    'year',
    'month',
    'goal_amount',
    'type',
    'sede_id',
  ];

  const filters = [
    'search' => ['sede.abreviatura', 'type'],
    'year' => '=',
    'month' => '=',
    'sede_id' => '=',
  ];

  const sorts = [
    'id',
    'year',
    'month',
    'goal_amount',
    'type',
    'sede_id',
  ];

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
