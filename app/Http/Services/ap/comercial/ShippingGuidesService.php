<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\ShippingGuides;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShippingGuidesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ShippingGuides::class,
      $request,
      ShippingGuides::filters,
      ShippingGuides::search,
      ShippingGuidesResource::class,
      [
        'vehicleMovement',
        'transmitter',
        'receiver',
        'transferModality',
        'transferReason'
      ]
    );
  }

  public function find($id)
  {
    $document = ShippingGuides::with([
      'vehicleMovement',
      'transmitter',
      'receiver',
      'transferModality',
      'transferReason'
    ])->find($id);

    if (!$document) {
      throw new Exception('Documento no encontrado');
    }

    return $document;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // 1. Crear el registro de movimiento del vehículo
      $vehicleMovementData = [
        'movement_type' => $data['movement_type'],
        'ap_vehicle_purchase_order_id' => $data['ap_vehicle_purchase_order_id'] ?? null,
        'observation' => $data['observation'] ?? null,
        'movement_date' => $data['movement_date'],
        'origin_address' => $data['origin_address'] ?? null,
        'destination_address' => $data['destination_address'] ?? null,
        'previous_status_id' => $data['previous_status_id'] ?? null,
        'new_status_id' => $data['new_status_id'] ?? null,
        'created_by' => Auth::id(),
      ];

      $vehicleMovement = ShippingGuides::create($vehicleMovementData);

      // 2. Manejar la carga del archivo si existe
      $filePath = null;
      $fileName = null;
      $fileType = null;
      $fileUrl = null;

      if (isset($data['file']) && $data['file']) {
        $file = $data['file'];
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();

        // Guardar en DigitalOcean Spaces o storage local
        $filePath = $file->storeAs('vehicle-documents', $fileName, 'do_spaces');
        $fileUrl = Storage::disk('do_spaces')->url($filePath);
      }

      // 3. Crear el documento del vehículo
      $documentData = [
        'document_type' => $data['document_type'],
        'issuer_type' => $data['issuer_type'],
        'document_series' => $data['document_series'] ?? null,
        'document_number' => $data['document_number'] ?? null,
        'issue_date' => $data['issue_date'] ?? null,
        'requires_sunat' => $data['requires_sunat'] ?? false,
        'is_sunat_registered' => $data['is_sunat_registered'] ?? false,
        'vehicle_movement_id' => $vehicleMovement->id,
        'transmitter_id' => $data['transmitter_id'],
        'receiver_id' => $data['receiver_id'],
        'file_path' => $filePath,
        'file_name' => $fileName,
        'file_type' => $fileType,
        'file_url' => $fileUrl,
        'driver_doc' => $data['driver_doc'] ?? null,
        'company_name' => $data['company_name'] ?? null,
        'license' => $data['license'] ?? null,
        'plate' => $data['plate'] ?? null,
        'driver_name' => $data['driver_name'] ?? null,
        'notes' => $data['notes'] ?? null,
        'status' => $data['status'] ?? true,
        'transfer_reason_id' => $data['transfer_reason_id'] ?? null,
        'transfer_modality_id' => $data['transfer_modality_id'] ?? null,
      ];

      $document = ShippingGuides::create($documentData);

      // 4. Cargar relaciones y retornar
      return new ShippingGuidesResource($document->load([
        'vehicleMovement',
        'transmitter',
        'receiver',
        'transferModality',
        'transferReason'
      ]));
    });
  }

  public function show($id)
  {
    return new ShippingGuidesResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $document = $this->find($data['id']);

      // 1. Manejar la carga del archivo si existe
      if (isset($data['file']) && $data['file']) {
        // Eliminar archivo anterior si existe
        if ($document->file_path) {
          Storage::disk('do_spaces')->delete($document->file_path);
        }

        $file = $data['file'];
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();

        $filePath = $file->storeAs('vehicle-documents', $fileName, 'do_spaces');
        $fileUrl = Storage::disk('do_spaces')->url($filePath);

        $data['file_path'] = $filePath;
        $data['file_name'] = $fileName;
        $data['file_type'] = $fileType;
        $data['file_url'] = $fileUrl;
      }

      // 2. Actualizar el documento
      unset($data['id'], $data['file']);
      $document->update($data);

      // 3. Retornar con relaciones
      return new ShippingGuidesResource($document->fresh()->load([
        'vehicleMovement',
        'transmitter',
        'receiver',
        'transferModality',
        'transferReason'
      ]));
    });
  }

  public function destroy($id)
  {
    return DB::transaction(function () use ($id) {
      $document = $this->find($id);

      // Eliminar archivo si existe
      if ($document->file_path) {
        Storage::disk('do_spaces')->delete($document->file_path);
      }

      // Eliminar el documento (soft delete)
      $document->delete();

      return response()->json(['message' => 'Documento eliminado correctamente']);
    });
  }

  /**
   * Cancelar un documento
   */
  public function cancel($id, $cancellationReason)
  {
    return DB::transaction(function () use ($id, $cancellationReason) {
      $document = $this->find($id);

      $document->update([
        'cancellation_reason' => $cancellationReason,
        'cancelled_by' => Auth::id(),
        'cancelled_at' => now(),
        'status' => false,
      ]);

      return new ShippingGuidesResource($document->fresh()->load([
        'vehicleMovement',
        'transmitter',
        'receiver',
        'transferModality',
        'transferReason'
      ]));
    });
  }
}
