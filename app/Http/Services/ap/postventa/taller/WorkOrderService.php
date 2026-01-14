<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Resources\ap\postventa\taller\WorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderItem;
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
    $workOrder->load('items', 'orderQuotation');
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

      // Update work order
      $workOrder->update($data);

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
    $workOrder = ApWorkOrder::with(['labours', 'advancesWorkOrder', 'orderQuotation'])
      ->findOrFail($workOrderId);

    // Filter labours by group_number if provided
    $labours = $groupNumber !== null
      ? $workOrder->labours->where('group_number', $groupNumber)
      : $workOrder->labours;

    // Calculate total labour cost
    $totalLabourCost = $labours->sum('total_cost') ?? 0;

    // Get total parts cost from order quotation total_amount
    $totalPartsCost = $workOrder->orderQuotation?->total_amount ?? 0;

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
      ],
      'advances' => ElectronicDocumentResource::collection($workOrder->advancesWorkOrder),
    ]);
  }
}
