<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollFoodCard extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_food_card';

  protected $fillable = [
    'worker_id',
    'period_id',
    'amount',
    'applies',
    'num_doc',
    'full_name',
    'status',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'applies' => 'boolean',
  ];

  const filters = [
    'search' => ['num_doc', 'full_name'],
    'worker_id' => '=',
    'period_id' => 'in_or_equal',
    'applies' => '=',
  ];

  const sorts = [
    'worker_id',
    'period_id',
    'amount',
    'full_name',
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
