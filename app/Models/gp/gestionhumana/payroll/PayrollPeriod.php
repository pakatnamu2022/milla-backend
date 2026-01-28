<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_periods';

  protected $fillable = [
    'code',
    'name',
    'year',
    'month',
    'start_date',
    'end_date',
    'payment_date',
    'status',
    'company_id',
  ];

  protected $casts = [
    'year' => 'integer',
    'month' => 'integer',
    'start_date' => 'date',
    'end_date' => 'date',
    'payment_date' => 'date',
  ];

  // Period statuses
  const STATUS_OPEN = 'OPEN';
  const STATUS_PROCESSING = 'PROCESSING';
  const STATUS_CALCULATED = 'CALCULATED';
  const STATUS_APPROVED = 'APPROVED';
  const STATUS_CLOSED = 'CLOSED';

  const STATUSES = [
    self::STATUS_OPEN,
    self::STATUS_PROCESSING,
    self::STATUS_CALCULATED,
    self::STATUS_APPROVED,
    self::STATUS_CLOSED,
  ];

  const filters = [
    'search' => ['code', 'name'],
    'code' => '=',
    'year' => '=',
    'month' => '=',
    'status' => '=',
    'company_id' => '=',
  ];

  const sorts = [
    'code',
    'name',
    'year',
    'month',
    'start_date',
    'status',
    'created_at',
  ];

  /**
   * Get the company for this period
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  /**
   * Get all schedules for this period
   */
  public function schedules(): HasMany
  {
    return $this->hasMany(PayrollSchedule::class, 'period_id');
  }

  /**
   * Get all calculations for this period
   */
  public function calculations(): HasMany
  {
    return $this->hasMany(PayrollCalculation::class, 'period_id');
  }

  /**
   * Scope to get only open periods
   */
  public function scopeOpen($query)
  {
    return $query->where('status', self::STATUS_OPEN);
  }

  /**
   * Scope to get periods by year
   */
  public function scopeByYear($query, int $year)
  {
    return $query->where('year', $year);
  }

  /**
   * Scope to get periods by company
   */
  public function scopeByCompany($query, int $companyId)
  {
    return $query->where('company_id', $companyId);
  }

  /**
   * Get the current period (latest open period)
   */
  public static function getCurrentPeriod(?int $companyId = null)
  {
    $query = self::open()->orderBy('year', 'desc')->orderBy('month', 'desc');

    if ($companyId) {
      $query->where('company_id', $companyId);
    }

    return $query->first();
  }

  /**
   * Generate period code
   */
  public static function generateCode(int $year, int $month): string
  {
    return sprintf('%d-%02d', $year, $month);
  }

  /**
   * Generate period name
   */
  public static function generateName(int $year, int $month): string
  {
    $months = [
      1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
      5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
      9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    return $months[$month] . ' ' . $year;
  }

  /**
   * Check if period can be modified
   */
  public function canModify(): bool
  {
    return in_array($this->status, [self::STATUS_OPEN, self::STATUS_PROCESSING]);
  }

  /**
   * Check if period can be calculated
   */
  public function canCalculate(): bool
  {
    return in_array($this->status, [self::STATUS_OPEN, self::STATUS_PROCESSING, self::STATUS_CALCULATED]);
  }
}
