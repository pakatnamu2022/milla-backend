<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignmentLeadershipPeriod extends Model
{
  use SoftDeletes;

  protected $table = 'ap_assignment_leadership_periods';

  protected $fillable = [
    'boss_id',
    'worker_id',
    'year',
    'month',
  ];

  const filters = [
    'search' => ['boss_id', 'worker_id'],
    'boss_id' => '=',
    'worker_id' => '=',
    'year' => '=',
    'month' => '=',
  ];

  const sorts = [
    'boss_id',
    'worker_id',
    'year',
    'month',
  ];

  public function boss()
  {
    return $this->belongsTo(Person::class, 'boss_id');
  }

  public function worker()
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }
}
