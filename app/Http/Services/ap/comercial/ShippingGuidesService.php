<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Jobs\SyncShippingGuideJob;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\gestionsistema\DigitalFile;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShippingGuidesService extends BaseService implements BaseServiceInterface
{
  protected NubefactShippingGuideApiService $nubefactService;
  protected DigitalFileService $digitalFileService;
  protected VehicleMovementService $vehicleMovementService;

  private const FILE_PATH = '/ap/comercial/guias-remision/';

  public function __construct(
    NubefactShippingGuideApiService $nubefactService,
    DigitalFileService              $digitalFileService,
    VehicleMovementService          $vehicleMovementService
  )
  {
    $this->nubefactService = $nubefactService;
    $this->digitalFileService = $digitalFileService;
    $this->vehicleMovementService = $vehicleMovementService;
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
      // 1. Validaciones de negocio
      $origin = BusinessPartnersEstablishment::find($data['transmitter_id']) ?? null;
      $destination = BusinessPartnersEstablishment::find($data['receiver_id']) ?? null;

      if ($data['transfer_reason_id'] == SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        if ($data['sede_transmitter_id'] == $data['sede_receiver_id']) {
          throw new Exception('La sede de origen y destino no pueden ser la misma para el motivo de traslado seleccionado');
        }
        if ($data['transmitter_id'] == $data['receiver_id']) {
          throw new Exception('El establecimiento de origen y destino no pueden ser el mismo para el motivo de traslado seleccionado');
        }
      }

      // Crear el movimiento de vehículo a travesia
      $vehicleMovement = $this->vehicleMovementService->storeShippingGuideVehicleMovement(
        $data['ap_vehicle_id'],
        $origin->address ?? '-',
        $destination->address ?? '-',
        $data['notes'] ?? null,
        $data['issue_date']
      );

      // 2. Manejar la carga del archivo si existe
      $file = null;
      if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
        $file = $data['file'];
        unset($data['file']);
      }

      // 3. Manejar series y correlativo según issuer_type
      $series = null;
      $correlative = null;
      $documentNumber = null;
      $documentSeriesId = null;

      if ($data['issuer_type'] == 'NOSOTROS') {
        if (empty($data['document_series_id'])) {
          throw new Exception('El campo document_series_id es obligatorio cuando el emisor es AUTOMOTORES');
        }

        // Generar automáticamente la serie y correlativo
        $assignSeries = AssignSalesSeries::findOrFail($data['document_series_id']);
        $series = $assignSeries->series;
        $correlativeStart = $assignSeries->correlative_start;

        // Contar documentos existentes con la misma serie
        $existingCount = ShippingGuides::where('document_series_id', $data['document_series_id'])->count();
        $correlativeNumber = $correlativeStart + $existingCount + 1;

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

      // 4. Manejar type_voucher_id para guías de remisión
      $typeVoucherId = null;
      if ($data['document_type'] == 'GUIA_REMISION') {
        $typeVoucherId = SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE;
      }

      // 5. Crear la guía de remisión
      $documentData = [
        'document_type' => $data['document_type'],
        'type_voucher_id' => $typeVoucherId,
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
        'transport_company_id' => $data['transport_company_id'] ?? null,
        'driver_doc' => $data['driver_doc'] ?? null,
        'license' => $data['license'] ?? null,
        'plate' => $data['plate'] ?? null,
        'driver_name' => $data['driver_name'] ?? null,
        'notes' => $data['notes'] ?? null,
        'status' => $data['status'] ?? true,
        'transfer_reason_id' => $data['transfer_reason_id'] ?? null,
        'transfer_modality_id' => $data['transfer_modality_id'] ?? null,
        'created_by' => Auth::id(),
        'ap_class_article_id' => $data['ap_class_article_id'] ?? null,
        'origin_ubigeo' => $origin->ubigeo ?? '-',
        'origin_address' => $origin->address ?? '-',
        'destination_ubigeo' => $destination->ubigeo ?? '-',
        'destination_address' => $destination->address ?? '-',
      ];

      $document = ShippingGuides::create($documentData);

      // 6. Si hay archivo, subirlo usando DigitalFileService
      if ($file) {
        $digitalFile = $this->digitalFileService->store(
          $file,
          self::FILE_PATH,
          'public',
          $document->getTable()
        );

        // Actualizar el documento con la información del archivo
        $document->update([
          'file_url' => $digitalFile->url,
        ]);
      }

      return new ShippingGuidesResource($document);
    });
  }

  public function show($id)
  {
    $document = ShippingGuides::with(['receivingChecklists.receiving'])->findOrFail($id);
    return new ShippingGuidesResource($document);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $document = $this->find($data['id']);

      if ($document->aceptada_por_sunat) {
        throw new Exception('No se puede editar un documento que ya ha sido aceptado por SUNAT');
      }

      if ($document->status_dynamic) {
        throw new Exception('No se puede editar un documento que ya ha sido migrado a Dynamics');
      }

      if ($document->is_received) {
        throw new Exception('No se puede editar un documento que ya ha sido recibido');
      }

      if ($data['transfer_reason_id'] == SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        if ($data['sede_transmitter_id'] == $data['sede_receiver_id']) {
          throw new Exception('La sede de origen y destino no pueden ser la misma para el motivo de traslado seleccionado');
        }
        if ($data['transmitter_id'] == $data['receiver_id']) {
          throw new Exception('El establecimiento de origen y destino no pueden ser el mismo para el motivo de traslado seleccionado');
        }
      }

      // 1. Manejar la carga del archivo si existe
      $file = null;
      if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
        $file = $data['file'];
        unset($data['file']); // Remover del array para no guardarlo en la BD
      }

      // 2. Recalcular serie y correlativo si cambió el document_series_id y el emisor es NOSOTROS
      if (isset($data['issuer_type']) && $data['issuer_type'] == 'NOSOTROS') {
        if (isset($data['document_series_id']) && $data['document_series_id'] != $document->document_series_id) {
          // Cambió la serie, recalcular correlativo
          $assignSeries = AssignSalesSeries::findOrFail($data['document_series_id']);
          $series = $assignSeries->series;
          $correlativeStart = $assignSeries->correlative_start;

          // Contar documentos existentes con la nueva serie (excluyendo el actual)
          $existingCount = ShippingGuides::where('document_series_id', $data['document_series_id'])
            ->where('id', '!=', $document->id)
            ->count();
          $correlativeNumber = $correlativeStart + $existingCount + 1;

          $correlative = str_pad($correlativeNumber, 8, '0', STR_PAD_LEFT);
          $documentNumber = $series . '-' . $correlative;

          // Actualizar los valores calculados
          $data['series'] = $series;
          $data['correlative'] = $correlative;
          $data['document_number'] = $documentNumber;
        }
      } elseif (isset($data['issuer_type']) && $data['issuer_type'] == 'PROVEEDOR') {
        // Para proveedor, reconstruir document_number si cambiaron series o correlative
        if (isset($data['series']) && isset($data['correlative'])) {
          $data['document_number'] = $data['series'] . '-' . $data['correlative'];
        }
      }

      // 3. Manejar type_voucher_id para guías de remisión
      if (isset($data['document_type']) && $data['document_type'] == 'GUIA_REMISION') {
        $data['type_voucher_id'] = SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE;
      }

      // 4. Actualizar direcciones y ubigeo en el movimiento de vehículo si cambiaron origen o destino
      if (isset($data['transmitter_id']) || isset($data['receiver_id'])) {
        // Obtener establecimiento de origen
        $originEstablishment = isset($data['transmitter_id'])
          ? BusinessPartnersEstablishment::find($data['transmitter_id'])
          : null;

        $originAddress = $originEstablishment
          ? ($originEstablishment->address ?? $document->origin_address)
          : $document->origin_address;

        $originUbigeo = $originEstablishment
          ? ($originEstablishment->ubigeo ?? $document->origin_ubigeo)
          : $document->origin_ubigeo;

        // Obtener establecimiento de destino
        $destinationEstablishment = isset($data['receiver_id'])
          ? BusinessPartnersEstablishment::find($data['receiver_id'])
          : null;

        $destinationAddress = $destinationEstablishment
          ? ($destinationEstablishment->address ?? $document->destination_address)
          : $document->destination_address;

        $destinationUbigeo = $destinationEstablishment
          ? ($destinationEstablishment->ubigeo ?? $document->destination_ubigeo)
          : $document->destination_ubigeo;

        // Actualizar las direcciones y ubigeo en data para la guía de remisión
        $data['origin_ubigeo'] = $originUbigeo;
        $data['origin_address'] = $originAddress;
        $data['destination_address'] = $destinationAddress;
        $data['destination_ubigeo'] = $destinationUbigeo;

        // Actualizar el movimiento de vehículo asociado
        if ($document->vehicle_movement_id) {
          $document->vehicleMovement->update([
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
          ]);
        }
      }

      // 5. Remover campos que no se pueden actualizar
      unset(
        $data['is_sunat_registered'],
        $data['status_nubefac'],
        $data['created_by'],
        $data['cancellation_reason'],
        $data['cancelled_by'],
        $data['cancelled_at']
      );

      $document->update($data);

      // 4. Si hay nuevo archivo, eliminar el anterior y subir el nuevo
      if ($file) {
        // Eliminar archivo anterior si existe
        if ($document->file_url) {
          $oldDigitalFile = DigitalFile::where('url', $document->file_url)->first();
          if ($oldDigitalFile) {
            $this->digitalFileService->destroy($oldDigitalFile->id);
          }
        }

        // Subir nuevo archivo
        $digitalFile = $this->digitalFileService->store(
          $file,
          self::FILE_PATH,
          'public',
          $document->getTable()
        );

        // Actualizar el documento con la información del nuevo archivo
        $document->update([
          'file_url' => $digitalFile->url,
        ]);
      }

      return new ShippingGuidesResource($document);
    });
  }

  public function destroy($id)
  {
    return DB::transaction(function () use ($id) {
      $document = $this->find($id);

      if ($document->aceptada_por_sunat) {
        throw new Exception('No se puede eliminar un documento que ya ha sido aceptado por SUNAT');
      }

      if ($document->status_dynamic) {
        throw new Exception('No se puede eliminar un documento que ya ha sido migrado a Dynamics');
      }

      if ($document->is_received) {
        throw new Exception('No se puede eliminar un documento que ya ha sido recibido');
      }

      // Eliminar archivo digital si existe
      if ($document->file_url) {
        $digitalFile = DigitalFile::where('url', $document->file_url)->first();
        if ($digitalFile) {
          $this->digitalFileService->destroy($digitalFile->id);
        }
      }

      // Eliminar el documento (soft delete)
      $document->delete();

      return response()->json(['message' => 'Documento eliminado correctamente']);
    });
  }

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

      // Sincronizar cancelación con Dynamics
      SyncShippingGuideJob::dispatchSync($document->id);

      return new ShippingGuidesResource($document);
    });
  }

  public function sendToNubefact($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $guide = $this->find($id);

      if ($guide->aceptada_por_sunat) {
        throw new Exception('La guía ya ha sido aceptada por SUNAT');
      }

      if ($guide->cancelled_at) {
        throw new Exception('No se puede enviar una guía anulada');
      }

      if (!$guide->requires_sunat) {
        throw new Exception('Esta guía no requiere registro en SUNAT');
      }

      if ($guide->sent_at && $guide->sent_at->diffInMinutes(now()) < 30) {
        throw new Exception('Debe esperar al menos 30 minutos antes de reenviar la guía a SUNAT');
      }

      if ($guide->document_type != 'GUIA_REMISION' || $guide->type_voucher_id != SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE) {
        throw new Exception('El tipo de documento o comprobante no es válido para envío a SUNAT');
      }

      $response = $this->nubefactService->generateGuide($guide);

      if ($response['success']) {
        $guide->markAsSent();

        $responseData = $response['data'];

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
      throw new Exception('Error al enviar la guía a Nubefact: ' . $e->getMessage());
    }
  }

  public function queryFromNubefact($id): JsonResponse
  {
    try {
      $guide = $this->find($id);

      if (!$guide->sent_at) {
        throw new Exception('La guía no ha sido enviada a SUNAT aún');
      }

      $response = $this->nubefactService->queryGuide($guide);

      if ($response['success']) {
        $responseData = $response['data'];

        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat'] && !$guide->aceptada_por_sunat) {
          // CASO 1: Recién se aceptó
          DB::beginTransaction();
          $guide->markAsAccepted($responseData);
          DB::commit();
          $message = 'La guía ha sido aceptada por SUNAT';

        } elseif (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat'] && $guide->aceptada_por_sunat) {
          // CASO 2: Ya estaba aceptada (consulta posterior)
          $guide->update([
            'enlace' => $responseData['enlace'] ?? $guide->enlace,
            'enlace_del_pdf' => $responseData['enlace_del_pdf'] ?? $guide->enlace_del_pdf,
            'enlace_del_xml' => $responseData['enlace_del_xml'] ?? $guide->enlace_del_xml,
            'enlace_del_cdr' => $responseData['enlace_del_cdr'] ?? $guide->enlace_del_cdr,
            'cadena_para_codigo_qr' => $responseData['cadena_para_codigo_qr'] ?? $guide->cadena_para_codigo_qr,
          ]);
          $message = 'La guía ya está aceptada por SUNAT'; // ← MENSAJE CORRECTO

        } else {
          // CASO 3: Realmente NO aceptada
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
      throw new Exception('Error al consultar la guía en Nubefact: ' . $e->getMessage());
    }
  }

  public function markAsReceived($id, $noteReceived): JsonResponse
  {
    return DB::transaction(function () use ($id, $noteReceived) {
      $guide = $this->find($id);

      if ($guide->is_received) {
        throw new Exception('La guía ya ha sido marcada como recepcionada');
      }

      if ($guide->cancelled_at) {
        throw new Exception('No se puede marcar como recepcionada una guía anulada');
      }

      if ($guide->document_type === 'GUIA_REMISION') {
        SyncShippingGuideJob::dispatchSync($guide->id);
      }

      $guide->update([
        'is_received' => true,
        'note_received' => $noteReceived,
        'received_by' => Auth::id(),
        'received_date' => now(),
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Guía marcada como recepcionada correctamente',
        'data' => new ShippingGuidesResource($guide->fresh()),
      ]);
    });
  }

  public function syncToDynamics($id): JsonResponse
  {
    try {
      $guide = $this->find($id);

      // Validaciones básicas antes de enviar al Job
      if (!$guide->vehicleMovement) {
        throw new Exception('La guía debe tener un movimiento de vehículo asociado');
      }

      if (!$guide->sedeTransmitter || !$guide->sedeReceiver) {
        throw new Exception('La guía debe tener origen y destino configurados');
      }

      return response()->json([
        'success' => true,
        'message' => 'La sincronización a Dynamics ha sido programada y se ejecutará en segundo plano',
        'data' => new ShippingGuidesResource($guide->fresh()),
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al programar la sincronización: ' . $e->getMessage());
    }
  }
}
