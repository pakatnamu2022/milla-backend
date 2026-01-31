<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApReceivingChecklistResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Jobs\SyncShippingGuideJob;
use App\Jobs\VerifyAndMigrateShippingGuideJob;
use App\Models\ap\comercial\ApReceivingChecklist;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApReceivingChecklistService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      ApReceivingChecklist::class,
      $request,
      ApReceivingChecklist::filters,
      ApReceivingChecklist::sorts,
      ApReceivingChecklistResource::class
    );
  }

  public function find($id): ApReceivingChecklist
  {
    $receivingChecklist = ApReceivingChecklist::where('id', $id)->first();
    if (!$receivingChecklist) {
      throw new Exception('Checklist de recepción no encontrado');
    }
    return $receivingChecklist;
  }

  public function getByShippingGuide($shippingGuideId): JsonResponse
  {
    try {
      // Validate shipping guide exists and load relationships
      $shippingGuide = ShippingGuides::with([
        'vehicleMovement' => function ($query) {
          $query->with([
            'vehicle' => function ($vehicleQuery) {
              $vehicleQuery->with([
                'purchaseOrders' => function ($poQuery) {
                  $poQuery->with(['items' => function ($itemQuery) {
                    $itemQuery->with('unitMeasurement')
                      ->where('is_vehicle', false);
                  }]);
                }
              ]);
            }
          ]);
        }
      ])->find($shippingGuideId);

      if (!$shippingGuide) {
        throw new Exception('Guía de envío no encontrada');
      }

      // Get all checklists for this shipping guide with relationships
      $checklists = ApReceivingChecklist::where('shipping_guide_id', $shippingGuideId)
        ->with(['receiving', 'shipping_guide'])
        ->get();

      // Get accessories from purchase orders related to this vehicle
      $accessories = [];

      if ($shippingGuide->vehicleMovement) {

        $vehicle = $shippingGuide->vehicleMovement->vehicle;

        if ($vehicle) {

          $purchaseOrders = $vehicle->purchaseOrders;

          foreach ($purchaseOrders as $purchaseOrder) {
            $accessoryItems = $purchaseOrder->items; // Ya filtrados por is_vehicle = false en el eager loading

            foreach ($accessoryItems as $item) {
              $accessories[] = [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'unit_measurement' => $item->unitMeasurement?->abbreviation ?? 'UND',
              ];
            }
          }
        }
      }

      return response()->json([
        'data' => ApReceivingChecklistResource::collection($checklists),
        'note_received' => $shippingGuide->note_received,
        'accessories' => $accessories,
      ]);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function update(mixed $data): JsonResponse
  {
    DB::beginTransaction();
    try {
      // Validate shipping guide exists
      $shippingGuide = ShippingGuides::find($data['shipping_guide_id']);
      if (!$shippingGuide) {
        throw new Exception('Guía de envío no encontrada');
      }

      if (!$shippingGuide->aceptada_por_sunat) {
        throw new Exception('Debe esperar a que la guía de remisión sea aceptada por SUNAT antes de registrar la recepción');
      }

      if ($shippingGuide->is_received) {
        throw new Exception('La guía de remisión ya ha sido recepcionada');
      }

      // Validate items_receiving is provided and is an array/object
      if (!isset($data['items_receiving']) || !is_array($data['items_receiving'])) {
        throw new Exception('items_receiving debe ser un objeto');
      }

      // validamos que la fecha de hoy no sea menos a la fecha de translado issue_date
      $today = now()->startOfDay();
      $issueDate = $shippingGuide->issue_date ? $shippingGuide->issue_date->startOfDay() : null;
      if ($issueDate && $today->lt($issueDate)) {
        throw new Exception('La fecha de recepción no puede ser anterior a la fecha de translado de la guía de remisión');
      }

      // Get existing records for this shipping guide
      $existingRecords = ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])->get();
      $existingReceivingIds = $existingRecords->pluck('receiving_id')->toArray();
      $newReceivingIds = array_keys($data['items_receiving']);

      // Determine which to delete (in existing but not in new)
      $toDelete = array_diff($existingReceivingIds, $newReceivingIds);

      // Determine which to add (in new but not in existing)
      $toAdd = array_diff($newReceivingIds, $existingReceivingIds);

      // Determine which to update (in both existing and new)
      $toUpdate = array_intersect($existingReceivingIds, $newReceivingIds);

      // Delete removed records
      if (!empty($toDelete)) {
        ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
          ->whereIn('receiving_id', $toDelete)
          ->delete();
      }

      // Add new records
      foreach ($toAdd as $receivingId) {
        // Validate receiving exists
        $receiving = ApDeliveryReceivingChecklist::find($receivingId);
        if (!$receiving) {
          throw new Exception("Checklist de recepción con ID {$receivingId} no encontrado");
        }

        ApReceivingChecklist::create([
          'receiving_id' => $receivingId,
          'shipping_guide_id' => $data['shipping_guide_id'],
          'quantity' => $data['items_receiving'][$receivingId],
        ]);
      }

      // Update existing records with new quantities
      foreach ($toUpdate as $receivingId) {
        ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
          ->where('receiving_id', $receivingId)
          ->update(['quantity' => $data['items_receiving'][$receivingId]]);
      }

      // marcar cono enviada a Dynamics
      $shippingGuide->markAsSentToDynamic();

      // Update shipping guide with note, is_received, received_by and received_date
      $shippingGuide->update([
        'is_received' => true,
        'note_received' => $data['note'] ?? null,
        'received_by' => auth()->id(),
        'received_date' => now(),
      ]);

      // Despachar el Job síncronamente para debugging
      VerifyAndMigrateShippingGuideJob::dispatchSync($shippingGuide->id);

      // Get updated records
      $updatedRecords = ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
        ->with('receiving')
        ->get()
        ->map(fn($record) => new ApReceivingChecklistResource($record));

      DB::commit();

      // Enviar correo de notificación en segundo plano (después del commit)
      try {
        $this->sendReceptionEmail($shippingGuide->fresh(['vehicleMovement.vehicle', 'transmitter', 'receiver', 'receivedBy']), $updatedRecords);
      } catch (Exception $e) {
        Log::error('Error al enviar correo de recepción de vehículo', [
          'shipping_guide_id' => $shippingGuide->id,
          'error' => $e->getMessage(),
        ]);
      }

      return response()->json([
        'data' => $updatedRecords,
        'note_received' => $shippingGuide->note_received,
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroyByShippingGuide($shippingGuideId): JsonResponse
  {
    DB::beginTransaction();
    try {
      $deleted = ApReceivingChecklist::where('shipping_guide_id', $shippingGuideId)->delete();
      DB::commit();
      return response()->json([
        'message' => 'Registros eliminados correctamente',
        'deleted' => $deleted,
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function getVehicleByShippingGuide($shippingGuideId): VehiclesResource
  {
    $shippingGuide = ShippingGuides::with([
      'vehicleMovement.vehicle' => function ($query) {
        $query->with([
          'model.family.brand',
          'color',
          'engineType',
          'vehicleStatus',
          'warehouse.sede',
          'warehousePhysical.sede',
          'customer',
          'vehicleMovements'
        ]);
      }
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      throw new Exception('Guía de envío no encontrada');
    }

    $vehicle = $shippingGuide->vehicleMovement?->vehicle;

    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado para esta guía de envío');
    }

    return new VehiclesResource($vehicle);
  }

  /**
   * Envía correo de notificación de recepción de vehículo en segundo plano
   *
   * @param ShippingGuides $shippingGuide
   * @param mixed $receivedItems
   * @return void
   */
  private function sendReceptionEmail(ShippingGuides $shippingGuide, mixed $receivedItems): void
  {
    try {
      $emailService = app(EmailService::class);
      $vehicle = $shippingGuide->vehicleMovement->vehicle;

      // Preparar items para el template
      $items = collect($receivedItems)->map(function ($item) {
        $description = $item->resource->receiving->description ?? 'N/A';
        $quantity = $item->resource->quantity ?? 0;

        // Si la cantidad es mayor a 0, mostrar descripción con cantidad
        // Si es 0 o null, solo mostrar la descripción
        if ($quantity > 0) {
          $formattedName = "{$description} ({$quantity})";
        } else {
          $formattedName = $description;
        }

        return [
          'name' => $formattedName,
          'quantity' => $quantity,
        ];
      })->toArray();

      $coordinator = $vehicle->purchaseOrder->vehicleMovement->createdByUser->person->email2 ?? $vehicle->purchaseOrder->vehicleMovement->createdByUser->person->email1 ?? null;
      $consultant = $vehicle->purchaseRequestQuote?->opportunity->worker->email2 ?? $vehicle->purchaseRequestQuote?->opportunity->worker->email1 ?? null;

      $emailsTo = [$coordinator, $consultant];
      $emailsCC = ['wsuclupef@automotorespakatnamu.com', 'dordinolac@grupopakatnamu.com', 'hvaldiviezos@automotorespakatnamu.com', 'kquesquenm@automotorespakatnamu.com'];

      $emailService->queue([
        'to' => array_filter($emailsTo),
        'cc' => $emailsCC,
        'subject' => 'Notificación de Recepción de Vehículo - ' . ($vehicle->vin ?? 'VIN no disponible'),
        'template' => 'emails.vehicle-reception',
        'data' => [
          'advisor_name' => 'Wilmer Yoel Suclupe Farroñan', // TODO: Obtener del asesor real
          'vehicle_vin' => $vehicle->vin ?? 'N/A',
          'vehicle_model' => $vehicle->model->version ?? 'N/A',
          'vehicle_brand' => $vehicle->model->family->brand->name ?? 'N/A',
          'vehicle_year' => $vehicle->year ?? 'N/A',
          'vehicle_color' => $vehicle->color->description ?? 'N/A',
          'origin' => $shippingGuide->transmitter->address ?? 'N/A',
          'destination' => $shippingGuide->receiver->address ?? 'N/A',
          'received_items' => $items,
          'note' => $shippingGuide->note_received ?? 'N/A',
          'received_by' => $shippingGuide->receivedBy->name ?? 'Sistema',
          'received_date' => $shippingGuide->received_date ? $shippingGuide->received_date->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
          'shipping_guide_id' => $shippingGuide->id,
        ]
      ]);
    } catch (Exception $e) {
      throw $e;
    }
  }
}
