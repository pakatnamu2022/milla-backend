<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\SctrRateResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\SctrRate;
use Illuminate\Http\Request;

class SctrRateService extends BaseService
{
    public function list(Request $request)
    {
        $query = SctrRate::query()
            ->orderBy('company_id')
            ->orderByDesc('effective_from');

        return $this->getFilteredResults(
            $query,
            $request,
            SctrRate::filters,
            SctrRate::sorts,
            SctrRateResource::class,
        );
    }

    public function store(array $data)
    {
        $data['created_by'] = auth()->id();

        return new SctrRateResource(SctrRate::createAndCloseCurrent($data));
    }
}
