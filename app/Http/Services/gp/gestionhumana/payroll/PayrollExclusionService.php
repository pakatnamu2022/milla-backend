<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollExclusionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollExclusion;
use Illuminate\Http\Request;
use Exception;

class PayrollExclusionService extends BaseService
{
    public function list(Request $request)
    {
        $query = PayrollExclusion::query()
            ->join('gh_payroll_periods', 'gh_payroll_exclusions.period_id', '=', 'gh_payroll_periods.id')
            ->select('gh_payroll_exclusions.*')
            ->orderBy('gh_payroll_periods.year', 'desc')
            ->orderBy('gh_payroll_periods.month', 'desc');

        return $this->getFilteredResults(
            $query,
            $request,
            PayrollExclusion::filters,
            PayrollExclusion::sorts,
            PayrollExclusionResource::class,
        );
    }

    public function store(array $data)
    {
        $data['created_by'] = auth()->id();

        $exclusion = PayrollExclusion::create($data);

        return new PayrollExclusionResource($exclusion);
    }

    public function destroy(int $id)
    {
        $exclusion = PayrollExclusion::find($id);
        if (!$exclusion) {
            throw new Exception('La exclusión no existe');
        }

        $exclusion->delete();

        return true;
    }
}
