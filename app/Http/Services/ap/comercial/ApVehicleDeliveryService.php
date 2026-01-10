<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApVehicleDeliveryResource;
use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Jobs\VerifyAndMigrateShippingGuideJob;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\UserSeriesAssignment;
use App\Models\gp\gestionsistema\UserSede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApVehicleDeliveryService extends BaseService implements BaseServiceInterface
{
  protected NubefactShippingGuideApiService $nubefactService;
  protected VehicleMovementService $vehicleMovementService;
  protected VehiclesService $vehiclesService;

  public function __construct(
    NubefactShippingGuideApiService $nubefactService,
    VehicleMovementService          $vehicleMovementService,
    VehiclesService                 $vehiclesService
  )
  {
    $this->nubefactService = $nubefactService;
    $this->vehicleMovementService = $vehicleMovementService;
    $this->vehiclesService = $vehiclesService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleDelivery::class,
      $request,
      ApVehicleDelivery::filters,
      ApVehicleDelivery::sorts,
      ApVehicleDeliveryResource::class,
    );
  }

  public function find($id)
  {
    $vehicleDelivery = ApVehicleDelivery::where('id', $id)->first();
    if (!$vehicleDelivery) {
      throw new Exception('Entrega de Vehículo no encontrado');
    }
    return $vehicleDelivery;
  }

  public function store(mixed $data)
  {
    try {
      DB::transaction(function () use ($data) {
        $user = auth()->user();
        $data['advisor_id'] = $user->partner_id;

        if (!$data['advisor_id']) {
          throw new Exception('El asesor no está asociado a un socio válido');
        }

        $existingDelivery = ApVehicleDelivery::where('vehicle_id', $data['vehicle_id'])
          ->where('scheduled_delivery_date', $data['scheduled_delivery_date'])
          ->first();
        if ($existingDelivery) {
          throw new Exception('Ya existe una entrega programada para este vehículo en la misma fecha');
        }

        if (isset($data['wash_date']) && $data['wash_date'] > $data['scheduled_delivery_date']) {
          throw new Exception('La fecha de lavado no puede ser mayor a la fecha de entrega programada');
        }

        // Obtener el documento electrónico y cliente usando el método centralizado
        $documentData = Vehicles::getElectronicDocumentWithClient($data['vehicle_id']);
        $data['client_id'] = $documentData->client->id;

        $vehicleDelivery = ApVehicleDelivery::create($data);
        $vehicle = Vehicles::find($data['vehicle_id']);

        if (!$vehicle) {
          throw new Exception('Vehículo no encontrado');
        }

        // creamos el movimiento de vehículo asociado
        $vehicleMovement = $this->vehicleMovementService->storeScheduleDeliveryVehicleMovement($vehicle);
        $vehicleDelivery->update(['vehicle_movement_id' => $vehicleMovement->id]);

        return new ApVehicleDeliveryResource($vehicleDelivery);
      });
    } catch (Exception $e) {
      throw new Exception('Error al crear la entrega de vehículo: ' . $e->getMessage());
    }
  }

  public function show($id)
  {
    $vehicleDelivery = ApVehicleDelivery::with('ShippingGuide')->find($id);
    if (!$vehicleDelivery) {
      throw new Exception('Entrega de Vehículo no encontrado');
    }
    return new ApVehicleDeliveryResource($vehicleDelivery);
  }

  public function update(mixed $data)
  {
    $vehicleDelivery = $this->find($data['id']);

    //validamos que si ya esta completado no se pueda cambiar la fecha de lavado ni de entrega
    if ($vehicleDelivery->status_wash === 'completed' && isset($data['wash_date']) && $data['wash_date'] !== $vehicleDelivery->wash_date) {
      throw new Exception('No se puede cambiar la fecha de lavado de un vehículo que ya ha sido lavado');
    }

    //validamos que si ya esta completado no se pueda cambiar la fecha de entrega
    if ($vehicleDelivery->status_delivery === 'completed' && isset($data['scheduled_delivery_date']) && $data['scheduled_delivery_date'] !== $vehicleDelivery->scheduled_delivery_date) {
      throw new Exception('No se puede cambiar la fecha de entrega de un vehículo que ya ha sido entregado');
    }

    // Si status_wash cambia a completed, setear real_wash_date
    if (isset($data['status_wash']) && $data['status_wash'] === 'completed') {
      $data['real_wash_date'] = now();
    }

    // Si status_delivery cambia a completed, setear real_delivery_date
    if (isset($data['status_delivery']) && $data['status_delivery'] === 'completed') {
      $data['real_delivery_date'] = now();
    }

    $vehicleDelivery->update($data);
    return new ApVehicleDeliveryResource($vehicleDelivery);
  }

  public function destroy($id)
  {
    $vehicleDelivery = $this->find($id);

    if ($vehicleDelivery->shipping_guide_id) {
      throw new Exception('No se puede eliminar una entrega que tiene una guía de remisión asociada');
    }

    DB::transaction(function () use ($vehicleDelivery) {
      $vehicleDelivery->delete();
    });
    return response()->json(['message' => 'Entrega de Vehículo eliminada correctamente']);
  }

  public function generateShippingGuide($id, $data = [])
  {
    try {
      return DB::transaction(function () use ($id, $data) {
        $record = $this->find($id);

        if (!$record) {
          throw new Exception('Entrega de Vehículo no encontrada');
        }

        // Verificar si ya existe una guía de remisión
        $existingShippingGuide = null;
        if ($record->shipping_guide_id) {
          $existingShippingGuide = ShippingGuides::find($record->shipping_guide_id);
        }

        // Si existe una guía, solo actualizar los campos permitidos
        if ($existingShippingGuide) {
          // Validar que la guía no haya sido enviada a SUNAT
          if ($existingShippingGuide->is_sunat_registered) {
            throw new Exception('No se puede modificar una guía que ya fue enviada a SUNAT');
          }

          // Validar que la guía no esté anulada
          if ($existingShippingGuide->cancelled_at) {
            throw new Exception('No se puede modificar una guía anulada');
          }

          // Actualizar solo los campos permitidos
          $updateData = [];

          if (isset($data['driver_doc'])) {
            $updateData['driver_doc'] = $data['driver_doc'];
            // Si cambia el documento del conductor, buscar la transportista
            $transportCompanyId = BusinessPartners::where('num_doc', $data['driver_doc'])
              ->first()->id ?? null;
            $updateData['transport_company_id'] = $transportCompanyId;
          }

          if (isset($data['license'])) {
            $updateData['license'] = $data['license'];
          }

          if (isset($data['plate'])) {
            $updateData['plate'] = $data['plate'];
          }

          if (isset($data['driver_name'])) {
            $updateData['driver_name'] = $data['driver_name'];
          }

          if (isset($data['enviar_sunat'])) {
            $updateData['requires_sunat'] = $data['enviar_sunat'];
          }

          if (isset($data['transfer_modality_id'])) {
            $updateData['transfer_modality_id'] = $data['transfer_modality_id'];
          }

          if (isset($data['carrier_ruc'])) {
            $updateData['ruc_transport'] = $data['carrier_ruc'];
          }

          if (isset($data['company_name_transport'])) {
            $updateData['company_name_transport'] = $data['company_name_transport'];
          }

          $existingShippingGuide->update($updateData);

          return new ShippingGuidesResource($existingShippingGuide->fresh());
        }

        // Si no existe, crear una nueva guía (código original)
        $userId = auth()->id();
        $sedeId = $record->sede_id;

        // 1. Validar que el usuario tenga permisos para la sede
        $userSede = UserSede::where('user_id', $userId)
          ->where('sede_id', $sedeId)
          ->where('status', true)
          ->first();

        if (!$userSede) {
          throw new Exception('No tiene permisos para generar guías de remisión en esta sede');
        }

        // 2. Buscar la serie asignada al usuario para Guía de Remisión Remitente
        $userSeriesAssignment = UserSeriesAssignment::where('worker_id', $userId)
          ->whereHas('voucher', function ($query) use ($sedeId) {
            $query->where('type_receipt_id', AssignSalesSeries::GUIA_REMISION)
              ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
              ->where('sede_id', $sedeId)
              ->where('status', true);
          })
          ->with('voucher')
          ->first();

        if (!$userSeriesAssignment || !$userSeriesAssignment->voucher) {
          throw new Exception('No tiene una serie asignada para emitir guías de remisión en esta sede');
        }

        $assignSeries = $userSeriesAssignment->voucher;
        $series = $assignSeries->series;
        $documentSeriesId = $assignSeries->id;

        // Generar el siguiente correlativo usando el método centralizado
        $nextCorrelative = ShippingGuides::generateNextCorrelative(
          $documentSeriesId,
          $assignSeries->correlative_start
        );

        $correlative = $nextCorrelative['correlative'];
        $documentNumber = $series . '-' . $correlative;

        $driverDoc = $data['driver_doc'];
        $transportCompanyId = BusinessPartners::where('num_doc', $driverDoc)
          ->first()->id ?? null;

        // Obtener el vehículo para validar el pago
        $vehicle = Vehicles::find($record->vehicle_id);
        $vehicleId = $record->vehicle_id;

        // Validar si el vehículo está completamente pagado usando el método centralizado
        $isPaid = Vehicles::isVehiclePaid($vehicleId);

        if (!$isPaid) {
          throw new Exception('El vehículo no está completamente pagado. No se puede generar la guía de remisión.');
        }

        // Obtener el documento electrónico y cliente usando el método centralizado
        $documentData = Vehicles::getElectronicDocumentWithClient($vehicleId);
        $client = $documentData->client;
        $originEstablishment = BusinessPartnersEstablishment::where('sede_id', $record->sede_id)->first();

        if (!$originEstablishment) {
          throw new Exception('No se encontró establecimiento de origen');
        }

        $originUbigeo = $originEstablishment->ubigeo;
        $originAddress = $originEstablishment->address;
        $destinationUbigeo = $client->district->ubigeo;
        $destinationAddress = $client->direction;

        // Creamos el movimiento de vehículo de entrega completada
        $vehicleMovement = $this->vehicleMovementService->storeCompletedDeliveryVehicleMovement($vehicle, $originAddress, $destinationAddress);

        // Crear la guía de remisión
        $shippingGuideData = [
          'document_type' => 'GUIA_REMISION',
          'type_voucher_id' => SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE,
          'issuer_type' => 'NOSOTROS',
          'document_series_id' => $documentSeriesId,
          'series' => $series,
          'correlative' => $correlative,
          'document_number' => $documentNumber,
          'issue_date' => now(),
          'requires_sunat' => true,
          'vehicle_movement_id' => $vehicleMovement->id,
          'sede_transmitter_id' => $record->sede_id,
          'sede_receiver_id' => $record->sede_id,
          'transmitter_id' => $originEstablishment->id,
          'receiver_id' => $originEstablishment->id,
          'transport_company_id' => $transportCompanyId,
          'driver_doc' => $data['driver_doc'],
          'driver_name' => $data['driver_name'],
          'license' => $data['license'] ?? null,
          'plate' => $data['plate'] ?? '',
          'notes' => $data['notes'] ?? 'ENTREGA DE VEHÍCULO VENDIDO',
          'status' => true,
          'transfer_reason_id' => SunatConcepts::TRANSFER_REASON_VENTA,
          'transfer_modality_id' => $data['transfer_modality_id'],
          'created_by' => auth()->id(),
          'ap_class_article_id' => $record->ap_class_article_id,
          'origin_ubigeo' => $originUbigeo,
          'origin_address' => $originAddress,
          'destination_ubigeo' => $destinationUbigeo,
          'destination_address' => $destinationAddress,
          'ruc_transport' => $data['carrier_ruc'] ?? null,
          'company_name_transport' => $data['company_name_transport'] ?? null,
          'net_weight' => 1,
          'total_weight' => preg_replace('/[^0-9.]/', '', $vehicle->model->gross_weight),
        ];

        $shippingGuide = ShippingGuides::create($shippingGuideData);
        $record->update(['shipping_guide_id' => $shippingGuide->id]);

        return new ShippingGuidesResource($shippingGuide);
      });
    } catch (Exception $e) {
      throw new Exception('Error al generar la guía de remisión: ' . $e->getMessage());
    }
  }

  public function sendToNubefact($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $vehicleDelivery = $this->find($id);

      if ($vehicleDelivery->status_delivery === 'completed') {
        throw new Exception('La entrega ya ha sido completada, no se puede enviar la guía');
      }

      if ($vehicleDelivery->status_wash === 'pending') {
        throw new Exception('El vehículo no ha sido lavado aún, no se puede enviar la guía');
      }

      $shippingGuide = ShippingGuides::find($vehicleDelivery->shipping_guide_id);

      if (!$shippingGuide) {
        throw new Exception('No se encontró una guía de remisión asociada a esta entrega');
      }

      if (!$shippingGuide->requires_sunat) {
        throw new Exception('Esta guía no requiere registro en SUNAT');
      }

      if ($shippingGuide->aceptada_por_sunat) {
        throw new Exception('La guía ya ha sido aceptada por SUNAT');
      }

      $response = $this->nubefactService->generateGuide($shippingGuide);

      if ($response['success']) {
        // Marcar como enviado
        $shippingGuide->markAsSent();

        $responseData = $response['data'];

        $shippingGuide->update([
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

        // Verificar si fue aceptada por SUNAT
        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat']) {
          $shippingGuide->markAsAccepted($responseData);
          $vehicleDelivery->update([
            'status_nubefact' => true,
            'status_sunat' => true,
            'real_delivery_date' => now()
          ]);
          $message = 'Guía enviada y aceptada por SUNAT correctamente';
        } else {
          $vehicleDelivery->update(['status_nubefact' => true]);
          $message = 'Guía enviada a Nubefact. Use la operación de consulta para verificar si SUNAT la aceptó.';
        }
      } else {
        $errorMessage = is_array($response['error']) ? json_encode($response['error']) : $response['error'];
        $shippingGuide->markAsRejected($errorMessage, $response['data'] ?? []);
        $message = 'Error al enviar la guía: ' . $errorMessage;
      }

      DB::commit();

      return response()->json([
        'success' => $response['success'],
        'message' => $message,
        'data' => new ApVehicleDeliveryResource($vehicleDelivery->fresh()),
        'shipping_guide' => new ShippingGuidesResource($shippingGuide->fresh()),
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
      $vehicleDelivery = $this->find($id);

      // Buscar la guía de remisión asociada
      $shippingGuide = ShippingGuides::find($vehicleDelivery->shipping_guide_id);

      if (!$shippingGuide) {
        throw new Exception('No se encontró una guía de remisión asociada a esta entrega');
      }

      if (!$shippingGuide->sent_at) {
        throw new Exception('La guía no ha sido enviada a SUNAT aún');
      }

      $response = $this->nubefactService->queryGuide($shippingGuide);

      // Actualizar estado si cambió
      if ($response['success']) {
        $responseData = $response['data'];

        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat'] && !$shippingGuide->aceptada_por_sunat) {
          DB::beginTransaction();
          $shippingGuide->markAsAccepted($responseData);
          $vehicleDelivery->update(['status_sunat' => true, 'real_delivery_date' => now()]);
          DB::commit();
          $message = 'La guía ha sido aceptada por SUNAT';
        } else {
          // Actualizar los enlaces aunque no esté aceptada aún
          $shippingGuide->update([
            'enlace' => $responseData['enlace'] ?? $shippingGuide->enlace,
            'enlace_del_pdf' => $responseData['enlace_del_pdf'] ?? $shippingGuide->enlace_del_pdf,
            'enlace_del_xml' => $responseData['enlace_del_xml'] ?? $shippingGuide->enlace_del_xml,
            'enlace_del_cdr' => $responseData['enlace_del_cdr'] ?? $shippingGuide->enlace_del_cdr,
          ]);
          $message = 'Estado de la guía consultado correctamente';
        }
      } else {
        $message = 'Error al consultar la guía: ' . ($response['error'] ?? 'Error desconocido');
      }

      return response()->json([
        'success' => $response['success'],
        'message' => $message,
        'data' => new ApVehicleDeliveryResource($vehicleDelivery->fresh()),
        'shipping_guide' => new ShippingGuidesResource($shippingGuide->fresh()),
        'nubefact_response' => $response['data'] ?? null
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al consultar la guía en Nubefact: ' . $e->getMessage());
    }
  }

  public function sendToDynamic($id): JsonResponse
  {
    try {
      $vehicleDelivery = $this->find($id);

      // Validar que exista una guía de remisión asociada
      if (!$vehicleDelivery->shipping_guide_id) {
        throw new Exception('No se encontró una guía de remisión asociada a esta entrega');
      }

      $shippingGuide = ShippingGuides::find($vehicleDelivery->shipping_guide_id);

      if (!$shippingGuide) {
        throw new Exception('No se encontró una guía de remisión asociada a esta entrega');
      }

      // Validar que la guía esté aceptada por SUNAT (opcional, dependiendo de tus reglas de negocio)
      if (!$shippingGuide->aceptada_por_sunat) {
        throw new Exception('La guía debe estar aceptada por SUNAT antes de enviarla a Dynamics');
      }

      // Validar que no haya sido enviada ya a Dynamics
      if ($shippingGuide->status_dynamic) {
        throw new Exception('La guía ya ha sido enviada a Dynamics');
      }

      // marcar cono enviada a Dynamics
      $shippingGuide->markAsSentToDynamic();

      // Despachar el Job síncronamente para debugging
      VerifyAndMigrateShippingGuideJob::dispatchSync($shippingGuide->id);

      return response()->json([
        'success' => true,
        'message' => 'Guía de remisión de venta enviada a Dynamics GP correctamente',
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al enviar la guía a Dynamics GP: ' . $e->getMessage());
    }
  }
}
