<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRegister extends BaseModel
{
  protected $table = 'gh_payroll_register';

  protected $fillable = [
    'period_id',
    'worker_id',
    'worker_name',
    'worker_vat',
    // Datos del período
    'cost_center',
    'status',
    'occupation',
    'monthly_salary',
    'afp_affiliation',
    'has_family_allowance',
    'has_essalud_vida',
    // Días
    'days_worked',
    'days_vacation',
    'days_medical_rest',
    'days_absence',
    'days_leave_unpaid',
    'days_leave_paid',
    'days_subsidy',
    'days_not_worked',
    'days_effective',
    'normal_hours',
    'has_vacation',
    'has_subsidy',
    'calc_days_worked',
    'calc_days_not_worked',
    // Ingresos
    'basic_salary',
    'family_allowance',
    'overtime_25',
    'overtime_35',
    'subsidy_disability',
    'work_conditions',
    'vacation_pay',
    'production_bonus',
    'holiday_days_pay',
    'worked_rest_days_pay',
    'night_bonus',
    'commercial_bonus',
    'schooling_allowance',
    'food_benefit',
    'total_income',
    // BB.SS truncos
    'cts_truncated',
    'gratification',
    'extraordinary_bonus',
    'vacation_truncated',
    // Descuentos
    'onp_deduction',
    'bonus_referral',
    'afp_mandatory',
    'afp_insurance',
    'afp_commission',
    'afp_total',
    'income_tax_5th',
    'oncosalud_plan',
    'advances_loans',
    'other_deductions',
    'judicial_deductions',
    'grace_amount',
    'total_deductions',
    // Netos y fin de año
    'net_pay_preliminary',
    'christmas_gratification',
    'christmas_extraordinary_bonus',
    'aguinaldo',
    'net_pay_plus_aguinaldo',
    // Aportes empleador
    'cts_employer',
    'essalud_employer',
    'sctr_total',
    'life_insurance',
    'sctr_health',
    'sctr_pension',
    'employer_contributions_total',
    // Netos finales
    'vacation_paid_preliminary',
    'net_pay_final',
    'worker_deduction_total',
  ];

  protected $casts = [
    // Período
    'monthly_salary'               => 'decimal:2',
    'has_family_allowance'         => 'boolean',
    'has_essalud_vida'             => 'boolean',
    // Días
    'days_worked'                  => 'decimal:1',
    'days_vacation'                => 'decimal:1',
    'days_medical_rest'            => 'decimal:1',
    'days_absence'                 => 'decimal:1',
    'days_leave_unpaid'            => 'decimal:1',
    'days_leave_paid'              => 'decimal:1',
    'days_subsidy'                 => 'decimal:1',
    'days_not_worked'              => 'decimal:1',
    'days_effective'               => 'decimal:1',
    'normal_hours'                 => 'decimal:2',
    'has_vacation'                 => 'boolean',
    'has_subsidy'                  => 'boolean',
    'calc_days_worked'             => 'decimal:1',
    'calc_days_not_worked'         => 'decimal:1',
    // Ingresos
    'basic_salary'                 => 'decimal:2',
    'family_allowance'             => 'decimal:2',
    'overtime_25'                  => 'decimal:2',
    'overtime_35'                  => 'decimal:2',
    'subsidy_disability'           => 'decimal:2',
    'work_conditions'              => 'decimal:2',
    'vacation_pay'                 => 'decimal:2',
    'production_bonus'             => 'decimal:2',
    'holiday_days_pay'             => 'decimal:2',
    'worked_rest_days_pay'         => 'decimal:2',
    'night_bonus'                  => 'decimal:2',
    'commercial_bonus'             => 'decimal:2',
    'schooling_allowance'          => 'decimal:2',
    'food_benefit'                 => 'decimal:2',
    'total_income'                 => 'decimal:2',
    // BB.SS truncos
    'cts_truncated'                => 'decimal:2',
    'gratification'                => 'decimal:2',
    'extraordinary_bonus'          => 'decimal:2',
    'vacation_truncated'           => 'decimal:2',
    // Descuentos
    'onp_deduction'                => 'decimal:2',
    'bonus_referral'               => 'decimal:2',
    'afp_mandatory'                => 'decimal:2',
    'afp_insurance'                => 'decimal:2',
    'afp_commission'               => 'decimal:2',
    'afp_total'                    => 'decimal:2',
    'income_tax_5th'               => 'decimal:2',
    'oncosalud_plan'               => 'decimal:2',
    'advances_loans'               => 'decimal:2',
    'other_deductions'             => 'decimal:2',
    'judicial_deductions'          => 'decimal:2',
    'grace_amount'                 => 'decimal:2',
    'total_deductions'             => 'decimal:2',
    // Netos y fin de año
    'net_pay_preliminary'          => 'decimal:2',
    'christmas_gratification'      => 'decimal:2',
    'christmas_extraordinary_bonus'=> 'decimal:2',
    'aguinaldo'                    => 'decimal:2',
    'net_pay_plus_aguinaldo'       => 'decimal:2',
    // Aportes empleador
    'cts_employer'                 => 'decimal:2',
    'essalud_employer'             => 'decimal:2',
    'sctr_total'                   => 'decimal:2',
    'life_insurance'               => 'decimal:2',
    'sctr_health'                  => 'decimal:2',
    'sctr_pension'                 => 'decimal:2',
    'employer_contributions_total' => 'decimal:2',
    // Netos finales
    'vacation_paid_preliminary'    => 'decimal:2',
    'net_pay_final'                => 'decimal:2',
    'worker_deduction_total'       => 'decimal:2',
  ];

  const filters = [
    'search'    => ['worker_name', 'worker_vat'],
    'period_id' => '=',
    'worker_id' => '=',
    'status'    => '=',
  ];

  const sorts = [
    'worker_name',
    'net_pay_final',
    'total_income',
    'total_deductions',
    'created_at',
  ];

  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function scopeByPeriod($query, int $periodId)
  {
    return $query->where('period_id', $periodId);
  }

  public function scopeByWorker($query, int $workerId)
  {
    return $query->where('worker_id', $workerId);
  }
}
