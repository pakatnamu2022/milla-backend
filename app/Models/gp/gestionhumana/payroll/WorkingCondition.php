<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkingCondition extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_working_conditions';

  protected $fillable = [
    'worker_id',
    'period_id',
    'amount',
    'status',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'status' => 'integer',
  ];

  const filters = [
    'search' => [],
    'worker_id' => '=',
    'period_id' => '=',
    'status' => '=',
    'period.company_id' => '='
  ];

  const sorts = [
    'worker_id',
    'period_id',
    'amount',
    'created_at',
  ];

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }
}
