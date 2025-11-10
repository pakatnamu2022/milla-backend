<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApVehicleDeliveryResource;
use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
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

        $vehicleDelivery = ApVehicleDelivery::create($data);

        // Crear el movimiento de vehículo asociado
        $this->vehicleMovementService->storeSheduleDeliveryVehicleMovement($vehicleDelivery->id);

        return new ApVehicleDeliveryResource($vehicleDelivery);
      });
    } catch (Exception $e) {
      throw new Exception('Error al crear la entrega de vehículo: ' . $e->getMessage());
    }
  }

  public function show($id)
  {
    return new ApVehicleDeliveryResource($this->find($id));
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
    DB::transaction(function () use ($vehicleDelivery) {
      $vehicleDelivery->delete();
    });
    return response()->json(['message' => 'Entrega de Vehículo eliminada correctamente']);
  }

  public function generateShippingGuide($id)
  {
    try {
      DB::transaction(function () use ($id) {
        $record = $this->find($id);

        if (!$record) {
          throw new Exception('Entrega de Vehículo no encontrada');
        }

        // Obtener el vehículo para validar el pago
        $vehicle = Vehicles::find($record->vehicle_id);
        $vehicleId = $record->vehicle_id;

        // Validar si el vehículo está completamente pagado usando el método centralizado
        $isPaid = $this->vehiclesService->isVehiclePaid($vehicle->vin);

        if (!$isPaid) {
          throw new Exception('El vehículo no está completamente pagado. No se puede generar la guía de remisión.');
        }

        // Obtener el documento electrónico (factura) asociado al vehículo
        $electronicDocument = ElectronicDocument::whereHas('vehicleMovement', function ($query) use ($vehicleId) {
          $query->where('ap_vehicle_id', $vehicleId);
        })
          ->where('aceptada_por_sunat', true)
          ->where('anulado', false)
          ->whereNotNull('client_id')
          ->whereNotNull('purchase_request_quote_id')
          ->with(['client', 'purchaseRequestQuote'])
          ->orderBy('fecha_de_emision', 'desc')
          ->first();

        if (!$electronicDocument || !$electronicDocument->client_id) {
          throw new Exception('No se encontró factura ni cliente asociado al vehículo');
        }

        if (!$electronicDocument->purchase_request_quote_id) {
          throw new Exception('No se encontró cotización asociada al vehículo');
        }

        $client = $electronicDocument->client;

        // Continuar con la lógica de generación de guía...
        // (El resto de la lógica la maneja el usuario)

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

      if ($vehicleDelivery->status_nubefact) {
        throw new Exception('La guía de remisión ya ha sido enviada a Nubefact');
      }

      if ($vehicleDelivery->status_delivery !== 'completed') {
        throw new Exception('Solo se pueden enviar guías de entregas completadas');
      }

      $shippingGuide = ShippingGuides::where('vehicle_movement_id', $vehicleDelivery->vehicle_id)
        ->whereNull('cancelled_at')
        ->latest()
        ->first();

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

      if (!$vehicleDelivery->status_nubefact) {
        throw new Exception('La guía no ha sido enviada a Nubefact aún');
      }

      // Buscar la guía de remisión asociada
      $shippingGuide = ShippingGuides::where('vehicle_movement_id', $vehicleDelivery->vehicle_id)
        ->whereNull('cancelled_at')
        ->latest()
        ->first();

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
          $vehicleDelivery->update(['status_sunat' => true]);
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

  /**
   * Obtiene la información necesaria para crear la guía de remisión de entrega al cliente
   */
  public function getShippingGuideInfo($vehicleId): JsonResponse
  {
    try {
      $vehicle = Vehicles::with([
        'warehousePhysical.sede',
        'model.family.brand',
        'color',
        'electronicDocuments' => function ($query) {
          $query->whereNull('ap_billing_electronic_documents.cancelled_at')
            ->whereNotNull('ap_billing_electronic_documents.client_id')
            ->where('ap_billing_electronic_documents.aceptada_por_sunat', true)
            ->latest();
        },
        'electronicDocuments.client'
      ])->find($vehicleId);

      if (!$vehicle) {
        throw new Exception('Vehículo no encontrado');
      }

      // Información del punto de partida (sede donde está el vehículo)
      $originAddress = $vehicle->warehousePhysical?->sede?->direccion ?? 'No disponible';
      $originSede = $vehicle->warehousePhysical?->sede;

      // Información del vehículo
      $vehicleInfo = [
        'vin' => $vehicle->vin,
        'model' => $vehicle->model?->version ?? 'N/A',
        'brand' => $vehicle->model?->family?->brand?->name ?? 'N/A',
        'family' => $vehicle->model?->family?->name ?? 'N/A',
        'year' => $vehicle->year,
        'color' => $vehicle->color?->description ?? 'N/A',
        'engine_number' => $vehicle->engine_number,
      ];

      // Buscar el último documento electrónico con cliente (factura de venta)
      $electronicDocument = $vehicle->electronicDocuments->first();

      $clientInfo = null;
      $driverInfo = null;

      if ($electronicDocument && $electronicDocument->client) {
        $client = $electronicDocument->client;

        $clientInfo = [
          'id' => $client->id,
          'num_doc' => $client->num_doc,
          'full_name' => $client->full_name,
          'direction' => $client->direction,
          'email' => $client->email,
          'phone' => $client->phone,
        ];

        $driverInfo = [
          'driver_num_doc' => $client->driver_num_doc ?? $client->num_doc,
          'driver_full_name' => $client->driver_full_name ?? $client->full_name,
          'driving_license' => $client->driving_license ?? 'N/A',
          'plate' => 'SIN PLACA', // Valor por defecto hasta que informen
        ];
      }

      return response()->json([
        'success' => true,
        'data' => [
          'vehicle' => $vehicleInfo,
          'origin' => [
            'address' => $originAddress,
            'sede_id' => $originSede?->id,
            'sede_name' => $originSede?->nombre ?? 'N/A',
            'sede_abbreviation' => $originSede?->abreviatura ?? 'N/A',
          ],
          'client' => $clientInfo,
          'driver' => $driverInfo,
          'has_client' => $clientInfo !== null,
        ]
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al obtener información para guía de remisión: ' . $e->getMessage());
    }
  }
}
