<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollFoodCardResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollFoodCard;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollFoodCardService extends BaseService
{
  public function list(Request $request)
  {
    $query = PayrollFoodCard::query()
      ->join('gh_payroll_periods', 'gh_payroll_food_card.period_id', '=', 'gh_payroll_periods.id')
      ->select('gh_payroll_food_card.*')
      ->orderBy('gh_payroll_periods.year', 'desc')
      ->orderBy('gh_payroll_periods.month', 'desc');

    return $this->getFilteredResults(
      $query,
      $request,
      PayrollFoodCard::filters,
      PayrollFoodCard::sorts,
      PayrollFoodCardResource::class,
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
      $foodCard = PayrollFoodCard::where('worker_id', $data['worker_id'])
        ->where('period_id', $data['period_id'])
        ->first();

      if ($foodCard) {
        // Actualizar registro existente
        $foodCard->update($data);
      } else {
        // Crear nuevo registro
        $foodCard = PayrollFoodCard::create($data);
      }

      DB::commit();
      return new PayrollFoodCardResource($foodCard);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
