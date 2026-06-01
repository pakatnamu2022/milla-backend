<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\GpMasters;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollBonus extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_bonuses';

  protected $fillable = [
    'worker_id',
    'period_id',
    'amount',
    'type_id',
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
    'type_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'worker_id',
    'period_id',
    'type_id',
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

  public function type(): BelongsTo
  {
    return $this->belongsTo(GpMasters::class, 'type_id');
  }
}
