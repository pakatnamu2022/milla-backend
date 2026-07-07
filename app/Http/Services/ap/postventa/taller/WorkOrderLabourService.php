<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderLabourResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Http\Utils\PriceRounding;
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
      $timeSpentDecimal = $this->convertToDecimalTime($data['time_spent']);

      $workOrder = ApWorkOrder::find($data['work_order_id']);
      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureCanBeModified();

      $validateReceipt = $workOrder->shouldValidateReceipt();

      if ($workOrder->vehicleInspection === null && $validateReceipt) {
        throw new Exception('No se puede agregar mano de obra a una orden de trabajo sin recepción de vehículo');
      }

      $this->handleQuotationDetail($data['quotation_detail_id'] ?? null);

      $factor = $this->getCurrencyConversionFactor($workOrder);
      $data['hourly_rate'] = $this->calculateLabourCosts($data, $timeSpentDecimal, floatval($data['hourly_rate']), $data['discount_percentage'] ?? 0, $factor);
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

      $workOrder->ensureCanBeModified();

      if (isset($data['time_spent']) || isset($data['hourly_rate']) || isset($data['discount_percentage'])) {
        $timeSpent = isset($data['time_spent'])
          ? $this->convertToDecimalTime($data['time_spent'])
          : $workOrderLabour->time_spent_decimal;

        $hourlyRate = $data['hourly_rate'] ?? $workOrderLabour->hourly_rate;
        $discountPercentage = $data['discount_percentage'] ?? $workOrderLabour->discount_percentage ?? 0;

        $factor = $this->getCurrencyConversionFactor($workOrder);
        $newHourlyRate = $this->calculateLabourCosts($data, $timeSpent, floatval($hourlyRate), $discountPercentage, $factor);

        if (isset($data['hourly_rate'])) {
          $data['hourly_rate'] = $newHourlyRate;
        }

        if (isset($data['time_spent'])) {
          $data['time_spent'] = $timeSpent;
        }

        // Validar que el nuevo monto no sea menor al monto pagado en anticipos
        $workOrder->refresh();
        $currentTotals = $workOrder->getTotalsArray();

        // Calcular el cambio en net_amount
        $oldNetAmount = $workOrderLabour->net_amount;
        $newNetAmount = $data['net_amount'];
        $deltaNetAmount = $newNetAmount - $oldNetAmount;

        // Aplicar IGV al delta (usar la misma lógica que ApWorkOrder::getTotalsArray)
        $deltaWithTax = $deltaNetAmount * (1 + Constants::VAT_TAX / 100);

        // Proyectar el nuevo total (final_amount incluye IGV)
        $projectedFinalAmount = $currentTotals['total_amount'] + $deltaWithTax;

        // Validar
        $workOrder->validateMinimumAmount($projectedFinalAmount);
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

    $workOrder->ensureCanBeModified();

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

    // Validar que el nuevo monto no sea menor al monto pagado en anticipos
    $workOrder->refresh();
    $currentTotals = $workOrder->getTotalsArray();

    // Calcular el monto del item con IGV incluido (usar la misma lógica que ApWorkOrder::getTotalsArray)
    $itemNetAmount = $workOrderLabour->net_amount;
    $itemWithTax = $itemNetAmount * (1 + Constants::VAT_TAX / 100);

    // Proyectar el nuevo total (final_amount incluye IGV)
    $projectedFinalAmount = $currentTotals['total_amount'] - $itemWithTax;

    $workOrder->validateMinimumAmount($projectedFinalAmount);

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

  /**
   * Convertir time_spent a decimal
   */
  private function convertToDecimalTime(mixed $timeValue): float
  {
    return is_numeric($timeValue)
      ? floatval($timeValue)
      : $this->timeToDecimal($timeValue);
  }

  /**
   * Obtener el factor de conversión de moneda
   */
  private function getCurrencyConversionFactor(ApWorkOrder $workOrder): float
  {
    if (!$workOrder->order_quotation_id) {
      return 1;
    }

    $orderQuotation = $workOrder->orderQuotation;

    if ($orderQuotation->currency_id === $workOrder->currency_id) {
      return 1;
    }

    if ($workOrder->currency_id === TypeCurrency::PEN_ID) {
      return $orderQuotation->exchange_rate;
    }

    if ($workOrder->currency_id === TypeCurrency::USD_ID) {
      return 1;
    }

    throw new Exception('Moneda no soportada para la cotización de la orden de trabajo');
  }

  /**
   * Calcular los costos de mano de obra. hourly_rate (convertido por factor) +
   * total_cost/net_amount/tax_amount: única fuente de verdad compartida con
   * repuestos y detalles de cotización. Devuelve el hourly_rate ya convertido y
   * redondeado para que el llamador decida si lo persiste (ver store()/update()).
   */
  private function calculateLabourCosts(array &$data, float $timeSpent, float $hourlyRate, float $discountPercentage, float $factor): float
  {
    $result = PriceRounding::calculateLine($hourlyRate, $timeSpent, $discountPercentage, $factor);
    $data['total_cost'] = $result['total_cost'];
    $data['net_amount'] = $result['net_amount'];
    $data['tax_amount'] = $result['tax_amount'];

    return $result['unit_price'];
  }

  /**
   * Manejar el detalle de cotización
   */
  private function handleQuotationDetail(?int $quotationDetailId): void
  {
    if ($quotationDetailId === null) {
      return;
    }

    $quotationDetail = ApOrderQuotationDetails::find($quotationDetailId);

    if (!$quotationDetail) {
      throw new Exception('Detalle de cotización no encontrado');
    }

    if ($quotationDetail->status === ApOrderQuotationDetails::STATUS_TAKEN) {
      throw new Exception('Este ítem de cotización ya está siendo utilizado en otra mano de obra');
    }

    $quotationDetail->update(['status' => ApOrderQuotationDetails::STATUS_TAKEN]);
  }
}
