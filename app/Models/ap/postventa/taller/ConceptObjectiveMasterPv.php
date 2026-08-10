<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConceptObjectiveMasterPv extends Model
{
  use SoftDeletes;

  protected $table = 'concept_objective_master_pv';

  protected $fillable = [
    'area_id',
    'description',
    'is_vehicular_crossing',
    'status',
    'order'
  ];

  protected $casts = [
    'is_vehicular_crossing' => 'boolean',
    'status' => 'boolean',
    'order' => 'integer'
  ];

  const filters = [
    'search' => ['description'],
    'area_id' => '=',
    'is_vehicular_crossing' => '=',
    'status' => '=',
  ];

  const sorts = [
    'description',
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = strtoupper($value);
  }

  public function area()
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function typePlannings()
  {
    return $this->belongsToMany(
      TypePlanningWorkOrder::class,
      'type_planning_concept_objective_master_pv',
      'concept_objective_master_pv_id',
      'type_planning_work_order_id'
    )->withTimestamps();
  }
}
