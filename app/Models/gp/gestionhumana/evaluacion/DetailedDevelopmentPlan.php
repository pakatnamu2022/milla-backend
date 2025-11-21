<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DetailedDevelopmentPlan extends Model
{
  use SoftDeletes;

  protected $table = 'detailed_development_plan';

  protected $fillable = [
    'title',
    'description',
    'comment',
    'start_date',
    'end_date',
    'worker_id',
    'boss_id',
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
  ];

  const filters = [
    'search' => ['description', 'worker.nombre_completo', 'boss.nombre_completo'],
    'worker_id' => '=',
    'boss_id' => '=',
  ];

  const sorts = [
    'id',
    'description',
    'worker_id',
    'boss_id',
    'created_at',
    'updated_at',
  ];

  public function setTitleAttribute($value)
  {
    if ($value) {
      $this->attributes['title'] = Str::upper($value);
    }
  }

  public function setDescriptionAttribute($value)
  {
    if ($value) {
      $this->attributes['description'] = Str::upper($value);
    }
  }

  public function setCommentAttribute($value)
  {
    if ($value) {
      $this->attributes['comment'] = Str::upper($value);
    }
  }

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function boss()
  {
    return $this->belongsTo(Worker::class, 'boss_id');
  }

  public function tasks()
  {
    return $this->hasMany(DevelopmentPlanTask::class, 'detailed_development_plan_id');
  }

  public function objectivesCompetences()
  {
    return $this->hasMany(DevelopmentPlanObjectiveCompetence::class, 'development_plan_id');
  }

  public function objectives()
  {
    return $this->hasMany(DevelopmentPlanObjectiveCompetence::class, 'development_plan_id')
      ->whereNotNull('objective_detail_id');
  }

  public function competences()
  {
    return $this->hasMany(DevelopmentPlanObjectiveCompetence::class, 'development_plan_id')
      ->whereNotNull('competence_detail_id');
  }
}
