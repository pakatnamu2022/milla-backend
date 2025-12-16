<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApPostVentaMasters;
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
    return $this->getFilteredResults(
      ApWorkOrder::class,
      $request,
      ApWorkOrder::filters,
      ApWorkOrder::sorts,
      WorkOrderResource::class);
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
      $data['status_id'] = ApPostVentaMasters::OPENING_WORK_ORDER_ID;
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
    return new WorkOrderResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);
      $vehicle = Vehicles::find($data['vehicle_id']);

      if ($vehicle->customer_id === null) {
        throw new Exception('El vehículo debe estar asociado a un "TITULAR" para crear una cotización');
      }

      // Extract items
      $items = $data['items'] ?? null;
      unset($data['items']);

      // Update work order
      $workOrder->update($data);

      // Update items if provided
      if ($items !== null) {
        // Get existing item IDs from request
        $requestItemIds = collect($items)->pluck('id')->filter()->toArray();

        // Delete items that are not in the request
        ApWorkOrderItem::where('work_order_id', $workOrder->id)
          ->whereNotIn('id', $requestItemIds)
          ->delete();

        // Update or create items
        foreach ($items as $itemData) {
          if (isset($itemData['id'])) {
            // Update existing item
            $item = ApWorkOrderItem::find($itemData['id']);
            if ($item && $item->work_order_id == $workOrder->id) {
              $item->update($itemData);
            }
          } else {
            // Create new item
            $itemData['work_order_id'] = $workOrder->id;
            ApWorkOrderItem::create($itemData);
          }
        }
      }

      // Reload relations
      $workOrder->load([
        'fuelLevel',
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
   * Calculate and update totals
   */
  public function calculateTotals($workOrderId)
  {
    $workOrder = $this->find($workOrderId);
    $workOrder->calculateTotals();

    return new WorkOrderResource($workOrder->fresh([
      'fuelLevel',
      'appointmentPlanning',
      'vehicle',
      'status',
      'advisor',
      'sede',
      'creator',
      'items.typePlanning'
    ]));
  }
}
