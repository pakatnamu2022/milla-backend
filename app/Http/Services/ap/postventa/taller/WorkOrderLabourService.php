<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderLabourResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkOrderLabourService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      WorkOrderLabour::class,
      $request,
      WorkOrderLabour::filters,
      WorkOrderLabour::sorts,
      WorkOrderLabourResource::class,
    );
  }

  public function find($id)
  {
    $workOrderLabour = WorkOrderLabour::with(['worker', 'workOrder'])->where('id', $id)->first();
    if (!$workOrderLabour) {
      throw new Exception('Mano de obra no encontrada');
    }
    return $workOrderLabour;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Convertir time_spent a decimal para el cálculo si es necesario
      $timeSpentDecimal = is_numeric($data['time_spent'])
        ? floatval($data['time_spent'])
        : $this->timeToDecimal($data['time_spent']);

      // obtenemos la OT y validamos que exista
      $workOrder = ApWorkOrder::find($data['work_order_id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede agregar mano de obra a una orden de trabajo cerrada');
      }

      if ($workOrder->order_quotation_id) {
        $orderQuotation = $workOrder->orderQuotation;
        if ($orderQuotation->currency_id === $workOrder->currency_id) { // MISMA MONEDA
          $factor = 1;
        } else {
          if ($workOrder->currency_id === TypeCurrency::PEN_ID) { //SI LA OT ESTA EN SOLES SE ENTIENDE QUE LA COTIZACION ESTA EN DOLARES
            $factor = $orderQuotation->exchange_rate;
          } else if ($workOrder->currency_id === TypeCurrency::USD_ID) { //SI LA OT ESTA EN DOLARES SE ENTIENDE QUE LA COTIZACION ESTA EN SOLES
            $factor = 1;
          } else {
            throw new Exception('Moneda no soportada para la cotización de la orden de trabajo');
          }
        }
      } else {
        $factor = 1;
      }

      // Calcular el costo total automáticamente
      if (isset($data['time_spent']) && isset($data['hourly_rate'])) {
        $data['total_cost'] = $timeSpentDecimal * floatval($data['hourly_rate']) * $factor;
      }
      $data['hourly_rate'] = floatval($data['hourly_rate']) * $factor;
      $data['time_spent'] = $timeSpentDecimal;

      $workOrderLabour = WorkOrderLabour::create($data);
      return new WorkOrderLabourResource($workOrderLabour->load(['worker', 'workOrder']));
    });
  }

  /**
   * Convertir formato TIME (HH:MM:SS o HH:MM) a decimal
   */
  private function timeToDecimal(string $time): float
  {
    $parts = explode(':', $time);
    $hours = intval($parts[0]);
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;

    return $hours + ($minutes / 60);
  }

  public function show($id)
  {
    return new WorkOrderLabourResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrderLabour = $this->find($data['id']);

      // Calcular el costo total automáticamente si se actualizan time_spent u hourly_rate
      if (isset($data['time_spent']) || isset($data['hourly_rate'])) {
        // Obtener time_spent en formato decimal
        if (isset($data['time_spent'])) {
          $timeSpent = is_numeric($data['time_spent'])
            ? floatval($data['time_spent'])
            : $this->timeToDecimal($data['time_spent']);
        } else {
          $timeSpent = $workOrderLabour->time_spent_decimal;
        }

        $hourlyRate = $data['hourly_rate'] ?? $workOrderLabour->hourly_rate;
        $data['total_cost'] = $timeSpent * floatval($hourlyRate);
      }

      $workOrderLabour->update($data);
      return new WorkOrderLabourResource($workOrderLabour->load(['worker', 'workOrder']));
    });
  }

  public function destroy($id)
  {
    $workOrderLabour = $this->find($id);
    DB::transaction(function () use ($workOrderLabour) {
      $workOrderLabour->delete();
    });
    return response()->json(['message' => 'Mano de obra eliminada correctamente']);
  }
}
