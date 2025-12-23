<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderLabour extends Model
{
  use SoftDeletes;

  protected $table = 'work_order_labour';

  protected $fillable = [
    'description',
    'time_spent',
    'hourly_rate',
    'total_cost',
    'worker_id',
    'work_order_id'
  ];

  const filters = [
    'search' => ['description', 'worker.worker_id'],
    'worker_id' => '=',
    'work_order_id' => '=',
  ];

  const sorts = [
    'id',
    'description',
    'time_spent',
    'hourly_rate',
    'total_cost',
    'worker_id',
    'created_at',
  ];

  protected $casts = [
    'hourly_rate' => 'decimal:2',
    'total_cost' => 'decimal:2',
  ];

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = strtoupper($value);
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }
}
