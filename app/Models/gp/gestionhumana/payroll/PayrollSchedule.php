<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSchedule extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_schedules';

  protected $fillable = [
    'worker_id',
    'code',
    'period_id',
    'work_date',
    'hours_worked',
    'extra_hours',
    'notes',
    'status',
  ];

  protected $casts = [
    'work_date' => 'date',
    'hours_worked' => 'decimal:2',
    'extra_hours' => 'decimal:2',
  ];

  // Schedule statuses
  const STATUS_SCHEDULED = 'SCHEDULED';
  const STATUS_WORKED = 'WORKED';
  const STATUS_ABSENT = 'ABSENT';
  const STATUS_VACATION = 'VACATION';
  const STATUS_SICK_LEAVE = 'SICK_LEAVE';
  const STATUS_PERMISSION = 'PERMISSION';

  const STATUSES = [
    self::STATUS_SCHEDULED,
    self::STATUS_WORKED,
    self::STATUS_ABSENT,
    self::STATUS_VACATION,
    self::STATUS_SICK_LEAVE,
    self::STATUS_PERMISSION,
  ];

  const filters = [
    'search' => ['notes'],
    'worker_id' => '=',
    'code' => '=',
    'period_id' => '=',
    'work_date' => 'date_between',
    'status' => '=',
  ];

  const sorts = [
    'work_date',
    'worker_id',
    'status',
    'created_at',
  ];

  /**
   * Get the worker for this schedule
   */
  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  /**
   * Get the period for this schedule
   */
  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }

  /**
   * Scope to get schedules by worker
   */
  public function scopeByWorker($query, int $workerId)
  {
    return $query->where('worker_id', $workerId);
  }

  /**
   * Scope to get schedules by period
   */
  public function scopeByPeriod($query, int $periodId)
  {
    return $query->where('period_id', $periodId);
  }

  /**
   * Scope to get worked schedules
   */
  public function scopeWorked($query)
  {
    return $query->where('status', self::STATUS_WORKED);
  }

  /**
   * Scope to get absent schedules
   */
  public function scopeAbsent($query)
  {
    return $query->where('status', self::STATUS_ABSENT);
  }

  /**
   * Get total hours for this schedule
   */
  public function getTotalHoursAttribute(): float
  {
    return (float) $this->hours_worked + (float) $this->extra_hours;
  }
}
