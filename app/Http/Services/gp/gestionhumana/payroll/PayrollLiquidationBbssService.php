<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLiquidationBbssResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollExclusion;
use App\Models\gp\gestionhumana\payroll\PayrollLiquidationBbss;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use App\Models\gp\gestionsistema\Company;
use App\Models\GeneralMaster;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLiquidationBbssService extends BaseService implements BaseServiceInterface
{
    /**
     * Fallback si aún no se sembró GeneralMaster (code=FAMILY_ALLOWANCE) para el rango de
     * fechas consultado — no debería usarse en producción, ver GeneralMaster::valueAt().
     * Monto legal fijo de la asignación familiar (10% de la RMV vigente, S/1,130 → S/113).
     * Se paga completo a todo trabajador con hijos menores a cargo (rrhh_persona.asignacion =
     * 'SI'), sin prorrateo. No depende de la tabla gh_payroll_family_allowance: esa tabla es un
     * registro manual que en la práctica nunca se llena, así que dejó de usarse como fuente para
     * este monto (confirmado con el usuario 2026-09-01).
     *
     * El valor real vigente se resuelve por fecha vía GeneralMaster::valueAt('FAMILY_ALLOWANCE',
     * $referenceDate, self::FAMILY_ALLOWANCE_AMOUNT) — así, si la RMV sube y cambia el monto,
     * los periodos ya pagados con el monto anterior no se ven afectados al recalcularse
     * (confirmado con el usuario 2026-09-01).
     */
    private const FAMILY_ALLOWANCE_AMOUNT = 113.00;

    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLiquidationBbss::class,
            $request,
            PayrollLiquidationBbss::filters,
            PayrollLiquidationBbss::sorts,
            PayrollLiquidationBbssResource::class,
        );
    }

    /**
     * Orden canónico de los conceptos del catálogo LIQUIDATION_BBSS, ver
     * Database\Seeders\gp\gestionhumana\payroll\PayrollLiquidationBbssTypeSeeder.
     */
    private const CONCEPT_LABELS = [
        PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD => 'Gratificación',
        PayrollLiquidationBbss::TYPE_BONIF_EXTRAORD_NAVIDAD => 'Bonif. Extraord. 9%',
        PayrollLiquidationBbss::TYPE_CTS_SEMESTRAL => 'CTS Semestral',
        PayrollLiquidationBbss::TYPE_CTS_TRUNCADA => 'CTS Truncada',
        PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA => 'Gratif. Truncada',
        PayrollLiquidationBbss::TYPE_BONIFICACION_EXTRAORDINARIA => 'Bonif. Extraord.',
        PayrollLiquidationBbss::TYPE_VACACIONES_TRUNCADAS => 'Vacaciones Truncadas',
        PayrollLiquidationBbss::TYPE_AGUINALDO => 'Aguinaldo',
    ];

    /**
     * Igual que list(), pero pivoteado: una sola fila por trabajador+periodo con un valor por
     * cada concepto (tipo) que tenga registrado, en vez de una fila por cada tipo — evita que un
     * trabajador con gratificación + bono extraordinario (o CTS) en el mismo periodo aparezca
     * dos veces. Requiere period_id porque, a diferencia de list(), no pagina: junta todas las
     * filas del periodo en memoria antes de agrupar (mismo patrón que PayrollRegisterService).
     */
    public function listPivoted(Request $request): array
    {
        $periodId = (int) $request->query('period_id');
        if (!$periodId) {
            throw new Exception('Debe seleccionar un periodo.');
        }

        $search = trim((string) $request->query('search', ''));

        $rows = PayrollLiquidationBbss::with(['worker', 'type', 'period'])
            ->where('period_id', $periodId)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('worker', function ($q) use ($search) {
                    $q->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('vat', 'like', "%{$search}%");
                });
            })
            ->get();

        $grouped = [];
        $codesPresent = [];

        foreach ($rows as $row) {
            $workerId = $row->worker_id;
            $code = $row->type?->code;
            if (!$code) {
                continue;
            }

            if (!isset($grouped[$workerId])) {
                $grouped[$workerId] = [
                    'worker_id' => $workerId,
                    'worker' => $row->worker?->nombre_completo,
                    'worker_vat' => $row->worker?->vat,
                    'period_id' => $row->period_id,
                    'period' => $row->period?->name,
                    'amounts' => [],
                    'ids' => [],
                    'total' => 0,
                ];
            }

            $amount = (float) $row->amount;
            $grouped[$workerId]['amounts'][$code] = $amount;
            $grouped[$workerId]['ids'][$code] = $row->id;
            $grouped[$workerId]['total'] += $amount;
            $codesPresent[$code] = true;
        }

        $data = array_values($grouped);
        usort($data, fn($a, $b) => strcmp((string) $a['worker'], (string) $b['worker']));

        $columns = [];
        foreach (self::CONCEPT_LABELS as $code => $label) {
            if (isset($codesPresent[$code])) {
                $columns[] = ['code' => $code, 'label' => $label];
            }
        }

        return [
            'data' => $data,
            'columns' => $columns,
        ];
    }

    public function find($id)
    {
        $record = PayrollLiquidationBbss::find($id);
        if (!$record) {
            throw new Exception('Liquidación BBSS no encontrada');
        }
        return $record;
    }

    public function show($id)
    {
        return new PayrollLiquidationBbssResource($this->find($id));
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollLiquidationBbss::create($data);
            DB::commit();
            return new PayrollLiquidationBbssResource($record);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($data['id']);
            $record->update($data);
            DB::commit();
            return new PayrollLiquidationBbssResource($record->fresh());
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($id);
            $record->delete();
            DB::commit();
            return response()->json(['message' => 'Liquidación BBSS eliminada correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula automáticamente la gratificación (Fiestas Patrias / Navidad) + bonificación
     * extraordinaria (9%) de todos los trabajadores activos de la empresa del periodo, y los
     * guarda en gh_payroll_liquidation_bbss (mismos tipos que usa la carga manual/Excel), para
     * que PayrollRegisterService::generate() los recoja sin cambios adicionales.
     *
     * Solo aplica a periodos de julio (semestre ene-jun) o diciembre (semestre jul-dic), que
     * son los únicos meses donde la ley exige pagar gratificación completa a todo el personal
     * activo. Para otros casos (ceses/truncas a mitad de año) se sigue usando la carga manual.
     *
     * gratificación = sueldo_computable x (meses_completos_del_semestre / 6), tope 6 meses.
     * bono_extraordinario = gratificación x 9% (tasa fija EsSalud).
     *
     * Un mes cuenta como "completo" si el trabajador ya había ingresado (fecha_inicio) antes o
     * al inicio de ese mes calendario. No se descuentan licencias sin goce (LSGH) — simplificación
     * aceptada, ver plan.
     *
     * @param int $periodId
     * @return array
     * @throws Exception
     */
    public function calculateGratification(int $periodId): array
    {
        try {
            DB::beginTransaction();

            $period = PayrollPeriod::find($periodId);
            if (!$period) {
                throw new Exception('El período no existe');
            }

            $month = (int)$period->month;
            if (!in_array($month, [7, 12], true)) {
                throw new Exception('Solo se puede calcular gratificación para periodos de Julio (Fiestas Patrias) o Diciembre (Navidad). Para otros meses, cargue las BB.SS. truncas manualmente o vía Excel.');
            }

            $year = (int)$period->year;
            $isJuly = $month === 7;

            $semesterStart = Carbon::create($year, $isJuly ? 1 : 7, 1)->startOfDay();
            $semesterEnd = $isJuly
                ? Carbon::create($year, 6, 30)->endOfDay()
                : Carbon::create($year, 12, 31)->endOfDay();

            $gratificationCode = $isJuly
                ? PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA
                : PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD;
            $bonusCode = $isJuly
                ? PayrollLiquidationBbss::TYPE_BONIFICACION_EXTRAORDINARIA
                : PayrollLiquidationBbss::TYPE_BONIF_EXTRAORD_NAVIDAD;

            $typeIds = PayrollLiquidationBbss::typeIdsByCode();
            $gratificationTypeId = $typeIds[$gratificationCode] ?? null;
            $bonusTypeId = $typeIds[$bonusCode] ?? null;

            if (!$gratificationTypeId || !$bonusTypeId) {
                throw new Exception('Falta sembrar el catálogo de tipos de BB.SS. truncos (GpMasters type=LIQUIDATION_BBSS).');
            }

            $companyId = $period->company_id;

            $workers = Worker::whereHas('sede', function ($query) use ($companyId) {
                $query->where('empresa_id', $companyId);
            })->working()->get();

            $processed = 0;
            $skipped = [];

            foreach ($workers as $worker) {
                if (!$worker->fecha_inicio) {
                    $skipped[] = "{$worker->nombre_completo}: sin fecha de ingreso registrada";
                    continue;
                }

                $startDate = Carbon::parse($worker->fecha_inicio)->startOfDay();
                $effectiveStart = $startDate->greaterThan($semesterStart) ? $startDate : $semesterStart->copy();

                if ($effectiveStart->greaterThan($semesterEnd)) {
                    $skipped[] = "{$worker->nombre_completo}: ingresó después del semestre";
                    continue;
                }

                $months = $this->completedMonths($effectiveStart, $semesterStart, $semesterEnd);

                if ($months <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: no completó ningún mes del semestre";
                    continue;
                }

                $base = $this->computeWorkerBase($worker, $periodId, $companyId, $semesterEnd);

                if ($base['salary'] <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: sin sueldo registrado";
                    continue;
                }

                // Remuneración computable real: básico + asignación familiar + promedio de
                // variables de los últimos 6 meses (horas extra/bonif. nocturna/feriado/
                // descanso), no solo el sueldo básico — confirmado contra
                // GRATIFICACION DICIEMBRE 2025.xlsx.
                $computable = $base['computable'];

                $gratification = round($computable * $months / 6, 2);
                $bonus = round($gratification * 0.09, 2);

                PayrollLiquidationBbss::updateOrCreate(
                    ['worker_id' => $worker->id, 'period_id' => $periodId, 'type_id' => $gratificationTypeId],
                    ['amount' => $gratification, 'status' => 1]
                );
                PayrollLiquidationBbss::updateOrCreate(
                    ['worker_id' => $worker->id, 'period_id' => $periodId, 'type_id' => $bonusTypeId],
                    ['amount' => $bonus, 'status' => 1]
                );

                $processed++;
            }

            DB::commit();

            return [
                'success' => true,
                'period_id' => $periodId,
                'workers_processed' => $processed,
                'skipped' => $skipped,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cuenta cuántos meses calendario completos, dentro de [$semesterStart, $semesterEnd], el
     * trabajador ya había ingresado antes o al inicio de cada mes. Compartido por gratificación
     * y CTS (misma regla legal de "mes completo"). Tope de 6, aplicado por el caller.
     */
    private function completedMonths(Carbon $effectiveStart, Carbon $semesterStart, Carbon $semesterEnd): int
    {
        $months = 0;
        $cursor = $semesterStart->copy()->startOfMonth();
        $lastMonthCursor = $semesterEnd->copy()->startOfMonth();
        while ($cursor->lte($lastMonthCursor)) {
            if ($effectiveStart->lte($cursor)) {
                $months++;
            }
            $cursor->addMonthNoOverflow();
        }
        return min($months, 6);
    }

    /**
     * Sueldo básico + asignación familiar + promedio de variables (últimos 6 meses) de un
     * trabajador para un periodo dado. Base compartida por gratificación, CTS y las boletas.
     *
     * La asignación familiar se otorga automáticamente (monto fijo, FAMILY_ALLOWANCE_AMOUNT) a
     * todo trabajador con rrhh_persona.asignacion = 'SI', salvo que exista una exclusión
     * puntual para ese trabajador+periodo en gh_payroll_exclusions (concepto FAMILY_ALLOWANCE).
     * Se prefirió este modelo "opt-out" al opt-in manual anterior (gh_payroll_family_allowance)
     * porque esa tabla nunca se llenaba en la práctica.
     */
    private function computeWorkerBase(Worker $worker, int $periodId, int $companyId, Carbon $referenceDate): array
    {
        $salary = WorkerContract::salaryForWorkerAtDate($worker->id, $referenceDate->format('Y-m-d'))
            ?? (float)($worker->sueldo ?? 0);

        $familyAllowanceExcluded = PayrollExclusion::where('worker_id', $worker->id)
            ->where('period_id', $periodId)
            ->where('concept', PayrollExclusion::CONCEPT_FAMILY_ALLOWANCE)
            ->exists();

        $familyAllowance = ($worker->asignacion === 'SI' && !$familyAllowanceExcluded)
            ? (float)GeneralMaster::valueAt('FAMILY_ALLOWANCE', $referenceDate, self::FAMILY_ALLOWANCE_AMOUNT)
            : 0.0;

        $avgDetail = PayrollCalculation::calcularPromedioUltimos6Meses($periodId, $worker->id, $companyId);
        $avgVariable = (float)($avgDetail->total_avg ?? 0);

        return [
            'salary' => $salary,
            'family_allowance' => $familyAllowance,
            'avg_variable' => $avgVariable,
            'avg_breakdown' => [
                'overtime' => (float)($avgDetail->avg_overtime ?? 0),
                'holiday' => (float)($avgDetail->avg_holiday ?? 0),
                'compensatory' => (float)($avgDetail->avg_compensatory ?? 0),
                'night_bonus' => (float)($avgDetail->avg_night_bonus ?? 0),
                'bonus' => (float)($avgDetail->avg_bonus ?? 0),
                'months_counted' => (int)($avgDetail->months_counted ?? 0),
            ],
            'computable' => $salary + $familyAllowance + $avgVariable,
        ];
    }

    /**
     * Dado un periodo de CTS (mayo o noviembre), resuelve el periodo de la gratificación bruta
     * que le sirve de referencia para el 1/6: mayo → diciembre del año anterior,
     * noviembre → julio del mismo año. Devuelve null si ese periodo no existe todavía.
     */
    private function resolveCtsReferencePeriod(PayrollPeriod $period): ?PayrollPeriod
    {
        $month = (int)$period->month;
        $year = (int)$period->year;

        if ($month === 5) {
            $refYear = $year - 1;
            $refMonth = 12;
        } elseif ($month === 11) {
            $refYear = $year;
            $refMonth = 7;
        } else {
            return null;
        }

        return PayrollPeriod::where('company_id', $period->company_id)
            ->where('year', $refYear)
            ->where('month', $refMonth)
            ->first();
    }

    /**
     * Indica si ya se puede calcular la CTS del periodo dado: si es mayo/noviembre, si existe el
     * periodo de gratificación de referencia, y si esa gratificación ya fue calculada (tiene al
     * menos un registro guardado). Lo usa el frontend para deshabilitar el botón "Calcular CTS"
     * hasta que corresponda, en vez de solo rechazar el cálculo al hacer clic.
     */
    public function gratificationReadiness(int $periodId): array
    {
        $period = PayrollPeriod::find($periodId);
        if (!$period) {
            throw new Exception('El período no existe');
        }

        $month = (int)$period->month;
        if (!in_array($month, [5, 11], true)) {
            return [
                'ready' => false,
                'reference_period' => null,
                'message' => 'La CTS solo se calcula para periodos de Mayo o Noviembre.',
            ];
        }

        $referencePeriod = $this->resolveCtsReferencePeriod($period);
        if (!$referencePeriod) {
            $refLabel = $month === 5 ? 'Diciembre del año anterior' : 'Julio de este año';
            return [
                'ready' => false,
                'reference_period' => null,
                'message' => "Falta crear el periodo de {$refLabel} para poder calcular la CTS.",
            ];
        }

        $typeIds = PayrollLiquidationBbss::typeIdsByCode();
        $gratificationCode = $month === 5
            ? PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD
            : PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA;
        $gratificationTypeId = $typeIds[$gratificationCode] ?? null;

        $hasGratification = $gratificationTypeId && PayrollLiquidationBbss::where('period_id', $referencePeriod->id)
            ->where('type_id', $gratificationTypeId)
            ->exists();

        return [
            'ready' => (bool)$hasGratification,
            'reference_period' => [
                'id' => $referencePeriod->id,
                'code' => $referencePeriod->code,
                'name' => $referencePeriod->name,
            ],
            'message' => $hasGratification
                ? null
                : "Primero debe calcular la gratificación del periodo {$referencePeriod->name} antes de generar la CTS.",
        ];
    }

    /**
     * Calcula automáticamente la CTS semestral (depósito de Mayo cubre Nov-Abril, depósito de
     * Noviembre cubre Mayo-Oct) de todos los trabajadores activos de la empresa del periodo, y
     * los guarda en gh_payroll_liquidation_bbss con el tipo CTS_SEMESTRAL (distinto de
     * CTS_TRUNCADA, que es solo para la LBS al cese).
     *
     * Fórmula real (confirmada contra CALCULO CTS NOV 2025-ABRIL 2026 TP SAC.xls):
     *   base_computable = básico + asig.familiar + promedio_variable_6m + (1/6 × gratificación
     *     bruta del semestre de referencia)
     *   CTS = (base_computable/12) × meses_completos − (base_computable/360) × días_LSGH
     *
     * LSGH = días con gh_payroll_schedules.status=ABSENT (no registrado/no justificado, a
     * diferencia de SICK_LEAVE/PERMISSION/VACATION) dentro del semestre — confirmado con el
     * usuario. Los días sueltos por ingreso a mitad de semestre se simplifican a 0 (mismo criterio
     * ya aceptado para gratificación).
     *
     * @param int $periodId
     * @return array
     * @throws Exception
     */
    public function calculateCts(int $periodId): array
    {
        try {
            DB::beginTransaction();

            $period = PayrollPeriod::find($periodId);
            if (!$period) {
                throw new Exception('El período no existe');
            }

            $month = (int)$period->month;
            if (!in_array($month, [5, 11], true)) {
                throw new Exception('Solo se puede calcular CTS para periodos de Mayo o Noviembre. Para casos de cese, use la Liquidación de BB.SS. (CTS trunca) manual.');
            }

            $referencePeriod = $this->resolveCtsReferencePeriod($period);
            if (!$referencePeriod) {
                throw new Exception('Falta crear el periodo de gratificación de referencia para poder calcular la CTS.');
            }

            $typeIds = PayrollLiquidationBbss::typeIdsByCode();
            $gratificationCode = $month === 5
                ? PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD
                : PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA;
            $gratificationTypeId = $typeIds[$gratificationCode] ?? null;
            $ctsTypeId = $typeIds[PayrollLiquidationBbss::TYPE_CTS_SEMESTRAL] ?? null;

            if (!$gratificationTypeId || !$ctsTypeId) {
                throw new Exception('Falta sembrar el catálogo de tipos de BB.SS. truncos (GpMasters type=LIQUIDATION_BBSS).');
            }

            $grossGratifications = PayrollLiquidationBbss::where('period_id', $referencePeriod->id)
                ->where('type_id', $gratificationTypeId)
                ->pluck('amount', 'worker_id');

            if ($grossGratifications->isEmpty()) {
                throw new Exception("Primero debe calcular la gratificación del periodo {$referencePeriod->name} antes de generar la CTS.");
            }

            $year = (int)$period->year;
            $isMay = $month === 5;
            $semesterStart = $isMay
                ? Carbon::create($year - 1, 11, 1)->startOfDay()
                : Carbon::create($year, 5, 1)->startOfDay();
            $semesterEnd = $isMay
                ? Carbon::create($year, 4, 30)->endOfDay()
                : Carbon::create($year, 10, 31)->endOfDay();

            $companyId = $period->company_id;

            $workers = Worker::whereHas('sede', function ($query) use ($companyId) {
                $query->where('empresa_id', $companyId);
            })->working()->get();

            $processed = 0;
            $skipped = [];

            foreach ($workers as $worker) {
                if (!$worker->fecha_inicio) {
                    $skipped[] = "{$worker->nombre_completo}: sin fecha de ingreso registrada";
                    continue;
                }

                $startDate = Carbon::parse($worker->fecha_inicio)->startOfDay();
                $effectiveStart = $startDate->greaterThan($semesterStart) ? $startDate : $semesterStart->copy();

                if ($effectiveStart->greaterThan($semesterEnd)) {
                    $skipped[] = "{$worker->nombre_completo}: ingresó después del semestre";
                    continue;
                }

                $months = $this->completedMonths($effectiveStart, $semesterStart, $semesterEnd);
                if ($months <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: no completó ningún mes del semestre";
                    continue;
                }

                $base = $this->computeWorkerBase($worker, $periodId, $companyId, $semesterEnd);
                if ($base['salary'] <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: sin sueldo registrado";
                    continue;
                }

                $grossGratification = (float)($grossGratifications[$worker->id] ?? 0);
                $computable = $base['computable'] + ($grossGratification / 6);

                $lsghDays = PayrollSchedule::where('worker_id', $worker->id)
                    ->where('status', PayrollSchedule::STATUS_ABSENT)
                    ->whereBetween('work_date', [$semesterStart->format('Y-m-d'), $semesterEnd->format('Y-m-d')])
                    ->count();

                $cts = round(($computable / 12) * $months - ($computable / 360) * $lsghDays, 2);

                PayrollLiquidationBbss::updateOrCreate(
                    ['worker_id' => $worker->id, 'period_id' => $periodId, 'type_id' => $ctsTypeId],
                    ['amount' => $cts, 'status' => 1]
                );

                $processed++;
            }

            DB::commit();

            return [
                'success' => true,
                'period_id' => $periodId,
                'reference_period_id' => $referencePeriod->id,
                'workers_processed' => $processed,
                'skipped' => $skipped,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Genera la boleta PDF de CTS o gratificación de un trabajador para un periodo.
     * $type = 'cts' | 'gratificacion'.
     */
    public function payslip(int $periodId, int $workerId, string $type)
    {
        $data = $this->payslipData($periodId, $workerId, $type);

        $pdf = Pdf::loadView('reports.gp.gestionhumana.payroll.liquidation-bbss-payslip', $data);
        $pdf->setOptions([
            'defaultFont' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'dpi' => 96,
        ]);
        $pdf->setPaper('A4', 'portrait');

        $slug = $type === 'cts' ? 'cts' : 'gratificacion';
        $filename = "boleta-{$slug}-{$data['worker']->vat}-{$data['period']->code}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Arma el detalle de la boleta de CTS o gratificación de un trabajador, con las mismas
     * columnas del cálculo real (Excel): básico, asig. familiar, promedio variable, 1/6 de
     * gratificación (solo CTS), meses/días/LSGH, y monto final ya guardado en
     * gh_payroll_liquidation_bbss.
     *
     * @throws Exception
     */
    public function payslipData(int $periodId, int $workerId, string $type): array
    {
        if (!in_array($type, ['cts', 'gratificacion'], true)) {
            throw new Exception('Tipo de boleta inválido. Use "cts" o "gratificacion".');
        }

        $period = PayrollPeriod::with('company')->find($periodId);
        if (!$period) {
            throw new Exception('El período no existe');
        }

        $worker = Worker::find($workerId);
        if (!$worker) {
            throw new Exception('Trabajador no encontrado');
        }

        $month = (int)$period->month;
        $year = (int)$period->year;
        $typeIds = PayrollLiquidationBbss::typeIdsByCode();

        if ($type === 'cts') {
            if (!in_array($month, [5, 11], true)) {
                throw new Exception('El periodo seleccionado no es de CTS (Mayo/Noviembre).');
            }
            $isMay = $month === 5;
            $semesterStart = $isMay ? Carbon::create($year - 1, 11, 1) : Carbon::create($year, 5, 1);
            $semesterEnd = $isMay ? Carbon::create($year, 4, 30) : Carbon::create($year, 10, 31);
            $amountTypeId = $typeIds[PayrollLiquidationBbss::TYPE_CTS_SEMESTRAL] ?? null;
            $concept = 'CTS Semestral';
        } else {
            if (!in_array($month, [7, 12], true)) {
                throw new Exception('El periodo seleccionado no es de gratificación (Julio/Diciembre).');
            }
            $isJuly = $month === 7;
            $semesterStart = $isJuly ? Carbon::create($year, 1, 1) : Carbon::create($year, 7, 1);
            $semesterEnd = $isJuly ? Carbon::create($year, 6, 30) : Carbon::create($year, 12, 31);
            $amountTypeId = $typeIds[$isJuly ? PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA : PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD] ?? null;
            $bonusTypeId = $typeIds[$isJuly ? PayrollLiquidationBbss::TYPE_BONIFICACION_EXTRAORDINARIA : PayrollLiquidationBbss::TYPE_BONIF_EXTRAORD_NAVIDAD] ?? null;
            $concept = 'Gratificación';
        }

        $liquidation = $amountTypeId
            ? PayrollLiquidationBbss::where('period_id', $periodId)->where('worker_id', $workerId)->where('type_id', $amountTypeId)->first()
            : null;

        if (!$liquidation) {
            throw new Exception("No hay {$concept} calculada para este trabajador en este periodo. Calcúlela primero.");
        }

        $base = $this->computeWorkerBase($worker, $periodId, $period->company_id, $semesterEnd->copy()->endOfDay());
        $startDate = $worker->fecha_inicio ? Carbon::parse($worker->fecha_inicio)->startOfDay() : $semesterStart->copy();
        $effectiveStart = $startDate->greaterThan($semesterStart) ? $startDate : $semesterStart->copy();
        $months = $this->completedMonths($effectiveStart, $semesterStart->copy()->startOfDay(), $semesterEnd->copy()->endOfDay());

        $extra = [];
        if ($type === 'cts') {
            $grossGratification = 0;
            $refGratTypeId = null;
            $refPeriod = $this->resolveCtsReferencePeriod($period);
            if ($refPeriod) {
                $refGratTypeId = $typeIds[$month === 5 ? PayrollLiquidationBbss::TYPE_GRATIFICACION_NAVIDAD : PayrollLiquidationBbss::TYPE_GRATIFICACION_TRUNCADA] ?? null;
                if ($refGratTypeId) {
                    $grossGratification = (float)(PayrollLiquidationBbss::where('period_id', $refPeriod->id)
                        ->where('worker_id', $workerId)
                        ->where('type_id', $refGratTypeId)
                        ->value('amount') ?? 0);
                }
            }
            $lsghDays = PayrollSchedule::where('worker_id', $workerId)
                ->where('status', PayrollSchedule::STATUS_ABSENT)
                ->whereBetween('work_date', [$semesterStart->format('Y-m-d'), $semesterEnd->format('Y-m-d')])
                ->count();
            $extra = [
                'sixth_gratification' => round($grossGratification / 6, 2),
                'lsgh_days' => $lsghDays,
                'bank' => $worker->entidad_cts,
                'account' => $worker->cta_cts,
                'cci' => $worker->cuenta_interbancaria_cts,
            ];
        } else {
            $bonus = $bonusTypeId
                ? PayrollLiquidationBbss::where('period_id', $periodId)->where('worker_id', $workerId)->where('type_id', $bonusTypeId)->value('amount')
                : null;
            $extra = ['extraordinary_bonus' => (float)($bonus ?? 0)];
        }

        return [
            'type' => $type,
            'concept' => $concept,
            'period' => $period,
            'company' => $period->company,
            'company_logo' => $this->companyLogoBase64($period->company),
            'legal_representative' => $this->companyLegalRepresentative($period->company_id),
            'worker' => $worker,
            'semester_start' => $semesterStart,
            'semester_end' => $semesterEnd,
            'months' => $months,
            'base' => $base,
            'extra' => $extra,
            'amount' => (float)$liquidation->amount,
            'amount_words' => $this->numberToWords((float)$liquidation->amount),
        ];
    }

    /**
     * Representante legal de la empresa para la constancia de CTS ("debidamente representado
     * por..."). Se guarda como GeneralMaster (code = LEGAL_REPRESENTATIVE_COMPANY_{id}, value =
     * id del Worker que lo representa) porque general_masters no tiene columna company_id propia
     * y así cada empresa puede tener el suyo; si no está configurado, se omite en la boleta.
     */
    private function companyLegalRepresentative(int $companyId): ?Worker
    {
        $workerId = GeneralMaster::valueAt('LEGAL_REPRESENTATIVE_COMPANY_' . $companyId, now(), null);

        return $workerId ? Worker::find($workerId) : null;
    }

    /**
     * Monto en letras para la constancia de CTS ("SON: MIL CIENTO SETENTA Y OCHO CON 71/100"),
     * mismo patrón que ElectronicDocumentService::convertNumberToWords().
     */
    private function numberToWords(float $amount): string
    {
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $integerPart = floor($amount);
        $decimalPart = round(($amount - $integerPart) * 100);

        return strtoupper($formatter->format($integerPart)) . ' CON ' . str_pad((string)$decimalPart, 2, '0', STR_PAD_LEFT) . '/100';
    }

    /**
     * Logo de la empresa embebido en base64, usando el mismo patrón ya usado en
     * resources/views/exports/equipment-assignment.blade.php: archivos locales en
     * public/companies/*.png mapeados por config('companies.logos.<abrev>.large'),
     * NO el campo companies.logo de BD (ese apunta a un disco S3 sin acceso).
     * Si no hay match o el archivo no existe, retorna null y la boleta cae al
     * monograma de respaldo (ver vista) en vez de romper la generación del PDF.
     */
    private function companyLogoBase64(?Company $company): ?string
    {
        $abbr = strtolower($company->abbreviation ?? '');
        $path = public_path(config("companies.logos.{$abbr}.large", '/companies/gplargo.png'));

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        $mime = mime_content_type($path) ?: 'image/png';

        return "data:{$mime};base64," . base64_encode($contents);
    }
}