<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollRegisterExport;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollRegisterResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollFamilyAllowance;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollRegister;
use App\Models\gp\gestionhumana\personal\Worker;
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
                ->with(['position', 'sede'])
                ->get();

            if ($workers->isEmpty()) {
                throw new Exception('No se encontraron trabajadores activos para esta empresa');
            }

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
                    'monthly_salary' => 0.00, // TODO: obtener de contrato o tabla de sueldos
                    'afp_affiliation' => null, // TODO: obtener de datos del trabajador
                    'has_family_allowance' => $hasFamilyAllowance,
                    'has_essalud_vida' => false, // TODO: obtener de datos del trabajador

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
                    'basic_salary' => $calculation->basic_salary ?? 0.00,
                    'family_allowance' => $familyAllowanceAmount,
                    'overtime_25' => $calculation->overtime_25 ?? 0.00,
                    'overtime_35' => $calculation->overtime_35 ?? 0.00,
                    'subsidy_disability' => 0.00, // TODO: implementar lógica
                    'work_conditions' => 0.00, // TODO: obtener de tabla de condiciones
                    'vacation_pay' => 0.00,
                    'production_bonus' => $productionBonus,
                    'holiday_days_pay' => $calculation->holiday_pay ?? 0.00,
                    'worked_rest_days_pay' => $calculation->compensatory_pay ?? 0.00,
                    'night_bonus' => $calculation->night_bonus ?? 0.00,
                    'commercial_bonus' => $commercialBonus,
                    'schooling_allowance' => 0.00,
                    'food_benefit' => 0.00,

                    // Calcular total de ingresos
                    'total_income' => $this->calculateTotalIncome([
                        'basic_salary' => $calculation->basic_salary ?? 0.00,
                        'family_allowance' => $familyAllowanceAmount,
                        'overtime_25' => $calculation->overtime_25 ?? 0.00,
                        'overtime_35' => $calculation->overtime_35 ?? 0.00,
                        'holiday_pay' => $calculation->holiday_pay ?? 0.00,
                        'worked_rest_days_pay' => $calculation->compensatory_pay ?? 0.00,
                        'night_bonus' => $calculation->night_bonus ?? 0.00,
                        'production_bonus' => $productionBonus,
                        'commercial_bonus' => $commercialBonus,
                    ]),

                    // BB.SS truncos
                    'cts_truncated' => 0.00,
                    'gratification' => 0.00,
                    'extraordinary_bonus' => 0.00,
                    'vacation_truncated' => 0.00,

                    // Descuentos (valores por defecto - TODO: implementar cálculos)
                    'onp_deduction' => 0.00,
                    'bonus_referral' => 0.00,
                    'afp_mandatory' => 0.00,
                    'afp_insurance' => 0.00,
                    'afp_commission' => 0.00,
                    'afp_total' => 0.00,
                    'income_tax_5th' => 0.00,
                    'oncosalud_plan' => 0.00,
                    'advances_loans' => 0.00,
                    'other_deductions' => 0.00,
                    'judicial_deductions' => 0.00,
                    'grace_amount' => 0.00,
                    'total_deductions' => 0.00,

                    // Netos
                    'net_pay_preliminary' => $calculation->net_salary ?? 0.00,
                    'christmas_gratification' => 0.00,
                    'christmas_extraordinary_bonus' => 0.00,
                    'aguinaldo' => 0.00,
                    'net_pay_plus_aguinaldo' => $calculation->net_salary ?? 0.00,

                    // Aportes empleador (valores por defecto - TODO: implementar cálculos)
                    'cts_employer' => 0.00,
                    'essalud_employer' => 0.00,
                    'sctr_total' => 0.00,
                    'life_insurance' => 0.00,
                    'sctr_health' => 0.00,
                    'sctr_pension' => 0.00,
                    'employer_contributions_total' => 0.00,

                    // Netos finales
                    'vacation_paid_preliminary' => 0.00,
                    'net_pay_final' => $calculation->net_salary ?? 0.00,
                    'worker_deduction_total' => 0.00,
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
