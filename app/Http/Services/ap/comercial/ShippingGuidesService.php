<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
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
      ShippingGuidesResource::class
    );
  }

  public function find($id)
  {
    $document = ShippingGuides::find($id);

    if (!$document) {
      throw new Exception('Documento no encontrado');
    }

    return $document;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // 1. Crear el vehicle_movement automáticamente
      $originAddress = BusinessPartnersEstablishment::find($data['transmitter_id'])->address ?? '-';
      $destinationAddress = BusinessPartnersEstablishment::find($data['receiver_id'])->address ?? '-';
      $statusCurrentVehicle = VehiclePurchaseOrder::find($data['ap_vehicle_purchase_order_id'])->ap_vehicle_status_id ?? null;

      $vehicleMovementData = [
        'ap_vehicle_purchase_order_id' => $data['ap_vehicle_purchase_order_id'],
        'movement_type' => 'TRAVESIA',
        'movement_date' => $data['issue_date'],
        'observation' => $data['notes'] ?? null,
        'origin_address' => $originAddress,
        'destination_address' => $destinationAddress,
        'previous_status_id' => $statusCurrentVehicle,
        'new_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
        'ap_vehicle_status_id' => $statusCurrentVehicle,
        'created_by' => Auth::id(),
      ];

      VehiclePurchaseOrder::find($data['ap_vehicle_purchase_order_id'])->update([
        'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      ]);

      $vehicleMovement = VehicleMovement::create($vehicleMovementData);

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

      // 3. Manejar series y correlativo según issuer_type
      $series = null;
      $correlative = null;
      $documentNumber = null;
      $documentSeriesId = null;

      if ($data['issuer_type'] == 'NOSOTROS') {
        // Validar que document_series_id sea obligatorio
        if (empty($data['document_series_id'])) {
          throw new Exception('El campo document_series_id es obligatorio cuando el emisor es AUTOMOTORES');
        }

        // Generar automáticamente la serie y correlativo
        $assignSeries = AssignSalesSeries::findOrFail($data['document_series_id']);
        $series = $assignSeries->series;
        $correlativeStart = $assignSeries->correlative_start;

        // Contar documentos existentes con la misma serie
        $existingCount = ShippingGuides::where('document_series_id', $data['document_series_id'])->count();
        $correlativeNumber = $correlativeStart + $existingCount;

        $correlative = str_pad($correlativeNumber, 8, '0', STR_PAD_LEFT);
        $documentNumber = $series . '-' . $correlative;
        $documentSeriesId = $data['document_series_id'];
      } elseif ($data['issuer_type'] == 'PROVEEDOR') {
        // Validar que series y correlative sean obligatorios
        if (empty($data['series'])) {
          throw new Exception('El campo series es obligatorio cuando el emisor es PROVEEDOR');
        }
        if (empty($data['correlative'])) {
          throw new Exception('El campo correlative es obligatorio cuando el emisor es PROVEEDOR');
        }

        // Usar los valores enviados por el cliente sin modificar
        $series = $data['series'];
        $correlative = $data['correlative'];
        $documentNumber = $series . '-' . $correlative;
        $documentSeriesId = null;
      }

      // 4. Crear la guía de remisión
      $documentData = [
        'document_type' => $data['document_type'],
        'issuer_type' => $data['issuer_type'],
        'document_series_id' => $documentSeriesId,
        'series' => $series,
        'correlative' => $correlative,
        'document_number' => $documentNumber,
        'issue_date' => $data['issue_date'],
        'requires_sunat' => $data['requires_sunat'] ?? false,
        'total_packages' => $data['total_packages'] ?? null,
        'total_weight' => $data['total_weight'] ?? null,
        'vehicle_movement_id' => $vehicleMovement->id,
        'sede_transmitter_id' => $data['sede_transmitter_id'],
        'sede_receiver_id' => $data['sede_receiver_id'],
        'transmitter_id' => $data['transmitter_id'],
        'receiver_id' => $data['receiver_id'],
        'file_path' => $filePath,
        'file_name' => $fileName,
        'file_type' => $fileType,
        'file_url' => $fileUrl,
        'transport_company_id' => $data['transport_company_id'] ?? null,
        'driver_doc' => $data['driver_doc'] ?? null,
        'license' => $data['license'] ?? null,
        'plate' => $data['plate'] ?? null,
        'driver_name' => $data['driver_name'] ?? null,
        'notes' => $data['notes'] ?? null,
        'status' => $data['status'] ?? true,
        'transfer_reason_id' => $data['transfer_reason_id'] ?? null,
        'transfer_modality_id' => $data['transfer_modality_id'] ?? null,
        'created_by' => Auth::id(), // Se establece automáticamente
      ];

      $document = ShippingGuides::create($documentData);

      return new ShippingGuidesResource($document);
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

      // 2. Remover campos que no se pueden actualizar
      unset(
        $data['id'],
        $data['file'],
        $data['document_series'], // Generado por la API
        $data['document_number'], // Generado por la API
        $data['is_sunat_registered'], // Se procesa con nubefac
        $data['created_by'], // No se puede modificar el creador
        $data['cancellation_reason'], // Solo se actualiza con el método cancel()
        $data['cancelled_by'], // Solo se actualiza con el método cancel()
        $data['cancelled_at'] // Solo se actualiza con el método cancel()
      );

      $document->update($data);

      return new ShippingGuidesResource($document);
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

      return new ShippingGuidesResource($document);
    });
  }
}
