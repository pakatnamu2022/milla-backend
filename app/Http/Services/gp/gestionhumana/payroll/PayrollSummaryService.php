<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollCalculation;

class PayrollSummaryService
{
  /**
   * Concept codes whose total pay is doubled (feriados)
   * FNT = Feriado Noche, FDT = Feriado Día Trabajado
   */
  const HOLIDAY_CODES = ['FNT', 'FDT'];

  /**
   * Concept codes whose total pay is doubled (descansos trabajados)
   * DNT = Descanso Trabajado Noche, DDT = Descanso Trabajado Día
   */
  const COMPENSATORY_CODES = ['DNT', 'DDT'];

  /**
   * Calculate payslip summary from calculation details.
   *
   * Formula:
   *   REM. BASICA    = salary / 30 * days_worked
   *   HE 25%         = SUM(EARNING where multiplier = 1.25)
   *   HE 35%         = SUM(EARNING where multiplier = 1.35)
   *   FERIADO        = SUM(EARNING where code IN HOLIDAY_CODES) * 2
   *   DDT            = SUM(EARNING where code IN COMPENSATORY_CODES) * 2
   *   REM. BRUTA     = SUM(all EARNING) - HE25% - HE35%
   *   BONIF. NOCT    = REM. BRUTA - REM. BASICA
   *   PAGO (neto)    = REM.BASICA + BONIF.NOCT + HE25% + HE35% + FERIADO + DDT + SUM(DEDUCTIONS)
   */
  public function calculate(PayrollCalculation $calc): array
  {
    if (!$calc->relationLoaded('details')) {
      $calc->load('details');
    }

    $details   = $calc->details;
    $earnings  = $details->where('type', 'EARNING');
    $deductions = $details->where('type', 'DEDUCTION');

    // HE 25% y HE 35%
    $overtime25 = round($earnings->where('multiplier', '1.2500')->sum('amount'), 2);
    $overtime35 = round($earnings->where('multiplier', '1.3500')->sum('amount'), 2);

    // FERIADO: suma de códigos de feriado × 2
    $holidayBase = $earnings->whereIn('concept_code', self::HOLIDAY_CODES)->sum('amount');
    $holidayPay  = round($holidayBase * 2, 2);

    // DDT: suma de códigos de descanso trabajado × 2
    $compensatoryBase = $earnings->whereIn('concept_code', self::COMPENSATORY_CODES)->sum('amount');
    $compensatoryPay  = round($compensatoryBase * 2, 2);

    // REM. BRUTA = total EARNING - HE25% - HE35%
    $grossSalary = round($earnings->sum('amount') - $overtime25 - $overtime35, 2);

    // Días trabajados: usa el valor guardado; si es 0, lo reconstruye desde los detalles
    $daysWorked = (int) $calc->days_worked > 0
      ? (int) $calc->days_worked
      : $earnings->groupBy('concept_code')
                 ->map(fn($g) => (int) $g->first()->days_worked)
                 ->sum();

    // REM. BASICA = sueldo / 30 * días trabajados
    $basicSalary = $daysWorked > 0
      ? round((float) $calc->salary / 30 * $daysWorked, 2)
      : 0;

    // BONIF. NOCT = REM. BRUTA - REM. BASICA
    $nightBonus = round($grossSalary - $basicSalary, 2);

    // PAGO total (las deducciones ya vienen en negativo)
    $totalDeductions = $deductions->sum('amount');
    $netSalary = round(
      $basicSalary + $nightBonus + $overtime25 + $overtime35
        + $holidayPay + $compensatoryPay + $totalDeductions,
      2
    );

    return [
      'basic_salary'     => $basicSalary,
      'night_bonus'      => $nightBonus,
      'overtime_25'      => $overtime25,
      'overtime_35'      => $overtime35,
      'holiday_pay'      => $holidayPay,
      'compensatory_pay' => $compensatoryPay,
      'gross_salary'     => $grossSalary,
      'net_salary'       => $netSalary,
      'days_worked'      => $daysWorked,
    ];
  }

  /**
   * Calculate and persist the payslip summary into the calculation record.
   */
  public function persist(PayrollCalculation $calc): PayrollCalculation
  {
    $summary = $this->calculate($calc);

    $calc->update([
      'basic_salary'     => $summary['basic_salary'],
      'night_bonus'      => $summary['night_bonus'],
      'overtime_25'      => $summary['overtime_25'],
      'overtime_35'      => $summary['overtime_35'],
      'holiday_pay'      => $summary['holiday_pay'],
      'compensatory_pay' => $summary['compensatory_pay'],
      'gross_salary'     => $summary['gross_salary'],
      'net_salary'       => $summary['net_salary'],
      'days_worked'      => $summary['days_worked'],
    ]);

    return $calc->fresh();
  }
}