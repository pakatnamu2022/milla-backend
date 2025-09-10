<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonCycleDetail extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person_cycle_detail';

  protected $fillable = [
    'person_id',
    'chief_id',
    'position_id',
    'sede_id',
    'area_id',
    'cycle_id',
    'category_id',
    'objective_id',
    'person',
    'chief',
    'position',
    'sede',
    'area',
    'category',
    'objective',
    'goal',
    'weight',
    'fixedWeight',
    'status'
  ];

  const filters = [
    'search' => ['person'],
    'person_id' => '=',
    'chief_id' => '=',
    'position_id' => '=',
    'sede_id' => '=',
    'area_id' => '=',
    'cycle_id' => '=',
    'category_id' => '=',
    'objective_id' => '='
  ];

  const sorts = [
    'id',
    'person',
    'chief',
    'position',
    'sede',
    'area',
    'category',
    'objective',
    'goal',
    'weight',
    'status',
    'created_at',
    'updated_at'
  ];

  public function person()
  {
    return $this->belongsTo(Person::class, 'person_id');
  }

  public function chief()
  {
    return $this->belongsTo(Person::class, 'chief_id');
  }

  public function position()
  {
    return $this->belongsTo(Position::class, 'position_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area()
  {
    return $this->belongsTo(Area::class, 'area_id');
  }

  public function cycle()
  {
    return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
  }

  public function category()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'category_id');
  }

  public function objectiveModel()
  {
    return $this->belongsTo(EvaluationObjective::class, 'objective_id');
  }


}
