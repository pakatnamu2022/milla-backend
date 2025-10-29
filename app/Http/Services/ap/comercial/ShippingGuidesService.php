<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShippingGuidesService extends BaseService implements BaseServiceInterface
{
  protected NubefactShippingGuideApiService $nubefactService;

  public function __construct(NubefactShippingGuideApiService $nubefactService)
  {
    $this->nubefactService = $nubefactService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ShippingGuides::class,
      $request,
      ShippingGuides::filters,
      ShippingGuides::sorts,
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
      $statusCurrentVehicle = Vehicles::find($data['ap_vehicle_id'])->ap_vehicle_status_id ?? null;

      $vehicleMovementData = [
        'ap_vehicle_id' => $data['ap_vehicle_id'],
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

      Vehicles::find($data['ap_vehicle_id'])->update([
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

      if ($data['issuer_type'] == 'PROVEEDOR') {
        $data['document_number'] = $data['series'] . '-' . $data['correlative'];
      }

      // 2. Remover campos que no se pueden actualizar
      unset(
        $data['is_sunat_registered'], // Se procesa con sunat
        $data['status_nubefac'], // Se procesa con nubefac
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

  /**
   * Envía la guía de remisión a SUNAT mediante Nubefact
   * @throws Exception
   */
  public function sendToNubefact($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $guide = $this->find($id);

      // Validar que la guía esté en estado correcto
      if ($guide->aceptada_por_sunat) {
        throw new Exception('La guía ya ha sido aceptada por SUNAT');
      }

      if ($guide->cancelled_at) {
        throw new Exception('No se puede enviar una guía anulada');
      }

      if (!$guide->requires_sunat) {
        throw new Exception('Esta guía no requiere registro en SUNAT');
      }

      // Marcar como enviado
      $guide->markAsSent();

      // Enviar a Nubefact
      $response = $this->nubefactService->generateGuide($guide);

      // Procesar respuesta
      if ($response['success']) {
        $responseData = $response['data'];

        // Actualizar con la respuesta de Nubefact
        $guide->update([
          'enlace' => $responseData['enlace'] ?? null,
          'enlace_del_pdf' => $responseData['enlace_del_pdf'] ?? null,
          'enlace_del_xml' => $responseData['enlace_del_xml'] ?? null,
          'enlace_del_cdr' => $responseData['enlace_del_cdr'] ?? null,
          'cadena_para_codigo_qr' => $responseData['cadena_para_codigo_qr'] ?? null,
          'sunat_description' => $responseData['sunat_description'] ?? null,
          'sunat_note' => $responseData['sunat_note'] ?? null,
          'sunat_responsecode' => $responseData['sunat_responsecode'] ?? null,
          'sunat_soap_error' => $responseData['sunat_soap_error'] ?? null,
        ]);

        // Verificar si fue aceptada por SUNAT (puede ser false inicialmente)
        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat']) {
          $guide->markAsAccepted($responseData);
          $message = 'Guía enviada y aceptada por SUNAT correctamente';
        } else {
          $message = 'Guía enviada a Nubefact. Use la operación de consulta para verificar si SUNAT la aceptó.';
        }
      } else {
        $errorMessage = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
        $guide->markAsRejected($errorMessage, $response['data'] ?? []);
        $message = 'Error al enviar la guía: ' . $errorMessage;
      }

      DB::commit();

      return response()->json([
        'success' => $response['success'],
        'message' => $message,
        'data' => new ShippingGuidesResource($guide->fresh()),
        'nubefact_response' => $response['data'] ?? null
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error sending shipping guide to Nubefact', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al enviar la guía a Nubefact: ' . $e->getMessage());
    }
  }

  /**
   * Consulta el estado de la guía en Nubefact/SUNAT
   * @throws Exception
   */
  public function queryFromNubefact($id): JsonResponse
  {
    try {
      $guide = $this->find($id);

      if (!$guide->sent_at) {
        throw new Exception('La guía no ha sido enviada a SUNAT aún');
      }

      $response = $this->nubefactService->queryGuide($guide);

      // Actualizar estado si cambió
      if ($response['success']) {
        $responseData = $response['data'];

        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat'] && !$guide->aceptada_por_sunat) {
          DB::beginTransaction();
          $guide->markAsAccepted($responseData);
          DB::commit();
          $message = 'La guía ha sido aceptada por SUNAT';
        } else {
          // Actualizar los enlaces aunque no esté aceptada aún
          $guide->update([
            'enlace' => $responseData['enlace'] ?? $guide->enlace,
            'enlace_del_pdf' => $responseData['enlace_del_pdf'] ?? $guide->enlace_del_pdf,
            'enlace_del_xml' => $responseData['enlace_del_xml'] ?? $guide->enlace_del_xml,
            'enlace_del_cdr' => $responseData['enlace_del_cdr'] ?? $guide->enlace_del_cdr,
            'cadena_para_codigo_qr' => $responseData['cadena_para_codigo_qr'] ?? $guide->cadena_para_codigo_qr,
          ]);
          $message = 'Estado consultado. La guía aún no ha sido aceptada por SUNAT.';
        }
      } else {
        $message = 'Error al consultar: ' . ($response['error'] ?? 'Error desconocido');
      }

      return response()->json([
        'success' => $response['success'],
        'message' => $message,
        'data' => new ShippingGuidesResource($guide->fresh()),
        'nubefact_response' => $response['data'] ?? null
      ]);
    } catch (Exception $e) {
      Log::error('Error querying shipping guide from Nubefact', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al consultar la guía en Nubefact: ' . $e->getMessage());
    }
  }
}
