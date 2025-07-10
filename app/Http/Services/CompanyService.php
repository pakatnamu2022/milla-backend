<?php

namespace App\Http\Services;

use App\Http\Resources\EquipmentResource;
use App\Http\Resources\CompanyResource;
use App\Models\Equipment;
use App\Models\Company;
use Exception;
use Illuminate\Http\Request;

class CompanyService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Company::class,
            $request,
            Company::filters,
            Company::sorts,
            CompanyResource::class,
        );
    }

    public function store($data)
    {
        $sede = Company::create($data);
        return new CompanyResource(Company::find($sede->id));
    }

    public function find($id)
    {
        $sede = Company::find($id);
        if (!$sede) {
            throw new Exception('Company no encontrada');
        }
        return new CompanyResource($sede);
    }

    public function update($data)
    {
        $equipment = Company::find($data['id']);
        if (!$equipment) {
            throw new Exception('Company no encontrada');
        }
        $equipment->update($data);
        return new EquipmentResource($equipment);
    }
}
