<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryIncrease extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_salary_increases';

  protected $fillable = [
    'worker_id',
    'previous_salary',
    'new_salary',
    'effective_date',
    'reason',
    'created_by',
  ];

  protected $casts = [
    'previous_salary' => 'decimal:2',
    'new_salary' => 'decimal:2',
    'effective_date' => 'date:Y-m-d',
  ];

  const filters = [
    'worker_id' => '=',
  ];

  const sorts = [
    'effective_date',
    'created_at',
  ];

  /** Último aumento del trabajador con fecha efectiva <= $date. */
  public static function latestAtDate(int $workerId, string $date): ?self
  {
    return static::where('worker_id', $workerId)
      ->whereDate('effective_date', '<=', $date)
      ->orderByDesc('effective_date')
      ->orderByDesc('id')
      ->first();
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }
}
