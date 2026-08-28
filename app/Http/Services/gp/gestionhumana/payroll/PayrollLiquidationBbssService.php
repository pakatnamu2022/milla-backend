<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLiquidationBbssResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLiquidationBbss;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLiquidationBbssService extends BaseService implements BaseServiceInterface
{
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

                $months = 0;
                $cursor = $semesterStart->copy()->startOfMonth();
                $lastMonthCursor = $semesterEnd->copy()->startOfMonth();
                while ($cursor->lte($lastMonthCursor)) {
                    if ($effectiveStart->lte($cursor)) {
                        $months++;
                    }
                    $cursor->addMonthNoOverflow();
                }
                $months = min($months, 6);

                if ($months <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: no completó ningún mes del semestre";
                    continue;
                }

                $salary = WorkerContract::salaryForWorkerAtDate($worker->id, $semesterEnd->format('Y-m-d'))
                    ?? (float)($worker->sueldo ?? 0);

                if ($salary <= 0) {
                    $skipped[] = "{$worker->nombre_completo}: sin sueldo registrado";
                    continue;
                }

                $gratification = round($salary * $months / 6, 2);
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
}