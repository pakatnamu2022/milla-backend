<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLiquidationBbssResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLiquidationBbss;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLiquidationBbssService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLiquidationBbss::class,
            $request,
            PayrollLiquidationBbss::filters,
            PayrollLiquidationBbss::sorts,
            PayrollLiquidationBbssResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollLiquidationBbss::find($id);
        if (!$record) {
            throw new Exception('Liquidación BBSS no encontrada');
        }
        return $record;
    }

    public function show($id)
    {
        return new PayrollLiquidationBbssResource($this->find($id));
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollLiquidationBbss::create($data);
            DB::commit();
            return new PayrollLiquidationBbssResource($record);
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
            return new PayrollLiquidationBbssResource($record->fresh());
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
            return response()->json(['message' => 'Liquidación BBSS eliminada correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}