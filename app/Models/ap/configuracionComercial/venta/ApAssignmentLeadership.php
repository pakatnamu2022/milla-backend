<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignmentLeadership extends Model
{
  use SoftDeletes;

  protected $table = 'ap_assignment_leadership_periods';

  protected $fillable = [
    'boss_id',
    'worker_id',
    'year',
    'month',
    'status',
  ];

  const filters = [
    'search' => ['boss_id', 'worker_id'],
    'boss_id' => '=',
    'worker_id' => '=',
    'year' => '=',
    'month' => '=',
    'status' => '=',
  ];

  const sorts = [
    'boss_id',
    'worker_id',
    'year',
    'month',
  ];
  
  public function boss()
  {
    return $this->belongsTo(Worker::class, 'boss_id');
  }

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }
}
