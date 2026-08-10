<?php

namespace App\Models\ap\postventa\taller;


use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;

class ObjectiveAdvisorsPeriodPv extends Model
{
  protected $table = 'objective_advisors_period_pv';

  protected $fillable = [
    'worker_id',
    'amount',
    'concept_objective_period_pv_id'
  ];

  protected $casts = [
    'amount' => 'decimal:2'
  ];

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function conceptObjectivePeriod()
  {
    return $this->belongsTo(ConceptObjectivePeriodPv::class, 'concept_objective_period_pv_id');
  }
}
