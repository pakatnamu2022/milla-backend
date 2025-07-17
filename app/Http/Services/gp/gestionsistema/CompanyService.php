<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\CompanyResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Company;
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

    public function find($id)
    {
        $view = Company::where('id', $id)
            ->where('status_deleted', 1)->first();
        if (!$view) {
            throw new Exception('Empresa no encontrada');
        }
        return $view;
    }

    public function store($data)
    {
        $company = Company::create($data);
        return new CompanyResource($company);
    }

    public function show($id)
    {
        return new CompanyResource($this->find($id));
    }

    public function update($data)
    {
        $company = $this->find($data['id']);
        $company->update($data);
        return new CompanyResource($company);
    }
}
