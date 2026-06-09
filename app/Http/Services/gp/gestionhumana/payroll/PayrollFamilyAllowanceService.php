<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollFamilyAllowanceResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollFamilyAllowance;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollFamilyAllowanceService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollFamilyAllowance::class,
            $request,
            PayrollFamilyAllowance::filters,
            PayrollFamilyAllowance::sorts,
            PayrollFamilyAllowanceResource::class,
        );
    }

    public function storeOrUpdate(array $data)
    {
        try {
            DB::beginTransaction();

            // Obtener información del worker
            $worker = Worker::find($data['worker_id']);
            if (!$worker) {
                throw new Exception('Trabajador no encontrado');
            }

            // Agregar num_doc y full_name desde el Worker
            $data['num_doc'] = $worker->vat;
            $data['full_name'] = $worker->nombre_completo;

            // Buscar si ya existe un registro con el mismo worker_id y period_id
            $familyAllowance = PayrollFamilyAllowance::where('worker_id', $data['worker_id'])
                ->where('period_id', $data['period_id'])
                ->first();

            if ($familyAllowance) {
                // Actualizar registro existente
                $familyAllowance->update($data);
            } else {
                // Crear nuevo registro
                $familyAllowance = PayrollFamilyAllowance::create($data);
            }

            DB::commit();
            return new PayrollFamilyAllowanceResource($familyAllowance);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}