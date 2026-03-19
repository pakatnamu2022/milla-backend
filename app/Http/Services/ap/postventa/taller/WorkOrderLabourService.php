<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderLabourResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
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

//      if ($workOrder->vehicleInspection === null) {
//        throw new Exception('No se puede agregar mano de obra a una orden de trabajo sin inspección de vehículo');
//      }

      // Validar que no existan avances de factura
      if ($workOrder->advancesWorkOrder()->exists()) {
        throw new Exception('No se puede agregar mano de obra porque la orden de trabajo ya tiene avances de factura');
      }

      // Si llega quotation_detail_id, validar y marcar como tomado
      if (isset($data['quotation_detail_id'])) {
        $quotationDetail = ApOrderQuotationDetails::find($data['quotation_detail_id']);

        if (!$quotationDetail) {
          throw new Exception('Detalle de cotización no encontrado');
        }

        if ($quotationDetail->status === ApOrderQuotationDetails::STATUS_TAKEN) {
          throw new Exception('Este ítem de cotización ya está siendo utilizado en otra mano de obra');
        }

        // Marcar como tomado
        $quotationDetail->update(['status' => ApOrderQuotationDetails::STATUS_TAKEN]);
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
        $discountPercentage = $data['discount_percentage'] ?? 0;
        $costBase = $timeSpentDecimal * floatval($data['hourly_rate']) * $factor;

        // Calculamos el monto total
        $data['total_cost'] = $costBase;

        // Aplicar descuento si existe
        if ($discountPercentage > 0) {
          $discountAmount = $costBase * ($discountPercentage / 100);
          $data['net_amount'] = $costBase - $discountAmount;
        } else {
          $data['net_amount'] = $costBase;
        }
      }
      $data['hourly_rate'] = floatval($data['hourly_rate']) * $factor;
      $data['time_spent'] = $timeSpentDecimal;

      $workOrderLabour = WorkOrderLabour::create($data);
      $workOrder->calculateTotals();

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

  /**
   * Traducir estado de solicitud de descuento
   */
  private function translateDiscountStatus(string $status): string
  {
    $translations = [
      DiscountRequestsWorkOrder::STATUS_PENDING => 'pendiente',
      DiscountRequestsWorkOrder::STATUS_APPROVED => 'aprobado',
      DiscountRequestsWorkOrder::STATUS_REJECTED => 'rechazado',
    ];

    return $translations[$status] ?? $status;
  }

  public function show($id)
  {
    return new WorkOrderLabourResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrderLabour = $this->find($data['id']);
      $workOrder = $workOrderLabour->workOrder;

      // Calcular el costo total automáticamente si se actualizan time_spent, hourly_rate o discount_percentage
      if (isset($data['time_spent']) || isset($data['hourly_rate']) || isset($data['discount_percentage'])) {
        // Obtener time_spent en formato decimal
        if (isset($data['time_spent'])) {
          $timeSpent = is_numeric($data['time_spent'])
            ? floatval($data['time_spent'])
            : $this->timeToDecimal($data['time_spent']);
        } else {
          $timeSpent = $workOrderLabour->time_spent_decimal;
        }

        $hourlyRate = $data['hourly_rate'] ?? $workOrderLabour->hourly_rate;
        $discountPercentage = $data['discount_percentage'] ?? $workOrderLabour->discount_percentage ?? 0;

        // Obtener el factor de conversión de moneda si hay cotización asociada
        if ($workOrder->order_quotation_id) {
          $orderQuotation = $workOrder->orderQuotation;
          if ($orderQuotation->currency_id === $workOrder->currency_id) {
            $factor = 1;
          } else {
            if ($workOrder->currency_id === TypeCurrency::PEN_ID) {
              $factor = $orderQuotation->exchange_rate;
            } else if ($workOrder->currency_id === TypeCurrency::USD_ID) {
              $factor = 1;
            } else {
              throw new Exception('Moneda no soportada para la cotización de la orden de trabajo');
            }
          }
        } else {
          $factor = 1;
        }

        // Calcular costo base con el factor de conversión
        $costBase = $timeSpent * floatval($hourlyRate) * $factor;

        $data['total_cost'] = $costBase;

        // Aplicar descuento si existe
        if ($discountPercentage > 0) {
          $discountAmount = $costBase * ($discountPercentage / 100);
          $data['net_amount'] = $costBase - $discountAmount;
        } else {
          $data['net_amount'] = $costBase;
        }

        // Actualizar hourly_rate con el factor si cambió
        if (isset($data['hourly_rate'])) {
          $data['hourly_rate'] = floatval($data['hourly_rate']) * $factor;
        }

        // Actualizar time_spent si cambió
        if (isset($data['time_spent'])) {
          $data['time_spent'] = $timeSpent;
        }
      }

      $workOrderLabour->update($data);
      $workOrder->calculateTotals();

      return new WorkOrderLabourResource($workOrderLabour->load(['worker', 'workOrder']));
    });
  }

  public function destroy($id)
  {
    $workOrderLabour = $this->find($id);
    $workOrder = $workOrderLabour->workOrder;

    // Validar que no existan avances de factura
    if ($workOrder->advancesWorkOrder()->exists()) {
      throw new Exception('No se puede eliminar la mano de obra porque la orden de trabajo ya tiene avances de factura');
    }

    // Validar si existe una solicitud de descuento activa
    $discountRequest = DiscountRequestsWorkOrder::where('part_labour_id', $id)
      ->where('part_labour_model', WorkOrderLabour::class)
      ->whereIn('status', [
        DiscountRequestsWorkOrder::STATUS_PENDING,
        DiscountRequestsWorkOrder::STATUS_APPROVED,
        DiscountRequestsWorkOrder::STATUS_REJECTED
      ])
      ->first();

    if ($discountRequest) {
      throw new Exception('No se puede eliminar la mano de obra porque tiene una solicitud de descuento en estado ' . $this->translateDiscountStatus($discountRequest->status));
    }

    DB::transaction(function () use ($workOrderLabour, $workOrder) {
      // Si la OT tiene order_quotation_id, buscar el item en la cotización y liberarlo
      if ($workOrder->order_quotation_id) {
        $quotationDetail = ApOrderQuotationDetails::where('order_quotation_id', $workOrder->order_quotation_id)
          ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_LABOR)
          ->where('description', $workOrderLabour->description)
          ->where('unit_price', $workOrderLabour->hourly_rate)
          ->where('quantity', $workOrderLabour->time_spent_decimal)
          ->where('status', ApOrderQuotationDetails::STATUS_TAKEN)
          ->first();

        if ($quotationDetail) {
          $quotationDetail->update(['status' => ApOrderQuotationDetails::STATUS_PENDING]);
        }
      }

      $workOrderLabour->delete();
      $workOrder->calculateTotals();
    });

    return response()->json(['message' => 'Mano de obra eliminada correctamente']);
  }
}
