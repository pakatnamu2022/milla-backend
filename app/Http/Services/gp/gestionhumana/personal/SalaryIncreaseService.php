<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\SalaryIncreaseResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\SalaryIncrease;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryIncreaseService extends BaseService
{
    public function list(Request $request)
    {
        $query = SalaryIncrease::query()
            ->with('worker')
            ->orderByDesc('effective_date')
            ->orderByDesc('id');

        return $this->getFilteredResults(
            $query,
            $request,
            SalaryIncrease::filters,
            SalaryIncrease::sorts,
            SalaryIncreaseResource::class,
        );
    }

    /**
     * Registra un aumento de sueldo y actualiza rrhh_persona.sueldo. Aplica a cualquier tipo de
     * contrato: un trabajador a plazo fijo puede recibir una mejora de remuneración a mitad de
     * contrato (por adenda o decisión del empleador) sin esperar a la renovación; el contrato
     * siguiente normalmente ya sale con el sueldo mejorado, pero eso no impide registrar el
     * aumento antes. La fecha efectiva se valida contra el contrato que estaba vigente en esa
     * fecha (no necesariamente el último contrato del trabajador).
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $worker = Worker::working()->find($data['worker_id']);
            if (!$worker) {
                throw new Exception('El trabajador no existe o no está activo');
            }

            $effective = Carbon::parse($data['effective_date'])->startOfDay();

            $contract = WorkerContract::resolveContractAtDate($worker->id, $effective->format('Y-m-d'));
            if (!$contract) {
                throw new Exception('El trabajador no tiene un contrato vigente en la fecha efectiva indicada');
            }

            $contractStart = Carbon::parse($contract->fecha_inicio_contrato)->startOfDay();
            if ($effective->lt($contractStart)) {
                throw new Exception('La fecha efectiva no puede ser anterior al inicio del contrato vigente en esa fecha (' . $contractStart->format('d/m/Y') . ')');
            }

            if ($contract->fecha_fin_contrato) {
                $contractEnd = Carbon::parse($contract->fecha_fin_contrato)->startOfDay();
                if ($effective->gt($contractEnd)) {
                    throw new Exception('La fecha efectiva no puede ser posterior al fin del contrato vigente en esa fecha (' . $contractEnd->format('d/m/Y') . ')');
                }
            }

            $last = SalaryIncrease::where('worker_id', $worker->id)
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->first();
            if ($last && $effective->lt($last->effective_date)) {
                throw new Exception('La fecha efectiva no puede ser anterior al último aumento registrado (' . $last->effective_date->format('d/m/Y') . ')');
            }

            $previous = (float)($data['previous_salary'] ?? ($last?->new_salary ?? $contract->sueldo));
            $new = (float)$data['new_salary'];

            $increase = SalaryIncrease::create([
                'worker_id' => $worker->id,
                'previous_salary' => $previous,
                'new_salary' => $new,
                'effective_date' => $effective->format('Y-m-d'),
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $worker->update(['sueldo' => $new]);

            return new SalaryIncreaseResource($increase->load('worker'));
        });
    }
}
