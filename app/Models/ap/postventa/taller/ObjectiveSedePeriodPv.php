<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;

class ObjectiveSedePeriodPv extends Model
{
  protected $table = 'objective_sede_period_pv';

  protected $fillable = [
    'sede_id',
    'year',
    'month',
    'amount'
  ];

  protected $casts = [
    'year' => 'integer',
    'month' => 'integer',
    'amount' => 'decimal:2'
  ];

  const filters = [
    'sede_id' => '=',
    'year' => '=',
    'month' => '=',
  ];

  const sorts = [
    'year',
    'month',
  ];

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function conceptObjectives()
  {
    return $this->hasMany(ConceptObjectivePeriodPv::class, 'objective_sede_period_pv_id');
  }
}
