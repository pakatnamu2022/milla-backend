<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Resources\ap\postventa\taller\WorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderItem;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = ApWorkOrder::with('items');
    return $this->getFilteredResults(
      $query,
      $request,
      ApWorkOrder::filters,
      ApWorkOrder::sorts,
      WorkOrderResource::class
    );
  }

  public function find($id)
  {
    $workOrder = ApWorkOrder::with([
      'appointmentPlanning',
      'vehicle',
      'status',
      'advisor',
      'sede',
      'creator',
      'vehicleInspection.damages',
      'items.typePlanning'
    ])->where('id', $id)->first();

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada');
    }

    return $workOrder;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Generate correlative
      $data['correlative'] = $this->generateCorrelative();
      $data['status_id'] = ApMasters::OPENING_WORK_ORDER_ID;
      $vehicle = Vehicles::find($data['vehicle_id']);

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      //Plate, vin del vehiculo
      $vehicle = Vehicles::find($data['vehicle_id']);
      if ($vehicle) {
        $data['vehicle_plate'] = $vehicle->plate;
        $data['vehicle_vin'] = $vehicle->vin;
      }

      // Set created_by
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
        $data['advisor_id'] = auth()->user()->person->id;
      }

      // Set is_taken
      if (isset($data['appointment_planning_id'])) {
        $appointmentPlanning = AppointmentPlanning::find($data['appointment_planning_id']);

        if (!$appointmentPlanning) {
          throw new Exception('Cita no encontrada');
        }

        if ($appointmentPlanning->is_taken) {
          throw new Exception('La cita ya está tomada');
        }

        $appointmentPlanning->update(['is_taken' => true]);
      }

      // Extract items
      $items = $data['items'] ?? [];
      unset($data['items']);

      // Create work order
      $workOrder = ApWorkOrder::create($data);

      // Create items
      if (!empty($items)) {
        foreach ($items as $item) {
          $item['work_order_id'] = $workOrder->id;
          ApWorkOrderItem::create($item);
        }
      }

      return new WorkOrderResource($workOrder);
    });
  }

  public function show($id)
  {
    $workOrder = $this->find($id);
    $workOrder->load('items', 'orderQuotation', 'labours', 'parts', 'advancesWorkOrder');
    return new WorkOrderResource($workOrder);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);
      $vehicle = Vehicles::find($data['vehicle_id']);

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede modificar una orden de trabajo cerrada');
      }

      // Detectar si cambió el tipo de moneda
      $oldCurrencyId = $workOrder->currency_id;
      $newCurrencyId = $data['currency_id'] ?? $oldCurrencyId;
      $currencyChanged = $oldCurrencyId !== null && $newCurrencyId !== null && $oldCurrencyId != $newCurrencyId;

      // Update work order
      $workOrder->update($data);

      // Si cambió el tipo de moneda, recalcular labours y parts
      if ($currencyChanged) {
        $this->recalculateCurrencyChange($workOrder, $oldCurrencyId, $newCurrencyId);
      }

      // Reload relations
      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function destroy($id)
  {
    $workOrder = $this->find($id);

    if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
      throw new Exception('No se puede eliminar una orden de trabajo cerrada');
    }

    if ($workOrder->appointment_planning_id !== null) {
      $appointmentPlanning = AppointmentPlanning::find($workOrder->appointment_planning_id);
      if ($appointmentPlanning) {
        $appointmentPlanning->update(['is_taken' => false]);
      }
    }

    DB::transaction(function () use ($workOrder) {
      // Delete items first
      ApWorkOrderItem::where('work_order_id', $workOrder->id)->delete();

      // Delete work order
      $workOrder->delete();
    });

    return response()->json(['message' => 'Orden de trabajo eliminada correctamente']);
  }

  /**
   * Genera el siguiente correlativo para una orden de trabajo en formato OT-YYYY-MM-XXXX
   */
  private function generateCorrelative(): string
  {
    $year = date('Y');
    $month = date('m');

    $lastWorkOrder = ApWorkOrder::withTrashed('correlative', 'like', "OT-{$year}-{$month}-%")
      ->orderBy('correlative', 'desc')
      ->first();

    if ($lastWorkOrder) {
      $lastNumber = (int)substr($lastWorkOrder->correlative, -4);
      $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
      $newNumber = '0001';
    }

    return "OT-{$year}-{$month}-{$newNumber}";
  }

  /**
   * Get payment summary for a work order
   * Returns consolidated payment information including labour, parts and advances
   * Parts cost is taken from the associated order quotation total
   */
  public function getPaymentSummary($workOrderId, $groupNumber = 1)
  {
    $workOrder = ApWorkOrder::with(['labours', 'advancesWorkOrder', 'parts'])
      ->findOrFail($workOrderId);

    // Filter labours by group_number if provided
    $labours = $groupNumber !== null
      ? $workOrder->labours->where('group_number', $groupNumber)
      : $workOrder->labours;

    // Filter parts by group_number if provided
    $parts = $groupNumber !== null
      ? $workOrder->parts->where('group_number', $groupNumber)
      : $workOrder->parts;

    // Calculate total labour cost
    $totalLabourCost = $labours->sum('total_cost') ?? 0;

    // Get total parts cost from order quotation total_amount
    $totalPartsCost = $parts->sum('total_amount') ?? 0;

    // Calculate total advances
    $totalAdvances = $workOrder->advancesWorkOrder->sum('total') ?? 0;

    // Calculate consolidated total
    $subtotal = $totalLabourCost + $totalPartsCost;
    $discountAmount = $workOrder->discount_amount ?? 0;
    $taxAmount = $workOrder->tax_amount ?? 0;
    $totalAmount = $subtotal - $discountAmount + $taxAmount;

    // Calculate remaining balance (total - advances)
    $remainingBalance = $totalAmount - $totalAdvances;

    return response()->json([
      'work_order_id' => $workOrder->id,
      'correlative' => $workOrder->correlative,
      'group_number' => $groupNumber,
      'payment_summary' => [
        'labour_cost' => (float)$totalLabourCost,
        'parts_cost' => (float)$totalPartsCost,
        'subtotal' => (float)$subtotal,
        'discount_amount' => (float)$discountAmount,
        'tax_amount' => (float)$taxAmount,
        'total_amount' => (float)$totalAmount,
        'total_advances' => (float)$totalAdvances,
        'remaining_balance' => (float)$remainingBalance,
      ]
    ]);
  }

  public function getPreLiquidationPdf($id)
  {
    $workOrder = $this->find($id);
    $workOrder->load([
      'vehicle.customer',
      'vehicle.model',
      'sede',
      'advisor',
      'labours.worker',
      'parts.product',
      'advancesWorkOrder',
      'vehicleInspection'
    ]);

    $client = $workOrder->vehicle->customer;
    $vehicle = $workOrder->vehicle;

    // Calcular totales de mano de obra
    $totalLabourCost = $workOrder->labours->sum('total_cost') ?? 0;

    // Calcular totales de repuestos
    $totalPartsCost = $workOrder->parts->sum('total_amount') ?? 0;

    // Calcular totales generales
    $subtotal = $totalLabourCost + $totalPartsCost;
    $discountAmount = $workOrder->discount_amount ?? 0;
    $taxAmount = $workOrder->tax_amount ?? 0;
    $totalAmount = $subtotal - $discountAmount + $taxAmount;

    // Calcular anticipos y saldo
    $totalAdvances = $workOrder->advancesWorkOrder->sum('total') ?? 0;
    $remainingBalance = $totalAmount - $totalAdvances;

    $data = [
      'workOrder' => $workOrder,
      'client' => $client,
      'vehicle' => $vehicle,
      'labours' => $workOrder->labours,
      'parts' => $workOrder->parts,
      'advances' => $workOrder->advancesWorkOrder,
      'totals' => [
        'labour_cost' => $totalLabourCost,
        'parts_cost' => $totalPartsCost,
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'tax_amount' => $taxAmount,
        'total_amount' => $totalAmount,
        'total_advances' => $totalAdvances,
        'remaining_balance' => $remainingBalance,
      ]
    ];

    // Generar PDF
    $pdf = \PDF::loadView('reports.ap.postventa.taller.pre-liquidation-work-order', $data);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream("pre-liquidacion-{$workOrder->correlative}.pdf");
  }

  /**
   * Recalcula los valores de labours y parts cuando cambia el tipo de moneda de la OT
   * Si la OT tiene cotización asociada: usa el tipo de cambio de la cotización
   * Si no tiene cotización: usa el tipo de cambio actual de la fecha
   *
   * @param ApWorkOrder $workOrder
   * @param int $oldCurrencyId
   * @param int $newCurrencyId
   * @return void
   * @throws Exception
   */
  private function recalculateCurrencyChange(ApWorkOrder $workOrder, int $oldCurrencyId, int $newCurrencyId): void
  {
    // Obtener el factor de conversión
    $factor = $this->getConversionFactor($workOrder, $oldCurrencyId, $newCurrencyId);

    // Recalcular labours
    $this->recalculateLabours($workOrder->id, $factor);

    // Recalcular parts
    $this->recalculateParts($workOrder->id, $factor);
  }

  /**
   * Obtiene el factor de conversión según la moneda y si tiene cotización
   *
   * @param ApWorkOrder $workOrder
   * @param int $oldCurrencyId
   * @param int $newCurrencyId
   * @return float
   * @throws Exception
   */
  private function getConversionFactor(ApWorkOrder $workOrder, int $oldCurrencyId, int $newCurrencyId): float
  {
    // Obtener el tipo de cambio
    $exchangeRate = $this->getExchangeRate($workOrder);

    // De soles a dólares: dividir por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::PEN_ID && $newCurrencyId === TypeCurrency::USD_ID) {
      return 1 / $exchangeRate;
    }

    // De dólares a soles: multiplicar por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::USD_ID && $newCurrencyId === TypeCurrency::PEN_ID) {
      return $exchangeRate;
    }

    // Si son la misma moneda o no reconocida, no hay conversión
    return 1;
  }

  /**
   * Obtiene el tipo de cambio a usar
   * Si la OT tiene cotización: usa el exchange_rate de la cotización
   * Si no tiene cotización: usa el tipo de cambio actual
   *
   * @param ApWorkOrder $workOrder
   * @return float
   * @throws Exception
   */
  private function getExchangeRate(ApWorkOrder $workOrder): float
  {
    // Si tiene cotización asociada, usar su tipo de cambio
    if ($workOrder->order_quotation_id) {
      $quotation = $workOrder->orderQuotation;
      if ($quotation && $quotation->exchange_rate) {
        return (float)$quotation->exchange_rate;
      }
    }

    // Si no tiene cotización, usar el tipo de cambio actual
    $today = Carbon::now()->format('Y-m-d');
    $exchangeRate = ExchangeRate::where('date', $today)
      ->where('type', ExchangeRate::TYPE_VENTA)
      ->first();

    if (!$exchangeRate) {
      throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy: ' . $today);
    }

    return (float)$exchangeRate->rate;
  }

  /**
   * Recalcula los valores de mano de obra con el factor de conversión
   *
   * @param int $workOrderId
   * @param float $factor
   * @return void
   */
  private function recalculateLabours(int $workOrderId, float $factor): void
  {
    $labours = WorkOrderLabour::where('work_order_id', $workOrderId)->get();

    foreach ($labours as $labour) {
      $newHourlyRate = $labour->hourly_rate * $factor;
      $newTotalCost = $labour->total_cost * $factor;

      $labour->update([
        'hourly_rate' => round($newHourlyRate, 2),
        'total_cost' => round($newTotalCost, 2),
      ]);
    }
  }

  /**
   * Recalcula los valores de repuestos con el factor de conversión
   *
   * @param int $workOrderId
   * @param float $factor
   * @return void
   */
  private function recalculateParts(int $workOrderId, float $factor): void
  {
    $parts = ApWorkOrderParts::where('work_order_id', $workOrderId)->get();

    foreach ($parts as $part) {
      $newUnitCost = $part->unit_cost * $factor;
      $newUnitPrice = $part->unit_price * $factor;
      $newSubtotal = $part->subtotal * $factor;
      $newTaxAmount = $part->tax_amount * $factor;
      $newTotalAmount = $part->total_amount * $factor;

      $part->update([
        'unit_cost' => round($newUnitCost, 2),
        'unit_price' => round($newUnitPrice, 2),
        'subtotal' => round($newSubtotal, 2),
        'tax_amount' => round($newTaxAmount, 2),
        'total_amount' => round($newTotalAmount, 2),
      ]);
    }
  }
}
