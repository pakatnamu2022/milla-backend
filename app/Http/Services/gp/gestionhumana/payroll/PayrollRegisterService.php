<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollRegisterExport;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollRegisterResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollFamilyAllowance;
use App\Models\gp\gestionhumana\payroll\PayrollLiquidationBbss;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollRegister;
use App\Models\gp\gestionhumana\payroll\PayrollWorkingCondition;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\GeneralMaster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollRegisterService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollRegister::class,
            $request,
            PayrollRegister::filters,
            PayrollRegister::sorts,
            PayrollRegisterResource::class,
        );
    }

    /**
     * Generar registros de planilla para un periodo y empresa
     *
     * @param int $companyId ID de la empresa
     * @param int $periodId ID del periodo
     * @return array
     */
    public function generate(int $companyId, int $periodId)
    {
        try {
            DB::beginTransaction();

            // Validar que el periodo existe y pertenece a la empresa
            $period = PayrollPeriod::where('id', $periodId)
                ->where('company_id', $companyId)
                ->first();

            if (!$period) {
                throw new Exception('El período no existe o no pertenece a la empresa especificada');
            }

            // Obtener todos los trabajadores activos de la empresa
            // Asumiendo que Worker tiene relación con Sede y Sede con Empresa
            $workers = Worker::whereHas('sede', function ($query) use ($companyId) {
                $query->where('empresa_id', $companyId);
            })
                ->working()
                ->with(['position', 'sede'])
                ->get();

            if ($workers->isEmpty()) {
                throw new Exception('No se encontraron trabajadores activos para esta empresa');
            }

            // Si no hay ningún cálculo (asistencias) generado para el período, seguimos
            // igual (con lo disponible: sueldo, aportes, descuentos, BB.SS. truncos) pero
            // avisamos al frontend para que RRHH sepa que faltan días/horas/extras reales.
            $calculationPending = !PayrollCalculation::where('period_id', $periodId)->exists();

            // Tipos del catálogo GpMasters para BB.SS. truncos (id por código, ver
            // Database\Seeders\gp\gestionhumana\payroll\PayrollLiquidationBbssTypeSeeder).
            $liquidationTypeIds = PayrollLiquidationBbss::typeIdsByCode();

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($workers as $worker) {
                // Verificar si ya existe un registro para este trabajador en este periodo
                $existing = PayrollRegister::where('period_id', $periodId)
                    ->where('worker_id', $worker->id)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                // Obtener datos calculados si existen
                $calculation = PayrollCalculation::where('period_id', $periodId)
                    ->where('worker_id', $worker->id)
                    ->where('company_id', $companyId)
                    ->first();

                // Obtener asignación familiar
                $familyAllowances = PayrollFamilyAllowance::where('period_id', $periodId)
                    ->where('worker_id', $worker->id)
                    ->where('applies', true)
                    ->get();

                $familyAllowanceAmount = $familyAllowances->sum('amount');
                $hasFamilyAllowance = $familyAllowances->isNotEmpty();

                // Obtener bonificaciones (ejemplo: bono comercial, producción, etc.)
                $bonuses = PayrollBonus::where('period_id', $periodId)
                    ->where('worker_id', $worker->id)
                    ->where('status', 1)
                    ->with('type')
                    ->get();

                // Mapear bonificaciones por tipo (aquí necesitarás los type_ids correctos)
                $commercialBonus = $bonuses->where('type_id', 1)->sum('amount'); // TODO: ajustar type_id
                $productionBonus = $bonuses->where('type_id', 2)->sum('amount'); // TODO: ajustar type_id

                // Datos del trabajador (snapshot)
                $workerName = $worker->nombre_completo ?? '';
                $workerVat = $worker->vat ?? '';
                $occupation = $worker->position->name ?? '';
                $costCenter = $worker->sede->nombre ?? '';

                $basicSalary = $calculation->basic_salary ?? (float)($worker->sueldo ?? 0.00);

                // Condiciones de trabajo (mismo patrón que asignación familiar/bonos)
                $workConditions = (float)(PayrollWorkingCondition::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->value('amount') ?? 0.00);

                $totalIncome = $this->calculateTotalIncome([
                    'basic_salary' => $basicSalary,
                    'family_allowance' => $familyAllowanceAmount,
                    'overtime_25' => $calculation->overtime_25 ?? 0.00,
                    'overtime_35' => $calculation->overtime_35 ?? 0.00,
                    'holiday_pay' => $calculation->holiday_pay ?? 0.00,
                    'worked_rest_days_pay' => $calculation->compensatory_pay ?? 0.00,
                    'night_bonus' => $calculation->night_bonus ?? 0.00,
                    'production_bonus' => $productionBonus,
                    'commercial_bonus' => $commercialBonus,
                    'work_conditions' => $workConditions,
                ]);

                // Aportes del empleador: SCTR (salud+pensión), EsSalud, Vida Ley
                $employerContributions = $this->calculateEmployerContributions($worker, $basicSalary, $totalIncome);

                // Descuentos ONP/AFP (según rrhh_persona.sis_pensiones_id -> rrhh_sist_pensiones)
                $pensionDeductions = $this->calculatePensionDeductions($worker, $totalIncome);

                // Renta de 5ta categoría: proyección anual simplificada (ingreso mensual x 12,
                // menos 7 UIT, tramos progresivos 8/14/17/20/30%, prorrateado a cuota mensual).
                $incomeTax5th = $this->calculateIncomeTax5th($totalIncome);

                // BB.SS. truncos: se cargan manualmente en el módulo "Liquidación BB.SS."
                // (gh_payroll_liquidation_bbss) y aquí solo se suman por tipo, igual que
                // ya se hace con los bonos comercial/producción.
                $liquidations = PayrollLiquidationBbss::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->where('status', 1)
                    ->get();

                $sumLiquidation = function (string $code) use ($liquidations, $liquidationTypeIds) {
                    $typeId = $liquidationTypeIds[$code] ?? null;
                    if (!$typeId) {
                        return 0.00;
                    }
                    return (float)$liquidations->where('type_id', $typeId)->sum('amount');
                };

                $ctsTruncated = $sumLiquidation(PayrollLiquidationBbss::TYPE_CTS_TRUNCADA);
                $gratification = $sumLiquidation(PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA);
                $extraordinaryBonus = $sumLiquidation(PayrollLiquidationBbss::TYPE_BONIFICACION_EXTRAORDINARIA);
                $vacationTruncated = $sumLiquidation(PayrollLiquidationBbss::TYPE_VACACIONES_TRUNCADAS);
                $aguinaldo = $sumLiquidation(PayrollLiquidationBbss::TYPE_AGUINALDO);
                $christmasGratification = $sumLiquidation(PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD);
                $christmasExtraordinaryBonus = $sumLiquidation(PayrollLiquidationBbss::TYPE_BONIF_EXTRAORD_NAVIDAD);

                // Fuentes de datos aún no identificadas: quedan en 0.00 (documentado en el plan).
                $oncosaludPlan = 0.00;
                $advancesLoans = 0.00;
                $otherDeductions = 0.00;
                $judicialDeductions = 0.00;
                $graceAmount = 0.00;
                $bonusReferral = 0.00;

                $totalDeductions = round(
                    $pensionDeductions['onp_deduction']
                    + $pensionDeductions['afp_total']
                    + $incomeTax5th
                    + $oncosaludPlan
                    + $advancesLoans
                    + $otherDeductions
                    + $judicialDeductions
                    + $bonusReferral
                    + $graceAmount,
                    2
                );

                $netPayPreliminary = round($totalIncome - $totalDeductions, 2);
                $netPayPlusAguinaldo = round(
                    $netPayPreliminary + $christmasGratification + $christmasExtraordinaryBonus + $aguinaldo,
                    2
                );

                // Crear el registro
                PayrollRegister::create([
                    'period_id' => $periodId,
                    'worker_id' => $worker->id,
                    'worker_name' => $workerName,
                    'worker_vat' => $workerVat,

                    // Datos del período
                    'cost_center' => $costCenter,
                    'status' => 'Activo',
                    'occupation' => $occupation,
                    'monthly_salary' => (float)($worker->sueldo ?? 0.00),
                    'afp_affiliation' => $pensionDeductions['affiliation'],
                    'has_family_allowance' => $hasFamilyAllowance,
                    'has_essalud_vida' => strtoupper($worker->essaludvida ?? '') === 'SI',

                    // Días (desde cálculos o valores por defecto)
                    'days_worked' => $calculation->days_worked ?? 30,
                    'days_vacation' => 0,
                    'days_medical_rest' => 0,
                    'days_absence' => 0,
                    'days_leave_unpaid' => 0,
                    'days_leave_paid' => 0,
                    'days_subsidy' => 0,
                    'days_not_worked' => 0,
                    'days_effective' => $calculation->days_worked ?? 30,
                    'normal_hours' => $calculation->total_normal_hours ?? 0,
                    'has_vacation' => false,
                    'has_subsidy' => false,
                    'calc_days_worked' => $calculation->days_worked ?? 30,
                    'calc_days_not_worked' => $calculation->days_absent ?? 0,

                    // Ingresos (desde cálculos o valores por defecto)
                    'basic_salary' => $basicSalary,
                    'family_allowance' => $familyAllowanceAmount,
                    'overtime_25' => $calculation->overtime_25 ?? 0.00,
                    'overtime_35' => $calculation->overtime_35 ?? 0.00,
                    'subsidy_disability' => 0.00, // TODO: implementar lógica
                    'work_conditions' => $workConditions,
                    'vacation_pay' => 0.00,
                    'production_bonus' => $productionBonus,
                    'holiday_days_pay' => $calculation->holiday_pay ?? 0.00,
                    'worked_rest_days_pay' => $calculation->compensatory_pay ?? 0.00,
                    'night_bonus' => $calculation->night_bonus ?? 0.00,
                    'commercial_bonus' => $commercialBonus,
                    'schooling_allowance' => 0.00,
                    'food_benefit' => 0.00,

                    // Calcular total de ingresos
                    'total_income' => $totalIncome,

                    // BB.SS truncos
                    'cts_truncated' => $ctsTruncated,
                    'gratification' => $gratification,
                    'extraordinary_bonus' => $extraordinaryBonus,
                    'vacation_truncated' => $vacationTruncated,

                    // Descuentos
                    'onp_deduction' => $pensionDeductions['onp_deduction'],
                    'bonus_referral' => $bonusReferral,
                    'afp_mandatory' => $pensionDeductions['afp_mandatory'],
                    'afp_insurance' => $pensionDeductions['afp_insurance'],
                    'afp_commission' => $pensionDeductions['afp_commission'],
                    'afp_total' => $pensionDeductions['afp_total'],
                    'income_tax_5th' => $incomeTax5th,
                    'oncosalud_plan' => $oncosaludPlan, // TODO: sin fuente de datos identificada
                    'advances_loans' => $advancesLoans, // TODO: sin fuente de datos identificada
                    'other_deductions' => $otherDeductions, // TODO: sin fuente de datos identificada
                    'judicial_deductions' => $judicialDeductions, // TODO: sin fuente de datos identificada
                    'grace_amount' => $graceAmount, // TODO: sin fuente de datos identificada
                    'total_deductions' => $totalDeductions,

                    // Netos
                    'net_pay_preliminary' => $netPayPreliminary,
                    'christmas_gratification' => $christmasGratification,
                    'christmas_extraordinary_bonus' => $christmasExtraordinaryBonus,
                    'aguinaldo' => $aguinaldo,
                    'net_pay_plus_aguinaldo' => $netPayPlusAguinaldo,

                    // Aportes empleador
                    'cts_employer' => 0.00, // TODO: Fase 3 (gratificación/CTS)
                    'essalud_employer' => $employerContributions['essalud'],
                    'sctr_total' => $employerContributions['sctr_total'],
                    'life_insurance' => $employerContributions['life_insurance'],
                    'sctr_health' => $employerContributions['sctr_health'],
                    'sctr_pension' => $employerContributions['sctr_pension'],
                    'employer_contributions_total' => $employerContributions['total'],

                    // Netos finales
                    'vacation_paid_preliminary' => 0.00, // TODO: sin fuente de datos identificada
                    'net_pay_final' => $netPayPlusAguinaldo,
                    'worker_deduction_total' => $totalDeductions,
                ]);

                $createdCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Registros de planilla generados exitosamente",
                'data' => [
                    'period_id' => $periodId,
                    'company_id' => $companyId,
                    'workers_processed' => $workers->count(),
                    'records_created' => $createdCount,
                    'records_skipped' => $skippedCount,
                    'calculation_pending' => $calculationPending,
                ]
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcular el total de ingresos
     *
     * @param array $incomes
     * @return float
     */
    private function calculateTotalIncome(array $incomes): float
    {
        return round(array_sum($incomes), 2);
    }

    /**
     * Calcular aportes del empleador: SCTR (salud + pensión), EsSalud y Vida Ley.
     *
     * Tasas configurables vía GeneralMaster (patrón ya usado en PayrollScheduleService):
     * - EsSalud: 9% sobre el total de ingresos, con piso RMV.
     * - SCTR (salud + pensión): 0.50% + 0.50% sobre el total de ingresos, solo si el
     *   trabajador está afiliado (rrhh_persona.estado_sctr = 'SI'). SCTR pensión tiene
     *   tope en la RMA (Remuneración Máxima Asegurable).
     * - Vida Ley: (sueldo básico x 3.12%) x (1 + IGV) / 12, prorrateado a cuota mensual
     *   (fórmula confirmada contra "CALCULO VIDA LEY TP - POR PERSONA POLIZA 2025-2026.xlsx").
     *
     * @param Worker $worker
     * @param float $basicSalary
     * @param float $totalIncome
     * @return array{essalud: float, sctr_health: float, sctr_pension: float, sctr_total: float, life_insurance: float, total: float}
     */
    private function calculateEmployerContributions(Worker $worker, float $basicSalary, float $totalIncome): array
    {
        $minimumWage = (float)(GeneralMaster::find(GeneralMaster::MINIMUM_WAGE_ID)->value ?? 1130);
        $essaludRate = (float)(GeneralMaster::find(GeneralMaster::ESSALUD_RATE_ID)->value ?? 0.09);
        $sctrHealthRate = (float)(GeneralMaster::find(GeneralMaster::SCTR_HEALTH_RATE_ID)->value ?? 0.005);
        $sctrPensionRate = (float)(GeneralMaster::find(GeneralMaster::SCTR_PENSION_RATE_ID)->value ?? 0.005);
        $insurableMaxRemuneration = (float)(GeneralMaster::find(GeneralMaster::INSURABLE_MAX_REMUNERATION_ID)->value ?? 12027.91);
        $lifeInsuranceRate = (float)(GeneralMaster::find(GeneralMaster::LIFE_INSURANCE_RATE_ID)->value ?? 0.0312);
        $igvRate = (float)(GeneralMaster::find(GeneralMaster::IGV_RATE_ID)->value ?? 0.18);

        // EsSalud: aplica a todos, con piso RMV.
        $essaludBase = max($totalIncome, $minimumWage);
        $essalud = round($essaludBase * $essaludRate, 2);

        // SCTR: solo trabajadores afiliados (rrhh_persona.estado_sctr = 'SI').
        $isSctrAffiliated = strtoupper($worker->estado_sctr ?? '') === 'SI';
        $sctrHealth = 0.0;
        $sctrPension = 0.0;
        if ($isSctrAffiliated) {
            $sctrHealth = round($totalIncome * $sctrHealthRate, 2);
            $sctrPensionBase = min($totalIncome, $insurableMaxRemuneration);
            $sctrPension = round($sctrPensionBase * $sctrPensionRate, 2);
        }
        $sctrTotal = round($sctrHealth + $sctrPension, 2);

        // Vida Ley: prima anual por persona sobre el básico, + IGV, prorrateada a 12 meses.
        $lifeInsuranceAnnualCost = $basicSalary * $lifeInsuranceRate;
        $lifeInsuranceAnnualWithIgv = $lifeInsuranceAnnualCost * (1 + $igvRate);
        $lifeInsurance = round($lifeInsuranceAnnualWithIgv / 12, 2);

        return [
            'essalud' => $essalud,
            'sctr_health' => $sctrHealth,
            'sctr_pension' => $sctrPension,
            'sctr_total' => $sctrTotal,
            'life_insurance' => $lifeInsurance,
            'total' => round($essalud + $sctrTotal + $lifeInsurance, 2),
        ];
    }

    /**
     * Calcular descuentos ONP/AFP del trabajador según su afiliación
     * (rrhh_persona.sis_pensiones_id -> rrhh_sist_pensiones).
     *
     * - ONP: aporte obligatorio 13% del total de ingresos, sin AFP.
     * - AFP: aporte obligatorio (~10%) + prima de seguro (~1.37%) + comisión variable
     *   (según AFP), todo sobre el total de ingresos. Si no tiene sistema de pensiones
     *   asignado, no se descuenta nada (se deja para que RRHH lo configure).
     *
     * @param Worker $worker
     * @param float $totalIncome
     * @return array{onp_deduction: float, afp_mandatory: float, afp_insurance: float, afp_commission: float, afp_total: float, affiliation: ?string}
     */
    private function calculatePensionDeductions(Worker $worker, float $totalIncome): array
    {
        $pension = $worker->pensionSystem;

        if (!$pension) {
            return [
                'onp_deduction' => 0.00,
                'afp_mandatory' => 0.00,
                'afp_insurance' => 0.00,
                'afp_commission' => 0.00,
                'afp_total' => 0.00,
                'affiliation' => null,
            ];
        }

        if ($pension->isOnp()) {
            return [
                'onp_deduction' => round($totalIncome * ((float)$pension->obl / 100), 2),
                'afp_mandatory' => 0.00,
                'afp_insurance' => 0.00,
                'afp_commission' => 0.00,
                'afp_total' => 0.00,
                'affiliation' => $pension->name,
            ];
        }

        $afpMandatory = round($totalIncome * ((float)$pension->obl / 100), 2);
        $afpInsurance = round($totalIncome * ((float)$pension->prima_seg / 100), 2);
        $afpCommission = round($totalIncome * ((float)$pension->com_var / 100), 2);

        return [
            'onp_deduction' => 0.00,
            'afp_mandatory' => $afpMandatory,
            'afp_insurance' => $afpInsurance,
            'afp_commission' => $afpCommission,
            'afp_total' => round($afpMandatory + $afpInsurance + $afpCommission, 2),
            'affiliation' => $pension->name,
        ];
    }

    /**
     * Calcular la retención de Renta de 5ta categoría: proyección anual simplificada.
     *
     * Proyecta el ingreso mensual x 12 (no incluye gratificaciones/bonos extraordinarios,
     * que suelen estar exonerados o requieren datos que hoy no se registran por
     * trabajador), resta 7 UIT y aplica los tramos progresivos vigentes (ley, no cambian
     * por empresa/periodo), prorrateando el impuesto anual resultante a una cuota mensual.
     * No cubre casos especiales (otro empleador, ingresos ya percibidos antes del alta
     * en el sistema, etc.) — aproximación razonable para la mayoría de casos.
     *
     * @param float $totalIncome
     * @return float
     */
    private function calculateIncomeTax5th(float $totalIncome): float
    {
        $uit = (float)(GeneralMaster::find(GeneralMaster::UIT_ID)->value ?? 5150);
        $deductionUit = (float)(GeneralMaster::find(GeneralMaster::INCOME_TAX_DEDUCTION_UIT_ID)->value ?? 7);

        $annualIncome = $totalIncome * 12;
        $taxableBase = max(0, $annualIncome - ($uit * $deductionUit));

        if ($taxableBase <= 0) {
            return 0.00;
        }

        // Tramos progresivos de renta de 5ta categoría (ley peruana vigente).
        $brackets = [
            ['limit' => 5 * $uit, 'rate' => 0.08],
            ['limit' => 20 * $uit, 'rate' => 0.14],
            ['limit' => 35 * $uit, 'rate' => 0.17],
            ['limit' => 45 * $uit, 'rate' => 0.20],
            ['limit' => INF, 'rate' => 0.30],
        ];

        $annualTax = 0.0;
        $previousLimit = 0.0;
        foreach ($brackets as $bracket) {
            if ($taxableBase <= $previousLimit) {
                break;
            }
            $taxableInBracket = min($taxableBase, $bracket['limit']) - $previousLimit;
            $annualTax += $taxableInBracket * $bracket['rate'];
            $previousLimit = $bracket['limit'];
        }

        return round($annualTax / 12, 2);
    }

    /**
     * Exportar registros de planilla por periodo a Excel
     *
     * @param int $periodId ID del periodo
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws Exception
     */
    public function exportByPeriod(int $periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);

        $registers = PayrollRegister::where('period_id', $periodId)
            ->orderBy('worker_name')
            ->get();

        if ($registers->isEmpty()) {
            throw new Exception('No se encontraron registros de planilla para este período');
        }

        $fileName = 'registro_planilla_' . $period->code . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new PayrollRegisterExport($registers, $period->code),
            $fileName
        );
    }
}
