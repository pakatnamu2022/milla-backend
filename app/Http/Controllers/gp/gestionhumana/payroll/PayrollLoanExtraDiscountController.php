<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollLoanExtraDiscountRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollLoanExtraDiscountRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollLoanExtraDiscountRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollLoanExtraDiscountService;
use App\Models\GeneralMaster;
use Exception;

class PayrollLoanExtraDiscountController extends Controller
{
    protected PayrollLoanExtraDiscountService $service;

    public function __construct(PayrollLoanExtraDiscountService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollLoanExtraDiscountRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollLoanExtraDiscountRequest $request)
    {
        try {
            $data = $request->validated();

            $generalMaster = GeneralMaster::findOrFail($data['concept_type_id']);
            $data['concept_type'] = $generalMaster->description;
            $data['month_number'] = $generalMaster->value;

            return $this->success($this->service->store($data));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show(int $id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(UpdatePayrollLoanExtraDiscountRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;

            if (isset($data['concept_type_id'])) {
                $generalMaster = GeneralMaster::findOrFail($data['concept_type_id']);
                $data['concept_type'] = $generalMaster->description;
                $data['month_number'] = $generalMaster->value;
            }

            return $this->success($this->service->update($data));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            return $this->service->destroy($id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}