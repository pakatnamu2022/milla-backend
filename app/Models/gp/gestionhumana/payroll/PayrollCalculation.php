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
    'biweekly',
    'worker_id',
    'company_id',
    'sede_id',
    'salary',
    'shift_hours',
    'base_hour_value',
    'vacation_hour_value',
    'total_normal_hours',
    'total_extra_hours_25',
    'total_extra_hours_35',
    'total_night_hours',
    'total_holiday_hours',
    'days_worked',
    'days_absent',
    'days_vacation',
    'gross_salary',
    'total_earnings',
    'total_deductions',
    'total_contributions',
    'net_salary',
    'employer_cost',
    'basic_salary',
    'night_bonus',
    'overtime_25',
    'overtime_35',
    'holiday_pay',
    'compensatory_pay',
    'status',
    'calculated_at',
    'calculated_by',
    'approved_at',
    'approved_by',
    'paid_at',
    'paid_by',
  ];

  protected $casts = [
    'biweekly' => 'integer',
    'salary' => 'decimal:2',
    'shift_hours' => 'decimal:2',
    'base_hour_value' => 'decimal:2',
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
    'total_contributions' => 'decimal:2',
    'net_salary' => 'decimal:2',
    'employer_cost' => 'decimal:2',
    'basic_salary' => 'decimal:2',
    'night_bonus' => 'decimal:2',
    'overtime_25' => 'decimal:2',
    'overtime_35' => 'decimal:2',
    'holiday_pay' => 'decimal:2',
    'compensatory_pay' => 'decimal:2',
    'calculated_at' => 'datetime',
    'approved_at' => 'datetime',
    'paid_at' => 'datetime',
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

  /**
   * Calculate average of last 6 months for variable concepts
   *
   * @param int $periodId Current period ID
   * @param int $workerId Worker ID
   * @param int $companyId Company ID
   * @return object
   */
  public static function calcularPromedioUltimos6Meses(int $periodId, int $workerId, int $companyId)
  {
    // Obtener el periodo actual para saber año y mes
    $currentPeriod = PayrollPeriod::find($periodId);

    if (!$currentPeriod) {
      return (object)[
        'avg_overtime' => 0,
        'avg_holiday' => 0,
        'avg_compensatory' => 0,
        'avg_night_bonus' => 0,
        'total_avg' => 0,
        'months_counted' => 0,
      ];
    }

    // Calcular los 6 meses anteriores
    $startYear = $currentPeriod->year;
    $startMonth = $currentPeriod->month;

    $sixMonthsAgo = \Carbon\Carbon::create($startYear, $startMonth, 1)->subMonths(6);

    // Obtener todos los periodos de los últimos 6 meses para esta company
    $periods = PayrollPeriod::where('company_id', $companyId)
      ->where(function ($query) use ($sixMonthsAgo, $currentPeriod) {
        $query->where('year', '>', $sixMonthsAgo->year)
          ->orWhere(function ($q) use ($sixMonthsAgo) {
            $q->where('year', '=', $sixMonthsAgo->year)
              ->where('month', '>=', $sixMonthsAgo->month);
          });
      })
      ->where(function ($query) use ($currentPeriod) {
        $query->where('year', '<', $currentPeriod->year)
          ->orWhere(function ($q) use ($currentPeriod) {
            $q->where('year', '=', $currentPeriod->year)
              ->where('month', '<', $currentPeriod->month);
          });
      })
      ->pluck('id');

    // Obtener cálculos de nómina de esos periodos para el trabajador
    $calculations = self::whereIn('period_id', $periods)
      ->where('worker_id', $workerId)
      ->where('company_id', $companyId)
      ->where('status', '!=', self::STATUS_DRAFT)
      ->with('period')
      ->get();

    if ($calculations->isEmpty()) {
      return (object)[
        'avg_overtime' => 0,
        'avg_holiday' => 0,
        'avg_compensatory' => 0,
        'avg_night_bonus' => 0,
        'total_avg' => 0,
        'months_counted' => 0,
      ];
    }

    // Agrupar por mes (year-month) y sumar valores (para manejar quincenas)
    $monthlyTotals = [];

    foreach ($calculations as $calc) {
      $key = $calc->period->year . '-' . str_pad($calc->period->month, 2, '0', STR_PAD_LEFT);

      if (!isset($monthlyTotals[$key])) {
        $monthlyTotals[$key] = [
          'overtime' => 0,
          'holiday' => 0,
          'compensatory' => 0,
          'night_bonus' => 0,
        ];
      }

      // Sumar valores (si hay biweekly=1 y biweekly=2, se suman)
      $monthlyTotals[$key]['overtime'] += ($calc->overtime_25 ?? 0) + ($calc->overtime_35 ?? 0);
      $monthlyTotals[$key]['holiday'] += $calc->holiday_pay ?? 0;
      $monthlyTotals[$key]['compensatory'] += $calc->compensatory_pay ?? 0;
      $monthlyTotals[$key]['night_bonus'] += $calc->night_bonus ?? 0;
    }

    // Contar meses únicos
    $monthsCount = count($monthlyTotals);

    if ($monthsCount === 0) {
      return (object)[
        'avg_overtime' => 0,
        'avg_holiday' => 0,
        'avg_compensatory' => 0,
        'avg_night_bonus' => 0,
        'total_avg' => 0,
        'months_counted' => 0,
      ];
    }

    // Calcular totales
    $totalOvertime = array_sum(array_column($monthlyTotals, 'overtime'));
    $totalHoliday = array_sum(array_column($monthlyTotals, 'holiday'));
    $totalCompensatory = array_sum(array_column($monthlyTotals, 'compensatory'));
    $totalNightBonus = array_sum(array_column($monthlyTotals, 'night_bonus'));

    // Calcular promedios
    $avgOvertime = $totalOvertime / $monthsCount;
    $avgHoliday = $totalHoliday / $monthsCount;
    $avgCompensatory = $totalCompensatory / $monthsCount;
    $avgNightBonus = $totalNightBonus / $monthsCount;

    return (object)[
      'avg_overtime' => round($avgOvertime, 2),
      'avg_holiday' => round($avgHoliday, 2),
      'avg_compensatory' => round($avgCompensatory, 2),
      'avg_night_bonus' => round($avgNightBonus, 2),
      'total_avg' => round($avgOvertime + $avgHoliday + $avgCompensatory + $avgNightBonus, 2),
      'months_counted' => $monthsCount,
    ];
  }
}
