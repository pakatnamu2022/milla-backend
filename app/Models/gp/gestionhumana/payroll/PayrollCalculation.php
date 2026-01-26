<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollCalculation extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_calculations';

  protected $fillable = [
    'period_id',
    'worker_id',
    'company_id',
    'sede_id',
    'total_normal_hours',
    'total_extra_hours_25',
    'total_extra_hours_35',
    'total_night_hours',
    'total_holiday_hours',
    'days_worked',
    'days_absent',
    'gross_salary',
    'total_earnings',
    'total_deductions',
    'net_salary',
    'employer_cost',
    'status',
    'calculated_at',
    'calculated_by',
    'approved_at',
    'approved_by',
  ];

  protected $casts = [
    'total_normal_hours' => 'decimal:2',
    'total_extra_hours_25' => 'decimal:2',
    'total_extra_hours_35' => 'decimal:2',
    'total_night_hours' => 'decimal:2',
    'total_holiday_hours' => 'decimal:2',
    'days_worked' => 'integer',
    'days_absent' => 'integer',
    'gross_salary' => 'decimal:2',
    'total_earnings' => 'decimal:2',
    'total_deductions' => 'decimal:2',
    'net_salary' => 'decimal:2',
    'employer_cost' => 'decimal:2',
    'calculated_at' => 'datetime',
    'approved_at' => 'datetime',
  ];

  // Calculation statuses
  const STATUS_DRAFT = 'DRAFT';
  const STATUS_CALCULATED = 'CALCULATED';
  const STATUS_APPROVED = 'APPROVED';
  const STATUS_PAID = 'PAID';

  const STATUSES = [
    self::STATUS_DRAFT,
    self::STATUS_CALCULATED,
    self::STATUS_APPROVED,
    self::STATUS_PAID,
  ];

  const filters = [
    'search' => ['worker.nombre_completo'],
    'period_id' => '=',
    'worker_id' => '=',
    'company_id' => '=',
    'sede_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'worker_id',
    'gross_salary',
    'net_salary',
    'status',
    'calculated_at',
    'created_at',
  ];

  /**
   * Get the period for this calculation
   */
  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }

  /**
   * Get the worker for this calculation
   */
  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  /**
   * Get the company for this calculation
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  /**
   * Get the sede for this calculation
   */
  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  /**
   * Get the user who calculated
   */
  public function calculatedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'calculated_by');
  }

  /**
   * Get the user who approved
   */
  public function approvedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by');
  }

  /**
   * Get all details for this calculation
   */
  public function details(): HasMany
  {
    return $this->hasMany(PayrollCalculationDetail::class, 'calculation_id')->orderBy('calculation_order');
  }

  /**
   * Get earnings details
   */
  public function earnings(): HasMany
  {
    return $this->hasMany(PayrollCalculationDetail::class, 'calculation_id')
      ->where('type', PayrollConcept::TYPE_EARNING)
      ->orderBy('calculation_order');
  }

  /**
   * Get deductions details
   */
  public function deductions(): HasMany
  {
    return $this->hasMany(PayrollCalculationDetail::class, 'calculation_id')
      ->where('type', PayrollConcept::TYPE_DEDUCTION)
      ->orderBy('calculation_order');
  }

  /**
   * Get employer contributions details
   */
  public function employerContributions(): HasMany
  {
    return $this->hasMany(PayrollCalculationDetail::class, 'calculation_id')
      ->where('type', PayrollConcept::TYPE_EMPLOYER_CONTRIBUTION)
      ->orderBy('calculation_order');
  }

  /**
   * Scope to get calculations by period
   */
  public function scopeByPeriod($query, int $periodId)
  {
    return $query->where('period_id', $periodId);
  }

  /**
   * Scope to get calculations by worker
   */
  public function scopeByWorker($query, int $workerId)
  {
    return $query->where('worker_id', $workerId);
  }

  /**
   * Scope to get calculated calculations
   */
  public function scopeCalculated($query)
  {
    return $query->where('status', self::STATUS_CALCULATED);
  }

  /**
   * Check if calculation can be modified
   */
  public function canModify(): bool
  {
    return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CALCULATED]);
  }

  /**
   * Check if calculation can be approved
   */
  public function canApprove(): bool
  {
    return $this->status === self::STATUS_CALCULATED;
  }
}
