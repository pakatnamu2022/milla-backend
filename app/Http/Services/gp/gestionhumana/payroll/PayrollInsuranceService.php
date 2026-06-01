<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollInsuranceResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollInsurance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollInsuranceService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollInsurance::class,
            $request,
            PayrollInsurance::filters,
            PayrollInsurance::sorts,
            PayrollInsuranceResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollInsurance::find($id);
        if (!$record) {
            throw new Exception('Seguro no encontrado');
        }
        return $record;
    }

    public function show($id)
    {
        return new PayrollInsuranceResource($this->find($id));
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollInsurance::create($data);
            DB::commit();
            return new PayrollInsuranceResource($record);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($data['id']);
            $record->update($data);
            DB::commit();
            return new PayrollInsuranceResource($record->fresh());
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($id);
            $record->delete();
            DB::commit();
            return response()->json(['message' => 'Seguro eliminado correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}