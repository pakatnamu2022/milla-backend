<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollSubsidyResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollRegister;
use App\Models\gp\gestionhumana\payroll\PayrollSubsidy;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class PayrollSubsidyService extends BaseService
{
    public function list(Request $request)
    {
        $query = PayrollSubsidy::query()->with('worker')->orderByDesc('start_date');

        return $this->getFilteredResults(
            $query,
            $request,
            PayrollSubsidy::filters,
            PayrollSubsidy::sorts,
            PayrollSubsidyResource::class,
        );
    }

    public function show(int $id)
    {
        $subsidy = PayrollSubsidy::with('worker')->find($id);
        if (!$subsidy) {
            throw new Exception('El subsidio no existe');
        }

        return new PayrollSubsidyResource($subsidy);
    }

    public function store(array $data)
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $this->assertNoOverlap((int)$data['worker_id'], $start, $end);

        $days = $start->diffInDays($end) + 1;

        $amount = $data['amount'] ?? null;
        if ($amount === null) {
            $amount = $this->estimateAmount((int)$data['worker_id'], $start, $end)['amount'];
        }

        $subsidy = PayrollSubsidy::create([
            'worker_id' => $data['worker_id'],
            'type' => $data['type'] ?? PayrollSubsidy::TYPE_TEMPORARY_DISABILITY,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $days,
            'amount' => $amount,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $this->show($subsidy->id);
    }

    public function update(int $id, array $data)
    {
        $subsidy = PayrollSubsidy::find($id);
        if (!$subsidy) {
            throw new Exception('El subsidio no existe');
        }

        $start = Carbon::parse($data['start_date'] ?? $subsidy->start_date);
        $end = Carbon::parse($data['end_date'] ?? $subsidy->end_date);
        if ($end->lt($start)) {
            throw new Exception('La fecha de fin no puede ser anterior a la de inicio');
        }

        $this->assertNoOverlap($subsidy->worker_id, $start, $end, $subsidy->id);

        $subsidy->update(array_merge($data, [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $start->diffInDays($end) + 1,
        ]));

        return $this->show($subsidy->id);
    }

    public function destroy(int $id)
    {
        $subsidy = PayrollSubsidy::find($id);
        if (!$subsidy) {
            throw new Exception('El subsidio no existe');
        }

        $subsidy->delete();

        return true;
    }

    /**
     * Estima el monto del subsidio como en el Excel (hoja SUBSIDIO, D.S. 013-2019, art. 33):
     * total de remuneraciones de los 12 meses calendario anteriores al mes en que inicia la
     * contingencia / 360, por los días subsidiados, redondeado hacia abajo.
     *
     * Las remuneraciones salen de gh_payroll_register (total_income sin el subsidio de esos
     * meses). Si el trabajador tiene menos de 12 meses de historial en el sistema, se divide
     * entre 30 x meses con historial. Sin ningún historial no se puede estimar: hay que enviar
     * el monto que indica EsSalud.
     *
     * @return array{amount: float, daily_average: float, months_counted: int, total_remuneration: float, days: int}
     */
    public function estimateAmount(int $workerId, Carbon $start, Carbon $end): array
    {
        $firstMonth = $start->copy()->startOfMonth()->subMonths(12);
        $lastMonth = $start->copy()->startOfMonth()->subMonth();

        $rows = PayrollRegister::query()
            ->join('gh_payroll_periods', 'gh_payroll_register.period_id', '=', 'gh_payroll_periods.id')
            ->where('gh_payroll_register.worker_id', $workerId)
            ->whereRaw('(gh_payroll_periods.year * 12 + gh_payroll_periods.month) between ? and ?', [
                $firstMonth->year * 12 + $firstMonth->month,
                $lastMonth->year * 12 + $lastMonth->month,
            ])
            ->selectRaw('gh_payroll_periods.year as year, gh_payroll_periods.month as month, sum(gh_payroll_register.total_income - gh_payroll_register.subsidy_disability) as total')
            ->groupBy('gh_payroll_periods.year', 'gh_payroll_periods.month')
            ->get();

        if ($rows->isEmpty()) {
            throw new Exception('No hay planillas de los 12 meses anteriores para estimar el subsidio; ingresa el monto indicado por EsSalud');
        }

        $months = $rows->count();
        $total = (float)$rows->sum('total');
        $dailyAverage = $total / ($months * PayrollSubsidy::MONTH_DAYS);
        $days = $start->diffInDays($end) + 1;

        return [
            'amount' => floor($dailyAverage * $days),
            'daily_average' => round($dailyAverage, 4),
            'months_counted' => $months,
            'total_remuneration' => round($total, 2),
            'days' => $days,
        ];
    }

    public function estimate(array $data): array
    {
        return $this->estimateAmount(
            (int)$data['worker_id'],
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
        );
    }

    private function assertNoOverlap(int $workerId, Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        $overlaps = PayrollSubsidy::where('worker_id', $workerId)
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($overlaps) {
            throw new Exception('El trabajador ya tiene un subsidio que se cruza con esas fechas');
        }
    }
}
