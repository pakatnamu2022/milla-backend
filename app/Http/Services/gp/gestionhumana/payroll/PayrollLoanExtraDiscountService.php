<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanExtraDiscountResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLoanExtraDiscount;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLoanExtraDiscountService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLoanExtraDiscount::class,
            $request,
            PayrollLoanExtraDiscount::filters,
            PayrollLoanExtraDiscount::sorts,
            PayrollLoanExtraDiscountResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollLoanExtraDiscount::find($id);
        if (!$record) {
            throw new Exception('Descuento extra no encontrado');
        }
        return $record;
    }

    public function show($id)
    {
        return new PayrollLoanExtraDiscountResource($this->find($id));
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollLoanExtraDiscount::create($data);
            DB::commit();
            return new PayrollLoanExtraDiscountResource($record);
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
            return new PayrollLoanExtraDiscountResource($record->fresh());
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
            return response()->json(['message' => 'Descuento extra eliminado correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}