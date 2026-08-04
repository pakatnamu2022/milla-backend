<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\CancelWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\ChangeAdvisorWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreDeductibleWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderItemsRequest;
use App\Http\Requests\ap\postventa\taller\UploadWorkOrderDocumentsRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteMigrationLogService;
use App\Http\Services\DatabaseSyncService;
use App\Jobs\VerifyAndMigrateInternalNoteJob;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource;
use Exception;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
  protected WorkOrderService $service;

  public function __construct(WorkOrderService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function listWithInternalNotes(Request $request)
  {
    try {
      return $this->service->listWithInternalNotes($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderRequest $request)
  {
    try {
      $this->validateVehicleForWorkOrder($request->vehicle_id);
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateWorkOrderRequest $request, $id)
  {
    try {
      $data = $request->validated();

      // Validar el vehículo si está presente en la actualización
      if (isset($data['vehicle_id'])) {
        $this->validateVehicleForWorkOrder($data['vehicle_id']);

        // Obtener los datos del vehículo para actualizar plate y vin
        $vehicle = Vehicles::find($data['vehicle_id']);
        if ($vehicle) {
          $data['vehicle_plate'] = $vehicle->plate;
          $data['vehicle_vin'] = $vehicle->vin;
        }
      }

      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateItems(UpdateWorkOrderItemsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['work_order_id'] = $id;
      return $this->success($this->service->updateItems($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getPreLiquidationPdf($id)
  {
    try {
      return $this->service->getPreLiquidationPdf($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al generar el preliquidación',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function unlinkQuotation($id)
  {
    try {
      return $this->success($this->service->unlinkQuotation($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function authorization(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'allow_remove_associated_quote' => 'sometimes|required|boolean:',
          'allow_editing_inspection' => 'sometimes|required|boolean'
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->authorization($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function invoiceTo(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'invoice_to' => 'required|integer|exists:business_partners,id',
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->invoiceTo($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updatePickupPerson(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'num_doc_pickup' => ['required', 'string', 'regex:/^(\d{8}|\d{9})$/'],
          'full_pickup_name' => 'required|string',
          'phone_pickup' => 'required|string',
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->updatePickupPerson($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changeAdvisor(ChangeAdvisorWorkOrderRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->changeAdvisor($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function generateDelivery(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'actual_delivery_date' => 'required|date',
          'follow_ups' => 'required|array|min:1',
          'follow_ups.*.days' => 'required|integer|min:1',
          'follow_ups.*.time_start' => 'required|date_format:H:i',
          'follow_ups.*.time_end' => 'required|date_format:H:i',
          'signature_delivery' => 'required|string|regex:/^data:image\/[a-z+]+;base64,/',
          'notes_delivery' => 'nullable|string|max:250',
        ]
      );
      $data['id'] = $id;

      return $this->success($this->service->generateDelivery($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function generateDeliveryReport($id)
  {
    try {
      return $this->service->generateDeliveryReport($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al generar el reporte de recepción',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function vehicleHistory($vehicleId)
  {
    try {
      return $this->success($this->service->getVehicleHistory($vehicleId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function generateInternalNote($id)
  {
    try {
      return $this->service->generateInternalNote($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function revertInternalNote($id)
  {
    try {
      return $this->service->revertInternalNote($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function internalNoteLogs($id)
  {
    try {
      $workOrder = $this->service->find($id);

      if (!$workOrder) {
        return response()->json([
          'success' => false,
          'message' => 'Orden de trabajo no encontrada',
        ], 404);
      }

      $internalNote = $workOrder->internalNote;

      if (!$internalNote) {
        return response()->json([
          'success' => false,
          'message' => 'La orden de trabajo no tiene una nota interna asociada',
        ], 404);
      }

      $logs = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
        ->orderBy('id')
        ->get();

      return response()->json([
        'success' => true,
        'data' => [
          'work_order' => [
            'id' => $workOrder->id,
            'code' => $workOrder->code,
            'status' => $workOrder->status->name ?? null,
          ],
          'internal_note' => [
            'id' => $internalNote->id,
            'created_date' => $internalNote->created_date?->format('Y-m-d H:i:s'),
            'status' => $internalNote->status,
            'created_at' => $internalNote->created_at->format('Y-m-d H:i:s'),
          ],
          'logs' => VehiclePurchaseOrderMigrationLogResource::collection($logs),
        ],
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  public function authorizeInternalNoteRevert($id)
  {
    try {
      $authorizedBy = auth()->id();
      return $this->service->authorizeInternalNoteRevert($id, $authorizedBy);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function generatePDIForVehicle($id)
  {
    try {
      return $this->service->generatePDIForVehicle($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function generateInstallationAccessories($id)
  {
    try {
      return $this->service->generateInstallationAccessories($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function getByIds(Request $request)
  {
    try {
      $ids = $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'required|integer|exists:ap_work_orders,id'
      ]);

      return $this->success($this->service->getByIds($ids['ids']));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changeCurrency(Request $request, $id)
  {
    try {
      $data = $request->validate([
        'currency_id' => 'required|integer|exists:type_currency,id'
      ]);
      $data['id'] = $id;
      return $this->success($this->service->changeCurrency($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sendToFinished($id)
  {
    try {
      $data['id'] = $id;
      return $this->success($this->service->sendToFinished($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function cancel(CancelWorkOrderRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->cancel($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function revertir($id)
  {
    try {
      $data['id'] = $id;
      return $this->success($this->service->revertir($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function exportWorkOrders(IndexWorkOrderRequest $request)
  {
    try {
      return $this->service->exportWorkOrders($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function recalculateTotals($id)
  {
    try {
      return $this->success($this->service->recalculateTotals($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeDeductible(StoreDeductibleWorkOrderRequest $request)
  {
    try {
      return $this->success($this->service->storeDeductible($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function deleteDeductible($id)
  {
    try {
      return $this->success($this->service->deleteDeductible($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function uploadDocuments(UploadWorkOrderDocumentsRequest $request, $id)
  {
    try {
      $files = $request->file('files');
      return $this->success($this->service->uploadDocuments((int)$id, $files));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function documents($id)
  {
    try {
      return $this->success($this->service->listDocuments((int)$id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Lanza el job de verificación de migración de nota interna a Dynamics
   */
  public function verifyInternalNoteMigration($id)
  {
    try {
      $workOrder = $this->service->find($id);

      if (!$workOrder) {
        return response()->json([
          'success' => false,
          'message' => 'Orden de trabajo no encontrada',
        ], 404);
      }

      $internalNote = $workOrder->internalNote;

      if (!$internalNote) {
        return response()->json([
          'success' => false,
          'message' => 'La orden de trabajo no tiene una nota interna asociada',
        ], 404);
      }

      // Determinar si es reversión
      $isReversal = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
        ->where('step', 'LIKE', '%REVERSAL%')
        ->exists();

      // Despachar job a la cola
      VerifyAndMigrateInternalNoteJob::dispatch($internalNote->id, $isReversal);

      return response()->json([
        'success' => true,
        'message' => 'Job de verificación de nota interna lanzado correctamente',
        'data' => [
          'internal_note_id' => $internalNote->id,
          'internal_note_number' => $internalNote->number,
          'is_reversal' => $isReversal,
        ],
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Valida que el vehículo cumpla con los requisitos para crear/actualizar una orden de trabajo
   *
   * @param int $vehicleId ID del vehículo a validar
   * @throws Exception Si el vehículo no cumple con los requisitos
   */
  private function validateVehicleForWorkOrder(int $vehicleId): void
  {
    $vehicle = Vehicles::with([
      'model.family.brand',
      'color'
    ])->find($vehicleId);

    if (!$vehicle) {
      throw new Exception('El vehículo seleccionado no existe.');
    }

    // Validar que el vehículo tenga un modelo asignado
    if (!$vehicle->ap_models_vn_id) {
      throw new Exception('El vehículo debe tener un modelo asignado para crear una orden de trabajo.');
    }

    // Validar que el modelo exista y tenga familia
    if (!$vehicle->model) {
      throw new Exception('El modelo del vehículo no existe.');
    }

    if (!$vehicle->model->family) {
      throw new Exception('El modelo del vehículo debe tener una familia asignada.');
    }

    // Validar que la familia tenga marca
    if (!$vehicle->model->family->brand) {
      throw new Exception('La familia del modelo debe tener una marca asignada.');
    }

    // Validar que la marca no sea otros id = 13
    if ($vehicle->model->family->brand->id === ApVehicleBrand::BRAND_OTHERS_ID) {
      throw new Exception('No se puede crear una orden de trabajo para vehículos de marca "OTROS".');
    }

    // Validar que el vehículo no tenga color "OTROS" (COLOR_OTHERS_ID = 1003)
    if ($vehicle->vehicle_color_id === ApMasters::COLOR_OTHERS_ID) {
      throw new Exception('No se puede crear una orden de trabajo para vehículos con color "OTROS".');
    }
  }
}
