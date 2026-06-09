<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ApplyPaymentPayrollLoanRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollLoanRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollLoanRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollLoanRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollLoanService;
use App\Models\GeneralMaster;
use Exception;

class PayrollLoanController extends Controller
{
    protected PayrollLoanService $service;

    public function __construct(PayrollLoanService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollLoanRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollLoanRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
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

    public function update(UpdatePayrollLoanRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
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

    public function applyPayment(ApplyPaymentPayrollLoanRequest $request, int $id)
    {
        try {
            $data = $request->validated();

            if (isset($data['concept_type_id'])) {
                $master = GeneralMaster::findOrFail($data['concept_type_id']);
                $data['concept_type'] = $master->description;
                unset($data['concept_type_id']);
            }

            return $this->success($this->service->applyPayment($id, $data));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function regenerateInstallments(int $id)
    {
        try {
            return $this->success($this->service->regenerateInstallments($id));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}