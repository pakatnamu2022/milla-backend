<?php

namespace App\Models\ap\postventa\taller;

use Illuminate\Database\Eloquent\Model;

class TypePlanningConceptObjectivePeriodPv extends Model
{
  protected $table = 'type_planning_concept_objective_period_pv';

  protected $fillable = [
    'type_planning_id',
    'concept_objective_period_pv_id'
  ];

  public function typePlanning()
  {
    return $this->belongsTo(TypePlanningWorkOrder::class, 'type_planning_id');
  }

  public function conceptObjectivePeriod()
  {
    return $this->belongsTo(ConceptObjectivePeriodPv::class, 'concept_objective_period_pv_id');
  }
}