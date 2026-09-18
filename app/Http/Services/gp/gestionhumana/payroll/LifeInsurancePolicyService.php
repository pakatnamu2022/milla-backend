<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\LifeInsurancePolicyResource;
use App\Http\Services\BaseService;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\payroll\LifeInsurancePolicy;
use App\Models\gp\gestionhumana\payroll\LifeInsurancePolicyWorker;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LifeInsurancePolicyService extends BaseService
{
    // Respaldo si general_masters no tiene el valor (mismo monto histórico que FAMILY_ALLOWANCE).
    private const FAMILY_ALLOWANCE_FALLBACK = 113.00;

    public function list(Request $request)
    {
        $query = LifeInsurancePolicy::query()
            ->withCount('workers')
            ->orderByDesc('start_date');

        return $this->getFilteredResults(
            $query,
            $request,
            LifeInsurancePolicy::filters,
            LifeInsurancePolicy::sorts,
            LifeInsurancePolicyResource::class,
        );
    }

    public function show(int $id)
    {
        $policy = LifeInsurancePolicy::with(['workers.worker'])->withCount('workers')->find($id);
        if (!$policy) {
            throw new Exception('La póliza no existe');
        }

        return new LifeInsurancePolicyResource($policy);
    }

    /**
     * Emite la póliza y calcula, una sola vez, el monto mensual de cada trabajador activo de la
     * empresa con su sueldo vigente al inicio de la póliza + asignación familiar (réplica del Excel).
     *
     * @return array{policy: LifeInsurancePolicyResource, skipped: array}
     */
    public function store(array $data): array
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $overlaps = LifeInsurancePolicy::where('company_id', $data['company_id'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
        if ($overlaps) {
            throw new Exception('Ya existe una póliza de esta empresa que se cruza con esas fechas de vigencia');
        }

        $monthlyRate = $data['monthly_rate']
            ?? round((float)(GeneralMaster::find(GeneralMaster::LIFE_INSURANCE_RATE_ID)->value ?? 0.0312) / 12, 6);
        $igvRate = $data['igv_rate'] ?? (float)(GeneralMaster::find(GeneralMaster::IGV_RATE_ID)->value ?? 0.18);
        $days = $data['days'] ?? ($start->diffInDays($end) + 1);
        $exclusion = (float)($data['exclusion'] ?? 0);

        $familyAllowance = (float)GeneralMaster::valueAt('FAMILY_ALLOWANCE', $start, self::FAMILY_ALLOWANCE_FALLBACK);

        // Solo quien ya había ingresado al iniciar la póliza; los ingresos posteriores se
        // agregan como inclusión (addWorker).
        $workers = Worker::working()
            ->whereHas('sede', fn ($q) => $q->where('empresa_id', $data['company_id']))
            ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $start))
            ->get();

        $insured = [];
        $skipped = [];
        foreach ($workers as $worker) {
            // Sueldo vigente al iniciar la póliza (historial de contratos + aumentos), no el
            // sueldo actual: una póliza emitida en octubre asegura el sueldo de octubre.
            $salary = (float)(WorkerContract::salaryForWorkerAtDate($worker->id, $start->format('Y-m-d')) ?? $worker->sueldo ?? 0);
            if ($salary <= 0) {
                $skipped[] = ['worker_id' => $worker->id, 'nombre_completo' => $worker->nombre_completo, 'reason' => 'Sin sueldo registrado'];
                continue;
            }
            $insured[$worker->id] = $salary + ($worker->asignacion === 'SI' ? $familyAllowance : 0.0);
        }

        if (empty($insured)) {
            throw new Exception('La empresa no tiene trabajadores activos con sueldo para asegurar');
        }

        $totalInsured = round(array_sum($insured) - $exclusion, 2);
        if ($totalInsured <= 0) {
            throw new Exception('El total de sueldos asegurados (menos la exclusión) debe ser mayor a cero');
        }

        $netPremium = $data['net_premium']
            ?? $totalInsured * $monthlyRate * 12 * ($days / 365);

        $policy = DB::transaction(function () use ($data, $start, $end, $days, $monthlyRate, $igvRate, $exclusion, $totalInsured, $netPremium, $insured) {
            $policy = LifeInsurancePolicy::create([
                'company_id' => $data['company_id'],
                'insurer' => $data['insurer'] ?? null,
                'policy_number' => $data['policy_number'] ?? null,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'days' => $days,
                'monthly_rate' => $monthlyRate,
                'igv_rate' => $igvRate,
                'exclusion' => $exclusion,
                'total_insured_salary' => $totalInsured,
                'net_premium' => $netPremium,
                'created_by' => auth()->id(),
            ]);

            foreach ($insured as $workerId => $insuredSalary) {
                LifeInsurancePolicyWorker::create([
                    'policy_id' => $policy->id,
                    'worker_id' => $workerId,
                    'insured_salary' => $insuredSalary,
                ] + $policy->monthlyAmountFor($insuredSalary));
            }

            return $policy;
        });

        return [
            'policy' => $this->show($policy->id),
            'skipped' => $skipped,
        ];
    }

    /**
     * Inclusión de un trabajador que ingresó después de emitida la póliza: mismo cálculo con la
     * prima efectiva de la póliza (prima neta / total asegurado), sin alterar los totales ni los
     * montos de los demás trabajadores.
     */
    public function addWorker(int $policyId, array $data)
    {
        $policy = LifeInsurancePolicy::find($policyId);
        if (!$policy) {
            throw new Exception('La póliza no existe');
        }

        $worker = Worker::working()
            ->whereHas('sede', fn ($q) => $q->where('empresa_id', $policy->company_id))
            ->find($data['worker_id']);
        if (!$worker) {
            throw new Exception('El trabajador no existe, no está activo o no pertenece a la empresa de la póliza');
        }

        if (LifeInsurancePolicyWorker::where('policy_id', $policy->id)->where('worker_id', $worker->id)->exists()) {
            throw new Exception('El trabajador ya está incluido en esta póliza');
        }

        $insuredSalary = $data['insured_salary'] ?? null;
        if ($insuredSalary === null) {
            $salary = (float)(WorkerContract::salaryForWorkerAtDate($worker->id, now()->format('Y-m-d')) ?? $worker->sueldo ?? 0);
            if ($salary <= 0) {
                throw new Exception('El trabajador no tiene sueldo registrado; indica el sueldo asegurado');
            }
            $familyAllowance = (float)GeneralMaster::valueAt('FAMILY_ALLOWANCE', now(), self::FAMILY_ALLOWANCE_FALLBACK);
            $insuredSalary = $salary + ($worker->asignacion === 'SI' ? $familyAllowance : 0.0);
        }

        LifeInsurancePolicyWorker::create([
            'policy_id' => $policy->id,
            'worker_id' => $worker->id,
            'insured_salary' => $insuredSalary,
        ] + $policy->monthlyAmountFor((float)$insuredSalary));

        return $this->show($policy->id);
    }
}
