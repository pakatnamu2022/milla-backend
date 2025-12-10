<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApWorkOrderAssignOperator extends Model
{
  use SoftDeletes;

  protected $table = 'ap_work_order_assign_operator';

  protected $fillable = [
    'work_order_id',
    'group_number',
    'operator_id',
    'registered_by',
    'status',
    'observations',
  ];

  const filters = [
    'work_order_id' => '=',
    'group_number' => '=',
    'operator_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'work_order_id',
    'operator_id',
    'status',
    'created_at',
  ];

  public function setObservationsAttribute($value)
  {
    if ($value) {
      $this->attributes['observations'] = Str::upper($value);
    }
  }

  public function workOrder()
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function operator()
  {
    return $this->belongsTo(Worker::class, 'operator_id');
  }

  public function registeredBy()
  {
    return $this->belongsTo(User::class, 'registered_by');
  }
}
