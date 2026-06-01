<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLoan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLoanService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLoan::class,
            $request,
            PayrollLoan::filters,
            PayrollLoan::sorts,
            PayrollLoanResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollLoan::find($id);
        if (!$record) {
            throw new Exception('Préstamo no encontrado');
        }
        return $record;
    }

    public function show($id)
    {
        $record = $this->find($id);
        $record->load('extraDiscounts');
        return new PayrollLoanResource($record);
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollLoan::create($data);
            DB::commit();
            return new PayrollLoanResource($record);
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
            return new PayrollLoanResource($record->fresh());
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
            return response()->json(['message' => 'Préstamo eliminado correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}