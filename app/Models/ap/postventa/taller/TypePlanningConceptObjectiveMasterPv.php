<?php

namespace App\Models\ap\postventa\taller;

use Illuminate\Database\Eloquent\Model;

class TypePlanningConceptObjectiveMasterPv extends Model
{
  protected $table = 'type_planning_concept_objective_master_pv';

  protected $fillable = [
    'concept_objective_master_pv_id',
    'type_planning_work_order_id'
  ];

  public $incrementing = false;

  public function conceptObjective()
  {
    return $this->belongsTo(ConceptObjectiveMasterPv::class, 'concept_objective_master_pv_id');
  }

  public function typePlanning()
  {
    return $this->belongsTo(TypePlanningWorkOrder::class, 'type_planning_work_order_id');
  }
}
