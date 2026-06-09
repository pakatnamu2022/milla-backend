<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollRegisterResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollRegister;
use Illuminate\Http\Request;

class PayrollRegisterService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollRegister::class,
            $request,
            PayrollRegister::filters,
            PayrollRegister::sorts,
            PayrollRegisterResource::class,
        );
    }
}
