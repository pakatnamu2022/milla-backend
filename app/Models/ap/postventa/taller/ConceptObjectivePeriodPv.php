<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;

class ConceptObjectivePeriodPv extends Model
{
  protected $table = 'concept_objective_period_pv';

  protected $fillable = [
    'objective_sede_period_pv_id',
    'area_id',
    'description',
    'is_vehicular_crossing',
    'status',
    'sub_amount',
    'order'
  ];

  protected $casts = [
    'is_vehicular_crossing' => 'boolean',
    'status' => 'boolean',
    'sub_amount' => 'decimal:2',
    'order' => 'integer'
  ];

  const filters = [
    'search' => ['description'],
    'objective_sede_period_pv_id' => '=',
    'area_id' => '=',
    'is_vehicular_crossing' => '=',
    'status' => '=',
  ];

  const sorts = [
    'order',
    'description',
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = strtoupper($value);
  }

  public function objectiveSedePeriod()
  {
    return $this->belongsTo(ObjectiveSedePeriodPv::class, 'objective_sede_period_pv_id');
  }

  public function area()
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function typePlannings()
  {
    return $this->belongsToMany(
      TypePlanningWorkOrder::class,
      'type_planning_concept_objective_period_pv',
      'concept_objective_period_pv_id',
      'type_planning_id'
    )->withTimestamps();
  }

  public function advisors()
  {
    return $this->hasMany(ObjectiveAdvisorsPeriodPv::class, 'concept_objective_period_pv_id');
  }
}