<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexCompanyRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Services\gp\gestionsistema\CompanyService;
use App\Models\gp\gestionsistema\Company;

class CompanyController extends Controller
{

    protected CompanyService $service;

    public function __construct(CompanyService $service)
    {
        $this->service = $service;
    }


    public function index(IndexCompanyRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreCompanyRequest $request)
    {
        //
    }

    public function show(Company $company)
    {
        //
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        //
    }

    public function destroy(Company $company)
    {
        //
    }
}
