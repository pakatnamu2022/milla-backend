<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollRegisterExport;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollRegisterResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\LifeInsurancePolicyWorker;
use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollExclusion;
use App\Models\gp\gestionhumana\payroll\PayrollInsurance;
use App\Models\gp\gestionhumana\payroll\PayrollLiquidationBbss;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollRegister;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\payroll\PayrollSubsidy;
use App\Models\gp\gestionhumana\payroll\PayrollWorkingCondition;
use App\Models\gp\gestionhumana\payroll\SctrRate;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use App\Models\GeneralMaster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollRegisterService extends BaseService
{
    /**
     * Monto fijo de asignación familiar (10% RMV) usado como default si GeneralMaster
     * no tiene un valor vigente para 'FAMILY_ALLOWANCE' en la fecha de referencia — mismo
     * valor/patrón que PayrollLiquidationBbssService::FAMILY_ALLOWANCE_AMOUNT.
     */
    private const FAMILY_ALLOWANCE_AMOUNT = 113.00;

    // Mes comercial de 30 días (mismo criterio del Excel de planilla y del resto del módulo).
    private const MONTH_DAYS = 30;

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
     * @param bool $force Si true, borra y recrea los registros ya existentes del período
     *                    (regenerar) en vez de saltarlos — usar cuando cambiaron datos
     *                    fuente (sueldo histórico, vacaciones, condiciones, etc.) después
     *                    de una generación previa.
     * @return array
     */
    public function generate(int $companyId, int $periodId, bool $force = false)
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

            // Regenerar: borra los registros ya existentes del período para que el bucle
            // los recree con los datos/cálculos actuales (sueldo histórico, vacaciones,
            // condiciones de trabajo, etc.), en vez de saltarlos como si nada cambió.
            if ($force) {
                PayrollRegister::where('period_id', $periodId)->delete();
            }

            // Centro de costo: rrhh_persona.centro_costo_id → rrhh_centro_costo.name
            $costCenters = DB::table('rrhh_centro_costo')->pluck('name', 'id');

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

                // Asignación familiar: automática (monto fijo) para todo trabajador con
                // rrhh_persona.asignacion = 'SI', salvo exclusión puntual en
                // gh_payroll_exclusions — mismo criterio que PayrollLiquidationBbssService,
                // en vez del registro manual en gh_payroll_family_allowance (esa tabla nunca
                // se llenaba en la práctica, dejando a trabajadores con hijos sin el monto).
                $familyAllowanceExcluded = PayrollExclusion::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->where('concept', PayrollExclusion::CONCEPT_FAMILY_ALLOWANCE)
                    ->exists();

                $hasFamilyAllowance = $worker->asignacion === 'SI' && !$familyAllowanceExcluded;
                $familyAllowanceAmount = $hasFamilyAllowance
                    ? (float)GeneralMaster::valueAt('FAMILY_ALLOWANCE', $period->end_date, self::FAMILY_ALLOWANCE_AMOUNT)
                    : 0.0;

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
                $cuspp = trim((string)($worker->cuspp ?? '')) ?: null;
                $costCenter = $costCenters[$worker->centro_costo_id] ?? '';

                // Sueldo mensual vigente EN EL PERÍODO, resuelto contra el historial de
                // contratos (rrhh_contrato) — nunca rrhh_persona.sueldo (sueldo ACTUAL),
                // que para periodos pasados no refleja el contrato que regía entonces.
                $monthlySalary = $calculation->salary
                    ?? WorkerContract::salaryForWorkerAtDate($worker->id, $period->end_date)
                    ?? (float)($worker->sueldo ?? 0.00);

                // Condiciones de trabajo (mismo patrón que asignación familiar/bonos)
                $workConditions = (float)(PayrollWorkingCondition::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->value('amount') ?? 0.00);

                // Días de vacación / descanso médico / faltas / licencias: se cuentan
                // directo desde gh_payroll_schedules (marcación diaria por código, "DM",
                // "VC", "F", "LSGH", "LCGH") — cubre tanto marcación manual como la
                // auto-completada desde vacaciones/ausentismo aprobado (ver
                // PayrollScheduleService::resolveApprovedLeaveCode).
                $scheduleCodeCounts = PayrollSchedule::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->selectRaw('code, count(*) as total')
                    ->groupBy('code')
                    ->pluck('total', 'code');

                $daysVacation = (int)($calculation->days_vacation ?? $scheduleCodeCounts['VC'] ?? 0);
                $daysMedicalRest = (int)($scheduleCodeCounts['DM'] ?? 0);
                $daysAbsence = (int)($scheduleCodeCounts['F'] ?? 0);
                $daysLeaveUnpaid = (int)($scheduleCodeCounts['LSGH'] ?? 0);
                $daysLeavePaid = (int)($scheduleCodeCounts['LCGH'] ?? 0);
                $daysNotWorked = (int)($scheduleCodeCounts['NL'] ?? 0);

                if ($calculation) {
                    // Hay PayrollCalculation: confiar en sus valores, ya prorateados
                    // correctamente por PayrollSummaryService (sueldo/30*días trabajados,
                    // donde días trabajados ya incluye DM/LCGH como día pagado).
                    $daysWorked = (int)$calculation->days_worked;
                    $basicSalary = (float)$calculation->basic_salary;
                } else {
                    // Sin PayrollCalculation (no se corrió "Calcular asistencias" para
                    // este trabajador/período — gh_payroll_schedules quedó vacío): en vez
                    // de asumir 30 días trabajados a ciegas, si hay vacaciones/ausentismo
                    // MÉDICO aprobado para fechas dentro del período (rrhh_vacaciones /
                    // rrhh_ausentismo_laboral) igual se descuentan del sueldo básico —
                    // esta es la fuente de datos real que el usuario reportó que faltaba.
                    $approvedLeave = $this->resolveApprovedLeaveDaysInPeriod($worker->id, $period);
                    $daysVacation = max($daysVacation, $approvedLeave['vacation']);
                    $daysMedicalRest = max($daysMedicalRest, $approvedLeave['medical_rest']);
                    $daysLeavePaid = max($daysLeavePaid, $approvedLeave['leave_paid']);
                    $daysLeaveUnpaid = max($daysLeaveUnpaid, $approvedLeave['leave_unpaid']);

                    // Convención del resto del módulo: mes de 30 días. DM/LCGH se pagan
                    // como si se hubiera trabajado (igual que en PayrollScheduleService),
                    // solo vacaciones (pagadas aparte) y licencia sin goce descuentan.
                    $daysWorked = max(0, 30 - $daysVacation - $daysLeaveUnpaid);

                    // Sueldo básico GANADO en el período: prorrateado por días trabajados
                    // (sueldo/30*días), no el sueldo mensual pleno.
                    $basicSalary = round($monthlySalary / 30 * $daysWorked, 2);
                }

                // Subsidio EsSalud (incapacidad temporal / maternidad): días y monto del
                // periodo según los certificados registrados (gh_payroll_subsidies). Esos
                // días no los paga el empleador, así que no cuentan como días trabajados.
                $subsidy = PayrollSubsidy::allocationForPeriod($worker->id, $period->start_date, $period->end_date);
                $daysSubsidy = $subsidy['days'];
                $subsidyAmount = $subsidy['amount'];
                if ($daysSubsidy > 0) {
                    $maxDaysWorked = max(0, self::MONTH_DAYS - $daysVacation - $daysSubsidy);
                    if ($daysWorked > $maxDaysWorked) {
                        $daysWorked = $maxDaysWorked;
                        $basicSalary = round($monthlySalary / self::MONTH_DAYS * $daysWorked, 2);
                    }
                }

                // Remuneración vacacional: (sueldo + promedio de variables de los últimos 6
                // meses) / 30 = valor del día vacacional, por los días de vacaciones. La
                // asignación familiar NO va aquí: se paga completa (100% del 10% RMV) en su
                // propia columna, tenga o no vacaciones, para no contarla dos veces.
                $vacationAverage = 0.0;
                $vacationDailyValue = 0.0;
                $vacationPay = 0.00;
                if ($daysVacation > 0) {
                    $vacationAverage = round((float)PayrollCalculation::calcularPromedioUltimos6Meses(
                        $periodId,
                        $worker->id,
                        $companyId
                    )->total_avg, 2);
                    $vacationDailyValue = ($monthlySalary + $vacationAverage) / self::MONTH_DAYS;
                    $vacationPay = round($daysVacation * $vacationDailyValue, 2);
                }

                // 100% del monto vigente, sin prorrateo por días trabajados, vacaciones ni subsidio.
                $familyAllowancePaid = round($familyAllowanceAmount, 2);

                $totalIncome = $this->calculateTotalIncome([
                    'basic_salary' => $basicSalary,
                    'family_allowance' => $familyAllowancePaid,
                    'overtime_25' => $calculation->overtime_25 ?? 0.00,
                    'overtime_35' => $calculation->overtime_35 ?? 0.00,
                    'subsidy_disability' => $subsidyAmount,
                    'holiday_pay' => $calculation->holiday_pay ?? 0.00,
                    'worked_rest_days_pay' => $calculation->compensatory_pay ?? 0.00,
                    'night_bonus' => $calculation->night_bonus ?? 0.00,
                    'production_bonus' => $productionBonus,
                    'commercial_bonus' => $commercialBonus,
                    'work_conditions' => $workConditions,
                    'vacation_pay' => $vacationPay,
                ]);

                // Aportes del empleador: SCTR (salud+pensión), EsSalud, Vida Ley
                $employerContributions = $this->calculateEmployerContributions($worker, $companyId, $basicSalary, $familyAllowanceAmount, $totalIncome, $subsidyAmount, $period->end_date);

                // Descuentos ONP/AFP (según rrhh_persona.sis_pensiones_id -> rrhh_sist_pensiones)
                $pensionDeductions = $this->calculatePensionDeductions($worker, $totalIncome, $subsidyAmount);

                // Renta de 5ta categoría: proyección anual simplificada (ingreso mensual x 12,
                // menos 7 UIT, tramos progresivos 8/14/17/20/30%, prorrateado a cuota mensual).
                $incomeTax5th = $this->calculateIncomeTax5th($totalIncome, $period->end_date);

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

                // Seguros (Fesalud/Oncosalud): registrados uno a uno o importados en
                // gh_payroll_insurances, se suman por trabajador/período.
                $oncosaludPlan = (float)(PayrollInsurance::where('worker_id', $worker->id)
                    ->where('period_id', $periodId)
                    ->sum('rate_with_tax'));

                // Fuentes de datos aún no identificadas: quedan en 0.00 (documentado en el plan).
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
                // La gratificación (Fiestas Patrias o Navidad) y su bonificación extraordinaria
                // no llevan descuentos (están inafectas), así que se suman completas al neto,
                // igual que el aguinaldo.
                $netPayPlusAguinaldo = round(
                    $netPayPreliminary
                    + $gratification + $extraordinaryBonus
                    + $christmasGratification + $christmasExtraordinaryBonus
                    + $aguinaldo,
                    2
                );

                // Crear el registro
                PayrollRegister::create([
                    'period_id' => $periodId,
                    'worker_id' => $worker->id,
                    'worker_name' => $workerName,
                    'worker_vat' => $workerVat,

                    // Datos del período
                    'cuspp' => $cuspp,
                    'cost_center' => $costCenter,
                    'status' => 'Activo',
                    'occupation' => $occupation,
                    'monthly_salary' => $monthlySalary,
                    'afp_affiliation' => $pensionDeductions['affiliation'],
                    'has_family_allowance' => $hasFamilyAllowance,
                    'has_essalud_vida' => strtoupper($worker->essaludvida ?? '') === 'SI',

                    // Días (desde cálculos, o desde vacaciones/ausentismo aprobado si no
                    // hay cálculo de asistencias corrido para este trabajador/período)
                    'days_worked' => $daysWorked,
                    'days_vacation' => $daysVacation,
                    'days_medical_rest' => $daysMedicalRest,
                    'days_absence' => $daysAbsence,
                    'days_leave_unpaid' => $daysLeaveUnpaid,
                    'days_leave_paid' => $daysLeavePaid,
                    'days_subsidy' => $daysSubsidy,
                    'days_not_worked' => $daysNotWorked,
                    // Días efectivos = días laborados (30 − vacaciones − subsidio −
                    // licencia sin goce): las vacaciones NO cuentan como día efectivo.
                    'days_effective' => $daysWorked,
                    'normal_hours' => $calculation->total_normal_hours ?? 0,
                    'has_vacation' => $daysVacation > 0,
                    'has_subsidy' => $daysSubsidy > 0,
                    'calc_days_worked' => $daysWorked,
                    'calc_days_not_worked' => $calculation->days_absent ?? 0,

                    // Ingresos (desde cálculos o valores por defecto)
                    'basic_salary' => $basicSalary,
                    'family_allowance' => $familyAllowancePaid,
                    'overtime_25' => $calculation->overtime_25 ?? 0.00,
                    'overtime_35' => $calculation->overtime_35 ?? 0.00,
                    'subsidy_disability' => $subsidyAmount,
                    'work_conditions' => $workConditions,
                    'vacation_pay' => $vacationPay,
                    'vacation_average' => $vacationAverage,
                    'vacation_daily_value' => round($vacationDailyValue, 4),
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
                    'sctr_rate_id' => $employerContributions['sctr_rate_id'],
                    'life_insurance_policy_worker_id' => $employerContributions['life_insurance_policy_worker_id'],
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
     * Cuenta días de vacación/descanso médico/licencia aprobados dentro del período,
     * leyendo directo de rrhh_vacaciones y rrhh_ausentismo_laboral — se usa solo como
     * respaldo cuando el trabajador no tiene PayrollCalculation (no se corrió "Calcular
     * asistencias" ese período), que era el caso reportado: un trabajador con vacaciones
     * y descanso médico aprobados por RRHH pero con gh_payroll_schedules vacío, cuyo
     * registro de planilla caía a 30 días trabajados por defecto ignorando ambos.
     *
     * Convención de status_deleted: en ambas tablas status_deleted=1 = registro vigente
     * (como el resto del proyecto y como AttendanceSyncService); status_deleted=0 son
     * solicitudes eliminadas o versiones reemplazadas de una solicitud editada.
     *
     * @param int $workerId
     * @param PayrollPeriod $period
     * @return array{vacation: int, medical_rest: int, leave_paid: int, leave_unpaid: int}
     */
    private function resolveApprovedLeaveDaysInPeriod(int $workerId, PayrollPeriod $period): array
    {
        $startDate = $period->start_date;
        $endDate = $period->end_date;

        $vacations = DB::table('rrhh_vacaciones')
            ->where('empleado_id', $workerId)
            ->where('status_id', 19) // APROBADO (config_status)
            ->where('status_deleted', 1) // vigente (0 = eliminada/reemplazada)
            ->where('fecha_inicio', '<=', $endDate)
            ->where('fecha_fin', '>=', $startDate)
            ->get(['fecha_inicio', 'fecha_fin']);

        $absences = DB::table('rrhh_ausentismo_laboral')
            ->where('empleado_id', $workerId)
            ->where('status_deleted', 1) // no eliminado (convención del proyecto)
            ->where('fecha_inicial', '<=', $endDate)
            ->where('fecha_fin', '>=', $startDate)
            ->get(['fecha_inicial', 'fecha_fin', 'id_tipo_descanso']);

        // Mapa fecha => código, recorriendo día por día para no contar dos veces si
        // hubiera solicitudes solapadas (mismo criterio que
        // PayrollScheduleService::resolveApprovedLeaveCode / MEDICAL_REST_TIPO_DESCANSO_IDS).
        $codeByDate = [];

        foreach ($vacations as $vacation) {
            $this->markDateRange($codeByDate, $vacation->fecha_inicio, $vacation->fecha_fin, $startDate, $endDate, 'VC');
        }

        foreach ($absences as $absence) {
            $code = in_array((int)$absence->id_tipo_descanso, [2, 19], true)
                ? 'DM'
                : ($absence->id_tipo_descanso == 16 ? 'LSGH' : 'LCGH');
            $this->markDateRange($codeByDate, $absence->fecha_inicial, $absence->fecha_fin, $startDate, $endDate, $code, overwrite: false);
        }

        $counts = array_count_values($codeByDate);

        return [
            'vacation' => $counts['VC'] ?? 0,
            'medical_rest' => $counts['DM'] ?? 0,
            'leave_paid' => $counts['LCGH'] ?? 0,
            'leave_unpaid' => $counts['LSGH'] ?? 0,
        ];
    }

    /**
     * Marca en $codeByDate cada fecha (Y-m-d) del rango [$from, $to] recortado a
     * [$periodStart, $periodEnd] con el código dado. Si $overwrite es false, no pisa
     * una fecha ya marcada (usado para que vacaciones tenga prioridad sobre ausentismo
     * si por algún motivo se solaparan).
     */
    private function markDateRange(array &$codeByDate, $from, $to, $periodStart, $periodEnd, string $code, bool $overwrite = true): void
    {
        $cursor = \Carbon\Carbon::parse($from)->max(\Carbon\Carbon::parse($periodStart));
        $end = \Carbon\Carbon::parse($to)->min(\Carbon\Carbon::parse($periodEnd));

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            if ($overwrite || !isset($codeByDate[$key])) {
                $codeByDate[$key] = $code;
            }
            $cursor->addDay();
        }
    }

    /**
     * Calcular aportes del empleador: SCTR (salud + pensión), EsSalud y Vida Ley.
     *
     * Tasas configurables vía GeneralMaster (patrón ya usado en PayrollScheduleService):
     * - EsSalud: 9% sobre el total de ingresos MENOS el subsidio (el subsidio lo paga EsSalud,
     *   no está afecto), con piso RMV cuando el total de ingresos no supera la RMV (igual que
     *   la columna ESSALUD de la planilla Excel).
     * - SCTR (salud + pensión): (total de ingresos - subsidio) x tasa, con la tasa de la
     *   empresa vigente a la fecha (gh_sctr_rates), solo si el trabajador está afiliado
     *   (rrhh_persona.estado_sctr = 'SI'). La base es el total de
     *   ingresos del periodo (antes de descuentos; incluye vacaciones, horas extra, bonos,
     *   asignación familiar), sin el subsidio, igual que EsSalud. SCTR pensión tiene tope en la RMA (Remuneración
     *   Máxima Asegurable).
     * - Vida Ley: NO se calcula aquí. El monto mensual se calculó una sola vez al emitir la
     *   póliza de la empresa (gh_life_insurance_policies) y aquí solo se lee el del
     *   trabajador en la póliza vigente a la fecha; si no está incluido, queda en 0.
     *
     * @param Worker $worker
     * @param int $companyId
     * @param float $basicSalary Básico del register (prorrateado).
     * @param float $familyAllowance
     * @param float $totalIncome Total de ingresos real del periodo (incluye el subsidio) — base de EsSalud.
     * @param float $subsidyAmount Subsidio EsSalud del periodo, que se resta de la base de EsSalud.
     * @param string|null $referenceDate Fecha (fin del periodo) para resolver las tasas/RMV/póliza
     *        vigentes en ese momento, no las de hoy — ver GeneralMaster::valueAt().
     * @return array{essalud: float, sctr_health: float, sctr_pension: float, sctr_total: float, sctr_rate_id: ?int, life_insurance: float, life_insurance_policy_worker_id: ?int, total: float}
     */
    private function calculateEmployerContributions(Worker $worker, int $companyId, float $basicSalary, float $familyAllowance, float $totalIncome, float $subsidyAmount = 0.0, ?string $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now()->format('Y-m-d');

        $minimumWage = (float)(GeneralMaster::valueAt('SALARIO_MINIMO', $referenceDate, 1130));
        $essaludRate = (float)(GeneralMaster::find(GeneralMaster::ESSALUD_RATE_ID)->value ?? 0.09);

        // Tasa SCTR por empresa y vigencia; si la empresa no tiene ninguna cargada, cae al
        // valor global de general_masters (comportamiento anterior) sin FK.
        $sctrRate = SctrRate::resolve($companyId, $referenceDate);
        $sctrHealthRate = (float)($sctrRate->health_rate
            ?? GeneralMaster::find(GeneralMaster::SCTR_HEALTH_RATE_ID)->value ?? 0.005);
        $sctrPensionRate = (float)($sctrRate->pension_rate
            ?? GeneralMaster::find(GeneralMaster::SCTR_PENSION_RATE_ID)->value ?? 0.005);
        $insurableMaxRemuneration = (float)(GeneralMaster::find(GeneralMaster::INSURABLE_MAX_REMUNERATION_ID)->value ?? 12027.91);

        // EsSalud: aplica a todos. Sobre (ingresos - subsidio); si los ingresos no superan la
        // RMV, se paga sobre la RMV.
        $essaludBase = $totalIncome > $minimumWage ? ($totalIncome - $subsidyAmount) : $minimumWage;
        $essalud = round($essaludBase * $essaludRate, 2);

        // SCTR: solo trabajadores afiliados (rrhh_persona.estado_sctr = 'SI').
        $isSctrAffiliated = strtoupper($worker->estado_sctr ?? '') === 'SI';
        $sctrHealth = 0.0;
        $sctrPension = 0.0;
        if ($isSctrAffiliated) {
            $sctrBase = $totalIncome - $subsidyAmount;
            $sctrHealth = round($sctrBase * $sctrHealthRate, 2);
            $sctrPensionBase = min($sctrBase, $insurableMaxRemuneration);
            $sctrPension = round($sctrPensionBase * $sctrPensionRate, 2);
        }
        $sctrTotal = round($sctrHealth + $sctrPension, 2);

        // Vida Ley: monto mensual ya calculado en la póliza vigente (0 si no está incluido).
        $policyWorker = LifeInsurancePolicyWorker::resolve($worker->id, $companyId, $referenceDate);
        $lifeInsurance = $policyWorker ? round((float)$policyWorker->monthly_amount, 2) : 0.0;

        return [
            'essalud' => $essalud,
            'sctr_health' => $sctrHealth,
            'sctr_pension' => $sctrPension,
            'sctr_total' => $sctrTotal,
            'sctr_rate_id' => $isSctrAffiliated ? $sctrRate?->id : null,
            'life_insurance' => $lifeInsurance,
            'life_insurance_policy_worker_id' => $policyWorker?->id,
            'total' => round($essalud + $sctrTotal + $lifeInsurance, 2),
        ];
    }

    /**
     * Calcular descuentos ONP/AFP del trabajador según su afiliación
     * (rrhh_persona.sis_pensiones_id -> rrhh_sist_pensiones).
     *
     * - ONP: aporte obligatorio 13% del total de ingresos MENOS el subsidio, sin AFP (si el
     *   trabajador es AFP, el campo ONP queda en 0).
     * - AFP: aporte obligatorio (~10%) + prima de seguro (~1.37%) + comisión variable
     *   (según AFP), todo sobre el total de ingresos. Si no tiene sistema de pensiones
     *   asignado, no se descuenta nada (se deja para que RRHH lo configure).
     *
     * @param Worker $worker
     * @param float $totalIncome
     * @param float $subsidyAmount Subsidio EsSalud del periodo (no afecto a ONP).
     * @return array{onp_deduction: float, afp_mandatory: float, afp_insurance: float, afp_commission: float, afp_total: float, affiliation: ?string}
     */
    private function calculatePensionDeductions(Worker $worker, float $totalIncome, float $subsidyAmount = 0.0): array
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
                'onp_deduction' => round(($totalIncome - $subsidyAmount) * ((float)$pension->obl / 100), 2),
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
     * @param string|null $referenceDate Fecha (fin del periodo) para resolver la UIT vigente en
     *        ese momento (cambia cada año) — ver GeneralMaster::valueAt().
     * @return float
     */
    private function calculateIncomeTax5th(float $totalIncome, ?string $referenceDate = null): float
    {
        $referenceDate = $referenceDate ?? now()->format('Y-m-d');

        $uit = (float)(GeneralMaster::valueAt('UIT', $referenceDate, 5150));
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
