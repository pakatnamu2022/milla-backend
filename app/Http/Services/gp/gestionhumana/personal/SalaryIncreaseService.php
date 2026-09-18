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
     * Registra un aumento de sueldo y actualiza rrhh_persona.sueldo (única vía prevista para
     * cambiar el sueldo de un trabajador con contrato INDETERMINADO). Los trabajadores con
     * contrato a plazo fijo cambian de sueldo generando un contrato nuevo, no por aquí.
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $worker = Worker::working()->find($data['worker_id']);
            if (!$worker) {
                throw new Exception('El trabajador no existe o no está activo');
            }

            $contract = WorkerContract::latestContract($worker->id);
            if (!$contract || !WorkerContract::isIndeterminado($contract)) {
                throw new Exception('Solo se registran aumentos a trabajadores con contrato INDETERMINADO; para contratos a plazo fijo el sueldo cambia con un contrato nuevo');
            }

            $effective = Carbon::parse($data['effective_date'])->startOfDay();
            $contractStart = Carbon::parse($contract->fecha_inicio_contrato)->startOfDay();
            if ($effective->lt($contractStart)) {
                throw new Exception('La fecha efectiva no puede ser anterior al inicio del contrato indeterminado (' . $contractStart->format('d/m/Y') . ')');
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
            if (abs($new - $previous) < 0.005) {
                throw new Exception('El sueldo nuevo es igual al sueldo anterior (' . number_format($previous, 2) . ')');
            }

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
