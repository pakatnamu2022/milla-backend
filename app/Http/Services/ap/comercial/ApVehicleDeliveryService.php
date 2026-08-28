<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApVehicleDeliveryResource;
use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\common\ExportService;
use App\Exports\VehicleDeliveryExport;
use App\Jobs\SyncAccountsReceivableJob;
use App\Jobs\VerifyAndMigrateShippingGuideJob;
use App\Models\dp\comercial\AccountReceivable;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use Carbon\Carbon;
use App\Models\ap\comercial\ApDeliveryChecklist;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ApVehicleDeliveryRescheduleLog;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\UserSeriesAssignment;
use App\Models\gp\gestionsistema\UserSede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ApVehicleDeliveryService extends BaseService implements BaseServiceInterface
{
  protected NubefactShippingGuideApiService $nubefactService;
  protected VehicleMovementService $vehicleMovementService;
  protected VehiclesService $vehiclesService;
  protected EmailService $emailService;

  public function __construct(
    NubefactShippingGuideApiService $nubefactService,
    VehicleMovementService          $vehicleMovementService,
    VehiclesService                 $vehiclesService,
    EmailService                    $emailService
  )
  {
    $this->nubefactService = $nubefactService;
    $this->vehicleMovementService = $vehicleMovementService;
    $this->vehiclesService = $vehiclesService;
    $this->emailService = $emailService;
  }

  public function list(Request $request)
  {
    $user = $request->user();

    if ($user->role->id === Constants::TICS_ROL_ID) {
      $query = ApVehicleDelivery::with(['ShippingGuide', 'deliveryChecklist']);
    } else {
      $sedes = $user->sedes()->pluck('config_sede.id')->toArray();
      $query = ApVehicleDelivery::with(['ShippingGuide', 'deliveryChecklist'])
        ->whereIn('sede_id', $sedes);
    }

    if ($request->boolean('extraordinary_review')) {
      // Solo entregas extraordinarias pendientes o rechazadas de aprobación.
      $query->where('is_extraordinary', true)
        ->where(function ($q) {
          $q->whereNull('extraordinary_approved')
            ->orWhere('extraordinary_approved', false);
        });
    } else {
      $query->where(function ($q) {
        $q->where('is_extraordinary', false)
          ->orWhereNull('is_extraordinary')
          ->orWhere('extraordinary_approved', true);
      });
    }

    // Filtro de guías anuladas: por defecto las oculta; con ?annulled=1 solo muestra las anuladas
    if ($request->filled('annulled')) {
      if ($request->boolean('annulled')) {
        $query->whereHas('ShippingGuide', fn($q) => $q->whereNotNull('cancelled_at'));
      } else {
        $query->where(function ($q) {
          $q->whereNull('shipping_guide_id')
            ->orWhereHas('ShippingGuide', fn($sq) => $sq->whereNull('cancelled_at'));
        });
      }
    }

    return $this->getFilteredResults(
      $query,
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

  public function store(mixed $data): ApVehicleDeliveryResource
  {
    try {
      return DB::transaction(function () use ($data) {
        $user = auth()->user();
        $data['advisor_id'] = $user->partner_id;
        $data['wash_date'] = now();
        $data['real_wash_date'] = now();
        $data['status_wash'] = 'completed';
        $isExtraordinary = !empty($data['is_extraordinary']);

        if (!$data['advisor_id']) {
          throw new Exception('El asesor no está asociado a un socio válido');
        }

        $existingDelivery = ApVehicleDelivery::where('vehicle_id', $data['vehicle_id'])
          ->where('status_delivery', '!=', ApVehicleDelivery::STATUS_CANCELLED)
          ->exists();
        if ($existingDelivery) {
          throw new Exception('Ya existe una entrega registrada para este vehículo.');
        }

        // Obtener el documento electrónico y cliente usando el método centralizado
        $documentData = Vehicles::getElectronicDocumentWithClient($data['vehicle_id']);
        $data['client_id'] = $documentData->client->id;

        // Sincronizar CxC al momento del store para tener datos frescos
        SyncAccountsReceivableJob::dispatchSync('automotores');

        // Validar que la cuenta por cobrar no esté vencida (liquidado = sin deuda vencida)
        $tieneVencido = AccountReceivable::where('electronic_document_id', $documentData->electronicDocument->id)
          ->where('overdue_status', 'VENCIDO')
          ->exists();

        if ($tieneVencido) {
          throw new Exception('La cuenta por cobrar del vehículo está vencida. Regularice el pago antes de programar la entrega.');
        }

        $scheduledDate = Carbon::parse($data['scheduled_delivery_date']);
        if ($scheduledDate->isPast()) {
          throw new Exception('No se puede programar una entrega en una fecha y hora pasada.');
        }
        if ($isExtraordinary && !$scheduledDate->isBefore(now()->addDays(2)->startOfDay())) {
          throw new Exception('Las entregas extraordinarias solo pueden programarse para hoy o mañana.');
        }
        if (!$isExtraordinary && !$scheduledDate->isAfter(now()->addHours(24))) {
          throw new Exception('La fecha de entrega debe programarse con al menos 24 horas de anticipación.');
        }

        if ($isExtraordinary) {
          $shopId = Sede::find($data['sede_id'])?->shop_id;
          $sedeIds = $shopId
            ? Sede::where('shop_id', $shopId)->pluck('id')
            : collect([$data['sede_id']]);

          $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $scheduledDate->format('Y-m-d H:i:s'))
            ->whereIn('sede_id', $sedeIds)
            ->whereNull('deleted_at')
            ->exists();

          if (!$slotTaken) {
            throw new Exception('Para una entrega extraordinaria, el horario seleccionado debe tener ya una entrega programada.');
          }

          $data['extraordinary_approved'] = null;
          $data['extraordinary_sent_by'] = auth()->id();
        }

        $vehicleDelivery = ApVehicleDelivery::create($data);
        $vehicle = Vehicles::find($data['vehicle_id']);

        if (!$vehicle) {
          throw new Exception('Vehículo no encontrado');
        }

        // creamos el movimiento de vehículo asociado
        $vehicleMovement = $this->vehicleMovementService->storeScheduleDeliveryVehicleMovement($vehicle);
        $vehicleDelivery->update(['vehicle_movement_id' => $vehicleMovement->id]);

        if ($isExtraordinary) {
          $this->sendExtraordinaryApprovalEmail($vehicleDelivery->fresh()->load(['vehicle', 'client', 'sede']));
        }

        return new ApVehicleDeliveryResource($vehicleDelivery);
      });
    } catch (Exception $e) {
      throw new Exception('Error al crear la entrega de vehículo: ' . $e->getMessage());
    }
  }

  public function show($id)
  {
    $vehicleDelivery = ApVehicleDelivery::with(['ShippingGuide', 'deliveryChecklist', 'rescheduleLogs.rescheduledBy'])->find($id);
    if (!$vehicleDelivery) {
      throw new Exception('Entrega de Vehículo no encontrado');
    }
    return new ApVehicleDeliveryResource($vehicleDelivery);
  }

  public function rescheduleHistory(int $id): array
  {
    $vehicleDelivery = ApVehicleDelivery::with(['rescheduleLogs.rescheduledBy'])->findOrFail($id);

    return $vehicleDelivery->rescheduleLogs->map(fn($log) => [
      'id'                  => $log->id,
      'previous_date'       => $log->previous_date,
      'new_date'            => $log->new_date,
      'is_extraordinary'    => $log->is_extraordinary,
      'observations'        => $log->observations,
      'rescheduled_by'      => $log->rescheduled_by,
      'rescheduled_by_name' => $log->rescheduledBy?->name,
      'created_at'          => $log->created_at,
    ])->values()->all();
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

    // Si status_delivery cambia a completed, verificar accesorios pendientes y setear real_delivery_date
    if (isset($data['status_delivery']) && $data['status_delivery'] === 'completed') {
      $pendingInstWO = ApWorkOrder::where('vehicle_id', $vehicleDelivery->vehicle_id)
        ->whereHas('items', fn($q) => $q->where('type_planning_id', TypePlanningWorkOrder::TYPE_PLANNING_INST_ACCESORIOS_ID))
        ->where('status_id', '!=', ApMasters::CLOSED_WORK_ORDER_ID)
        ->exists();

      if ($pendingInstWO) {
        throw new Exception('No se puede completar la entrega: existen órdenes de trabajo de instalación de accesorios pendientes de cierre.');
      }

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

        // Validar que la guía se genere el mismo día de la entrega programada
        if (!$record->scheduled_delivery_date || !now()->isSameDay(\Carbon\Carbon::parse($record->scheduled_delivery_date))) {
          throw new Exception('La guía de remisión solo puede generarse el día de la entrega programada (' . \Carbon\Carbon::parse($record->scheduled_delivery_date)->format('d/m/Y') . ')');
        }

        // Verificar si ya existe una guía de remisión activa
        if ($record->shipping_guide_id) {
          $existingShippingGuide = ShippingGuides::where('id', $record->shipping_guide_id)->whereNull('cancelled_at')->first();
          if ($existingShippingGuide) {
            throw new Exception('Esta entrega ya tiene una guía de remisión asociada. No se puede generar otra.');
          }
        }

        // Validar que exista un checklist de entrega confirmado antes de generar la guía
        $checklist = ApDeliveryChecklist::where('vehicle_delivery_id', $id)->first();
        if (!$checklist) {
          throw new Exception('Debe crear y confirmar el checklist de entrega antes de generar la guía de remisión');
        }
        if (!$checklist->isConfirmed()) {
          throw new Exception('El checklist de entrega debe estar confirmado antes de generar la guía de remisión');
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

        if (!$vehicle->is_paid) {
          throw new Exception('El vehículo no está completamente pagado. No se puede generar la guía de remisión.');
        }

        $client = $record->client()->with('district')->first();
        if (!$client) {
          throw new Exception('No se encontró cliente asociado a la entrega');
        }
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
          'document_type'          => 'GUIA_REMISION',
          'type_voucher_id'        => SunatConcepts::TYPE_VOUCHER_REMISION_REMITENTE,
          'issuer_type'            => ShippingGuides::ISSUER_TYPE_SYSTEM,
          'document_series_id'     => $documentSeriesId,
          'series'                 => $series,
          'correlative'            => $correlative,
          'document_number'        => $documentNumber,
          'issue_date'             => now(),
          'requires_sunat'         => true,
          'vehicle_movement_id'    => $vehicleMovement->id,
          'sede_transmitter_id'    => $record->sede_id,
          'sede_receiver_id'       => $record->sede_id,
          'transmitter_id'         => $originEstablishment->id,
          'receiver_id'            => $originEstablishment->id,
          'transport_company_id'   => $transportCompanyId,
          'driver_doc'             => $data['driver_doc'],
          'driver_name'            => $data['driver_name'],
          'license'                => $data['license'] ?? null,
          'plate'                  => $data['plate'] ?? '',
          'notes'                  => $data['notes'] ?? 'ENTREGA DE VEHÍCULO VENDIDO',
          'status'                 => true,
          'transfer_reason_id'     => SunatConcepts::TRANSFER_REASON_VENTA,
          'transfer_modality_id'   => $data['transfer_modality_id'],
          'created_by'             => auth()->id(),
          'ap_class_article_id'    => $record->ap_class_article_id,
          'origin_ubigeo'          => $originUbigeo,
          'origin_address'         => $originAddress,
          'destination_ubigeo'     => $destinationUbigeo,
          'destination_address'    => $destinationAddress,
          'ruc_transport'          => $data['carrier_ruc'] ?? null,
          'company_name_transport' => $data['company_name_transport'] ?? null,
          'net_weight'             => 1,
          'total_packages'         => $data['total_packages'] ?? 1,
          'total_weight'           => (float)preg_replace('/[^0-9.]/', '', $vehicle->model->gross_weight ?? '') ?: 1000,
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
          'enlace'                => $responseData['enlace'] ?? null,
          'enlace_del_pdf'        => $responseData['enlace_del_pdf'] ?? null,
          'enlace_del_xml'        => $responseData['enlace_del_xml'] ?? null,
          'enlace_del_cdr'        => $responseData['enlace_del_cdr'] ?? null,
          'cadena_para_codigo_qr' => $responseData['cadena_para_codigo_qr'] ?? null,
          'sunat_description'     => $responseData['sunat_description'] ?? null,
          'sunat_note'            => $responseData['sunat_note'] ?? null,
          'sunat_responsecode'    => $responseData['sunat_responsecode'] ?? null,
          'sunat_soap_error'      => $responseData['sunat_soap_error'] ?? null,
        ]);

        // Verificar si fue aceptada por SUNAT
        if (isset($responseData['aceptada_por_sunat']) && $responseData['aceptada_por_sunat']) {
          $shippingGuide->markAsAccepted($responseData);
          $vehicleDelivery->update([
            'status_nubefact'    => true,
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
        'success'           => $response['success'],
        'message'           => $message,
        'data'              => new ApVehicleDeliveryResource($vehicleDelivery->fresh()),
        'shipping_guide'    => new ShippingGuidesResource($shippingGuide->fresh()),
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
          $vehicleDelivery->update(['aceptada_por_sunat' => true, 'real_delivery_date' => now()]);
          DB::commit();
          try {
            $freshDelivery = $vehicleDelivery->fresh()->load(['vehicle.model.family.brand', 'vehicle.color', 'client', 'advisor', 'sede']);
            $this->sendClientWelcomeEmail($freshDelivery);
          } catch (\Throwable $e) {
            \Log::warning('Welcome email could not be queued: ' . $e->getMessage());
          }
          $message = 'La guía ha sido aceptada por SUNAT';
        } else {
          // Actualizar los enlaces aunque no esté aceptada aún
          $shippingGuide->update([
            'enlace'         => $responseData['enlace'] ?? $shippingGuide->enlace,
            'enlace_del_pdf' => $responseData['enlace_del_pdf'] ?? $shippingGuide->enlace_del_pdf,
            'enlace_del_xml' => $responseData['enlace_del_xml'] ?? $shippingGuide->enlace_del_xml,
            'enlace_del_cdr' => $responseData['enlace_del_cdr'] ?? $shippingGuide->enlace_del_cdr,
          ]);
          if ($responseData['sunat_soap_error'] !== '') {
            $shippingGuide->update([
              'aceptada_por_sunat'  => false,
              'cancelled_at'        => now(),
              'cancellation_reason' => 'Error en SOAP: ' . $responseData['sunat_soap_error'],
            ]);
            $vehicleDelivery->update(['sent_at' => null]);
          }

          $message = 'Estado de la guía consultado correctamente';
        }
      } else {
        $message = 'Error al consultar la guía: ' . ($response['error'] ?? 'Error desconocido');
      }

      return response()->json([
        'success'           => $response['success'],
        'message'           => $message,
        'data'              => new ApVehicleDeliveryResource($vehicleDelivery->fresh()),
        'shipping_guide'    => new ShippingGuidesResource($shippingGuide->fresh()),
        'nubefact_response' => $response['data'] ?? null
      ]);
    } catch (Exception $e) {
      throw new Exception('Error al consultar la guía en Nubefact: ' . $e->getMessage());
    }
  }

  /**
   * Lista los vehículos de stock inicial (compra con '%SI-%') en estado VENDIDO NO ENTREGADO
   * que aún no tienen una entrega registrada.
   */
  public function listStockInicialVehicles(?int $sedeId = null)
  {
    $vehicleIds = PurchaseOrder::whereNull('deleted_at')
      ->where('number', 'like', '%SI-%')
      ->whereHas('vehicleMovement', function ($q) {
        $q->whereNotNull('ap_vehicle_id');
      })
      ->with('vehicleMovement')
      ->get()
      ->pluck('vehicleMovement.ap_vehicle_id')
      ->filter()
      ->unique()
      ->values();

    $deliveredVehicleIds = ApVehicleDelivery::whereNull('deleted_at')
      ->pluck('vehicle_id');

    $query = Vehicles::whereIn('id', $vehicleIds)
      ->whereNotIn('id', $deliveredVehicleIds)
      ->where('ap_vehicle_status_id', ApVehicleStatus::VENDIDO_NO_ENTREGADO)
      ->with(['model', 'color', 'vehicleStatus']);

    if ($sedeId) {
      $query->whereHas('warehouse', fn($q) => $q->where('sede_id', $sedeId));
    }

    return VehiclesResource::collection($query->get());
  }

  /**
   * Crea una entrega para un vehículo de stock inicial.
   * El lavado se marca como completado por defecto.
   * El advisor_id proviene del request (no del usuario autenticado).
   */
  public function storeStockInicial(mixed $data): ApVehicleDeliveryResource
  {
    try {
      return DB::transaction(function () use ($data) {
        $vehicle = Vehicles::findOrFail($data['vehicle_id']);
        $isExtraordinary = !empty($data['is_extraordinary']);

        $existingDelivery = ApVehicleDelivery::where('vehicle_id', $data['vehicle_id'])
          ->where('status_delivery', '!=', ApVehicleDelivery::STATUS_CANCELLED)
          ->exists();
        if ($existingDelivery) {
          throw new Exception('Ya existe una entrega registrada para este vehículo.');
        }

        $scheduledDate = Carbon::parse($data['scheduled_delivery_date']);
        if ($scheduledDate->isPast()) {
          throw new Exception('No se puede programar una entrega en una fecha y hora pasada.');
        }
        if ($isExtraordinary && !$scheduledDate->isBefore(now()->addDays(2)->startOfDay())) {
          throw new Exception('Las entregas extraordinarias solo pueden programarse para hoy o mañana.');
        }
        if (!$isExtraordinary && !$scheduledDate->isAfter(now()->addHours(24))) {
          throw new Exception('La fecha de entrega debe programarse con al menos 24 horas de anticipación.');
        }

        if ($isExtraordinary) {
          $shopId = Sede::find($data['sede_id'])?->shop_id;
          $sedeIds = $shopId
            ? Sede::where('shop_id', $shopId)->pluck('id')
            : collect([$data['sede_id']]);

          $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $scheduledDate->format('Y-m-d H:i:s'))
            ->whereIn('sede_id', $sedeIds)
            ->whereNull('deleted_at')
            ->exists();

          if (!$slotTaken) {
            throw new Exception('Para una entrega extraordinaria, el horario seleccionado debe tener ya una entrega programada.');
          }
        }

        // Reutilizar el movimiento SOLD_NOT_DELIVERED ya existente
        $vehicleMovement = VehicleMovement::where('ap_vehicle_id', $vehicle->id)
          ->where('movement_type', VehicleMovement::SOLD_NOT_DELIVERED)
          ->latest()
          ->first();

        $deliveryData = [
          'advisor_id'              => $data['advisor_id'],
          'vehicle_id'              => $data['vehicle_id'],
          'sede_id'                 => $data['sede_id'],
          'ap_class_article_id'     => $data['ap_class_article_id'],
          'scheduled_delivery_date' => $data['scheduled_delivery_date'],
          'wash_date'               => now(),
          'real_wash_date'          => now(),
          'status_wash'             => 'completed',
          'status_delivery'         => 'pending',
          'observations'            => $data['observations'] ?? null,
          'client_id'               => $data['client_id'],
          'vehicle_movement_id'     => $vehicleMovement?->id,
        ];

        if ($isExtraordinary) {
          $deliveryData['extraordinary_approved'] = null;
          $deliveryData['extraordinary_sent_by'] = auth()->id();
        }

        $vehicleDelivery = ApVehicleDelivery::create($deliveryData);

        if ($isExtraordinary) {
          $this->sendExtraordinaryApprovalEmail($vehicleDelivery->fresh()->load(['vehicle', 'client', 'sede']));
        }

        return new ApVehicleDeliveryResource($vehicleDelivery);
      });
    } catch (Exception $e) {
      throw new Exception('Error al crear la entrega de stock inicial: ' . $e->getMessage());
    }
  }

  public function reschedule(int $id, array $data): ApVehicleDeliveryResource
  {
    $vehicleDelivery = $this->find($id);
    $isExtraordinary = !empty($data['is_extraordinary']);

    if ($vehicleDelivery->status_delivery === 'completed') {
      throw new Exception('No se puede reprogramar una entrega ya completada.');
    }

    if ($vehicleDelivery->shipping_guide_id) {
      throw new Exception('No se puede reprogramar una entrega que ya tiene guía de remisión generada.');
    }

    $newDate = Carbon::parse($data['scheduled_delivery_date']);

    if ($newDate->isPast()) {
      throw new Exception('No se puede reprogramar a una fecha y hora pasada.');
    }
    if ($isExtraordinary && !$newDate->isBefore(now()->addDays(2)->startOfDay())) {
      throw new Exception('Las reprogramaciones extraordinarias solo pueden ser para hoy o mañana.');
    }
    if (!$isExtraordinary && !$newDate->isAfter(now()->addHours(24))) {
      throw new Exception('La fecha de reprogramación debe ser con al menos 24 horas de anticipación.');
    }

    $sedeIdsDelShop = $vehicleDelivery->sede?->shop_id
      ? Sede::where('shop_id', $vehicleDelivery->sede->shop_id)->pluck('id')
      : collect([$vehicleDelivery->sede_id]);

    if ($isExtraordinary) {
      $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $newDate->format('Y-m-d H:i:s'))
        ->whereIn('sede_id', $sedeIdsDelShop)
        ->where('id', '!=', $id)
        ->whereNull('deleted_at')
        ->exists();

      if (!$slotTaken) {
        throw new Exception('Para una reprogramación extraordinaria, el horario seleccionado debe tener ya una entrega programada.');
      }
    } else {
      $slotTaken = ApVehicleDelivery::where('scheduled_delivery_date', $newDate->format('Y-m-d H:i:s'))
        ->whereIn('sede_id', $sedeIdsDelShop)
        ->where('id', '!=', $id)
        ->whereNull('deleted_at')
        ->exists();

      if ($slotTaken) {
        throw new Exception('El horario ' . $newDate->format('H:i') . ' del ' . $newDate->format('d/m/Y') . ' ya está ocupado en este shop. Elija otro horario.');
      }
    }

    $previousDate = $vehicleDelivery->scheduled_delivery_date;

    $updateData = [
      'scheduled_delivery_date' => $data['scheduled_delivery_date'],
      'observations'            => $data['observations'] ?? $vehicleDelivery->observations,
      'rescheduled_by'          => auth()->id(),
      'is_extraordinary'        => $isExtraordinary,
      'extraordinary_reason'    => $isExtraordinary ? ($data['extraordinary_reason'] ?? null) : null,
    ];

    if ($isExtraordinary) {
      $updateData['extraordinary_approved'] = null;
      $updateData['extraordinary_sent_by'] = auth()->id();
    }

    $vehicleDelivery->update($updateData);

    ApVehicleDeliveryRescheduleLog::create([
      'vehicle_delivery_id' => $vehicleDelivery->id,
      'previous_date'       => $previousDate,
      'new_date'            => $data['scheduled_delivery_date'],
      'rescheduled_by'      => auth()->id(),
      'is_extraordinary'    => $isExtraordinary,
      'observations'        => $data['observations'] ?? null,
    ]);

    if ($isExtraordinary) {
      $this->sendExtraordinaryApprovalEmail($vehicleDelivery->fresh()->load(['vehicle', 'client', 'sede']));
    }

    return new ApVehicleDeliveryResource($vehicleDelivery->fresh());
  }

  public function approveExtraordinary(int $id, ?string $comment = null): ApVehicleDeliveryResource
  {
    $delivery = ApVehicleDelivery::whereNull('deleted_at')->findOrFail($id);

    if (!$delivery->is_extraordinary) {
      throw new Exception('Esta entrega no es extraordinaria.');
    }

    if ($delivery->extraordinary_approved === true) {
      throw new Exception('Esta entrega ya fue aprobada.');
    }

    if ($delivery->extraordinary_approved === false) {
      throw new Exception('Esta entrega ya fue rechazada.');
    }

    $delivery->update([
      'extraordinary_approved'         => true,
      'extraordinary_approved_at'      => now(),
      'extraordinary_approved_by'      => auth()->user()->name,
      'extraordinary_approval_comment' => $comment,
    ]);

    return new ApVehicleDeliveryResource($delivery->fresh());
  }

  public function rejectExtraordinary(int $id, string $comment): ApVehicleDeliveryResource
  {
    $delivery = ApVehicleDelivery::whereNull('deleted_at')->findOrFail($id);

    if (!$delivery->is_extraordinary) {
      throw new Exception('Esta entrega no es extraordinaria.');
    }

    if ($delivery->extraordinary_approved === true) {
      throw new Exception('Esta entrega ya fue aprobada.');
    }

    if ($delivery->extraordinary_approved === false) {
      throw new Exception('Esta entrega ya fue rechazada.');
    }

    $delivery->update([
      'extraordinary_approved'         => false,
      'extraordinary_approved_at'      => now(),
      'extraordinary_approved_by'      => auth()->user()->name,
      'extraordinary_approval_comment' => $comment,
    ]);

    return new ApVehicleDeliveryResource($delivery->fresh());
  }

  public function resendExtraordinaryApproval(int $id): ApVehicleDeliveryResource
  {
    $delivery = ApVehicleDelivery::with(['vehicle', 'client', 'sede'])
      ->whereNull('deleted_at')
      ->findOrFail($id);

    if ($delivery->extraordinary_approved === true) {
      throw new Exception('Esta entrega extraordinaria ya fue aprobada.');
    }

    $delivery->update([
      'extraordinary_approved' => null,
      'extraordinary_sent_by'  => auth()->id(),
    ]);

    $this->sendExtraordinaryApprovalEmail($delivery->fresh()->load(['vehicle', 'client', 'sede']));

    return new ApVehicleDeliveryResource($delivery->fresh());
  }

  /**
   * Diagnostica por qué no se puede generar una entrega para un VIN dado.
   * Retorna una lista ordenada de pasos/verificaciones con su estado.
   */
  public function diagnoseVin(string $vin, ?int $sedeId = null): array
  {
    $checks = [];
    $canGenerate = true;

    // ── 1. VIN existe ────────────────────────────────────────────────────────
    $vehicle = Vehicles::with([
      'vehicleStatus',
      'warehouse.sede',
      'warehousePhysical',
      'vehicleMovements.shippingGuides.receivingChecklists',
      'purchaseOrders',
    ])->where('vin', $vin)->first();

    if (!$vehicle) {
      return [
        'can_generate_delivery' => false,
        'vehicle'               => null,
        'checks'                => [[
          'step'    => 'VIN en el sistema',
          'status'  => 'fail',
          'message' => "El VIN «{$vin}» no existe en el sistema.",
          'action'  => 'Verifica que el VIN esté correctamente escrito o regístralo primero.',
        ]],
      ];
    }

    $vehicleInfo = [
      'id'        => $vehicle->id,
      'vin'       => $vehicle->vin,
      'status'    => $vehicle->vehicleStatus?->description ?? '—',
      'warehouse' => $vehicle->warehouse?->description ?? '—',
      'sede'      => $vehicle->warehouse?->sede?->abreviatura ?? '—',
    ];

    // ── 2. Ya tiene entrega registrada ───────────────────────────────────────
    $existingDelivery = ApVehicleDelivery::where('vehicle_id', $vehicle->id)
      ->whereNull('deleted_at')
      ->where('status_delivery', '!=', ApVehicleDelivery::STATUS_CANCELLED)
      ->first();

    if ($existingDelivery) {
      $statusLabel = $existingDelivery->status_delivery === ApVehicleDelivery::STATUS_DELIVERED
        ? 'completada'
        : 'pendiente';
      $checks[] = [
        'step'    => 'Entrega existente',
        'status'  => 'fail',
        'message' => "El vehículo ya tiene una entrega {$statusLabel} registrada (ID: {$existingDelivery->id}).",
        'action'  => $existingDelivery->status_delivery === ApVehicleDelivery::STATUS_DELIVERED
          ? 'El vehículo ya fue entregado. No se puede generar una nueva entrega.'
          : 'Existe una entrega pendiente. Completa o elimina la entrega existente antes de crear una nueva.',
      ];
      $canGenerate = false;
    } else {
      $checks[] = [
        'step'    => 'Entrega existente',
        'status'  => 'pass',
        'message' => 'El vehículo no tiene una entrega activa registrada.',
        'action'  => null,
      ];
    }

    // ── 3. Estado del vehículo ───────────────────────────────────────────────
    $statusId = $vehicle->ap_vehicle_status_id;

    $allowedStatuses = [
      ApVehicleStatus::VENDIDO_NO_ENTREGADO,
      ApVehicleStatus::FACTURADO,
      ApVehicleStatus::FACTURADO_FINAL,
    ];

    $transitStatuses = [
      ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
    ];

    if (in_array($statusId, $transitStatuses)) {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'fail',
        'message' => "El vehículo está en estado «{$vehicle->vehicleStatus->description}»: aún no ha sido recibido en almacén.",
        'action'  => 'Debe registrarse la recepción del vehículo (checklist de recepción) antes de generar la entrega.',
      ];
      $canGenerate = false;
    } elseif ($statusId === ApVehicleStatus::PEDIDO_VN) {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'fail',
        'message' => 'El vehículo está en estado «Pedido VN»: todavía no ha salido del proveedor.',
        'action'  => 'Espera a que el proveedor despache el vehículo y se registre el movimiento de tránsito.',
      ];
      $canGenerate = false;
    } elseif ($statusId === ApVehicleStatus::VENDIDO_ENTREGADO) {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'fail',
        'message' => 'El vehículo ya fue entregado (estado «Vendido Entregado»).',
        'action'  => 'Este vehículo ya completó su ciclo de entrega.',
      ];
      $canGenerate = false;
    } elseif ($statusId === ApVehicleStatus::CONSIGNACION) {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'fail',
        'message' => 'El vehículo está en consignación.',
        'action'  => 'Resuelve el proceso de consignación antes de generar una entrega normal.',
      ];
      $canGenerate = false;
    } elseif (in_array($statusId, $allowedStatuses) || $statusId === ApVehicleStatus::INVENTARIO_VN) {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'pass',
        'message' => "El vehículo está en estado «{$vehicle->vehicleStatus->description}», apto para entrega.",
        'action'  => null,
      ];
    } else {
      $checks[] = [
        'step'    => 'Estado del vehículo',
        'status'  => 'warning',
        'message' => "El vehículo está en estado «{$vehicle->vehicleStatus->description}».",
        'action'  => 'Verifica que este estado permita generar una entrega.',
      ];
    }

    // ── 4. Guía de compra y recepción ────────────────────────────────────────
    // Resolver la orden de compra aquí para usarla también en el paso 6
    $purchaseOrder = $vehicle->purchaseOrders()->orderByDesc('id')->first();
    $isStockInicial = $purchaseOrder && str_contains($purchaseOrder->number ?? '', 'SI-');

    // Buscamos la guía de remisión de compra (la que tiene checklist de recepción)
    $receptionGuide = $vehicle->vehicleMovements()
      ->with(['shippingGuides' => function ($q) {
        $q->whereHas('receivingChecklists')->orderByDesc('id');
      }])
      ->get()
      ->flatMap(fn($m) => $m->shippingGuides)
      ->first();

    if (!$receptionGuide) {
      // Buscar si hay alguna guía de tránsito (EN TRAVESIA) sin recepción
      $transitGuide = $vehicle->vehicleMovements()
        ->where('movement_type', VehicleMovement::IN_TRANSIT)
        ->with('shippingGuides')
        ->get()
        ->flatMap(fn($m) => $m->shippingGuides)
        ->first();

      if ($transitGuide) {
        $checks[] = [
          'step'    => 'Recepción en almacén',
          'status'  => 'fail',
          'message' => "El vehículo tiene guía de tránsito (N° {$transitGuide->document_number}) pero aún no se ha registrado la recepción en almacén.",
          'action'  => 'Completa el checklist de recepción del vehículo para registrar su ingreso al almacén.',
        ];
        $canGenerate = false;
      } elseif ($purchaseOrder) {
        // Tiene orden de compra (vino de proveedor) → la recepción es obligatoria aunque no se detecte guía de tránsito
        $checks[] = [
          'step'    => 'Recepción en almacén',
          'status'  => 'fail',
          'message' => "El vehículo ingresó por orden de compra (N° {$purchaseOrder->number}) pero no tiene una guía de recepción registrada en el sistema.",
          'action'  => 'Debe registrarse la recepción del vehículo (checklist de recepción) antes de generar la entrega.',
        ];
        $canGenerate = false;
      } else {
        // Sin orden de compra ni guía de tránsito: podría ser stock inicial manual
        $checks[] = [
          'step'    => 'Recepción en almacén',
          'status'  => 'warning',
          'message' => 'No se encontró una guía de recepción registrada para este vehículo.',
          'action'  => 'Si es un vehículo de stock inicial (prefijo SI-), usa el flujo especial de entrega de stock inicial.',
        ];
      }
    } elseif (!$receptionGuide->is_accounted) {
      $checks[] = [
        'step'    => 'Recepción en almacén',
        'status'  => 'fail',
        'message' => "La guía de recepción (N° {$receptionGuide->document_number}) existe pero aún no está contabilizada.",
        'action'  => 'Contabiliza la guía de remisión de compra en el módulo de contabilidad antes de generar la entrega.',
      ];
      $canGenerate = false;
    } else {
      $checks[] = [
        'step'    => 'Recepción en almacén',
        'status'  => 'pass',
        'message' => "Recepción registrada y contabilizada (guía N° {$receptionGuide->document_number}).",
        'action'  => null,
      ];
    }

    // ── 5. Almacén de la sede seleccionada ───────────────────────────────────
    if ($sedeId) {
      $vehicleSedeId = $vehicle->warehouse?->sede_id;
      $sede = Sede::find($sedeId);
      $sedeLabel = $sede?->abreviatura ?? "ID {$sedeId}";

      if ($vehicleSedeId !== $sedeId) {
        $vehicleSedeLabel = $vehicle->warehouse?->sede?->abreviatura ?? 'desconocida';
        $checks[] = [
          'step'    => 'Almacén de la sede',
          'status'  => 'fail',
          'message' => "El vehículo está en el almacén de la sede «{$vehicleSedeLabel}», no en la sede «{$sedeLabel}».",
          'action'  => "Transfiere el vehículo al almacén de la sede «{$sedeLabel}» antes de generar la entrega, o selecciona la sede correcta.",
        ];
        $canGenerate = false;
      } else {
        $checks[] = [
          'step'    => 'Almacén de la sede',
          'status'  => 'pass',
          'message' => "El vehículo está en el almacén de la sede «{$sedeLabel}».",
          'action'  => null,
        ];
      }
    }

    // ── 6. Stock inicial (orden de compra con prefijo SI-) ───────────────────
    // $purchaseOrder e $isStockInicial ya están resueltos en el paso 4

    if ($isStockInicial) {
      // Si ya tiene un movimiento con estado FACTURADO_FINAL, fue procesado en el sistema → flujo normal
      $hasFacturadoFinal = $vehicle->vehicleMovements()
        ->where('ap_vehicle_status_id', ApVehicleStatus::FACTURADO_FINAL)
        ->exists();

      if ($hasFacturadoFinal) {
        $checks[] = [
          'step'    => 'Stock inicial',
          'status'  => 'pass',
          'message' => "El vehículo es de stock inicial (OC: {$purchaseOrder->number}) y ya fue facturado en el sistema. Sigue el flujo normal de entrega.",
          'action'  => null,
        ];
      } elseif ($statusId !== ApVehicleStatus::VENDIDO_NO_ENTREGADO) {
        $checks[] = [
          'step'    => 'Stock inicial',
          'status'  => 'fail',
          'message' => "El vehículo pertenece a stock inicial (OC: {$purchaseOrder->number}) pero no está en estado «Vendido No Entregado».",
          'action'  => 'Para vehículos de stock inicial, contacta al área de TIC\'s enviando un correo con el VIN para regularizar el estado.',
        ];
        $canGenerate = false;
      } elseif ($receptionGuide && $receptionGuide->is_accounted) {
        // Recepción completa pero sin FACTURADO_FINAL → TIC's debe procesar la entrega
        $checks[] = [
          'step'    => 'Stock inicial',
          'status'  => 'fail',
          'message' => "El vehículo es de stock inicial (OC: {$purchaseOrder->number}) y debe ser programado por TIC's.",
          'action'  => 'Envía un correo a dordinolac@grupopakatnamu.com con el DNI del cliente ya creado, el VIN, la fecha y la hora de programación para que TIC\'s registre la entrega.',
        ];
        $canGenerate = false;
      } else {
        $checks[] = [
          'step'    => 'Stock inicial',
          'status'  => 'pass',
          'message' => "El vehículo es de stock inicial (OC: {$purchaseOrder->number}). Completa primero la recepción en almacén.",
          'action'  => null,
        ];
      }
    } else {
      $checks[] = [
        'step'    => 'Stock inicial',
        'status'  => 'pass',
        'message' => 'El vehículo no es de stock inicial; sigue el flujo normal de entrega.',
        'action'  => null,
      ];
    }

    // ── 7. Factura electrónica ───────────────────────────────────────────────
    // Buscar el documento para todos los vehículos (se usa también en el check de CxC)
    $electronicDocument = ElectronicDocument::whereHas('vehicleMovement', function ($q) use ($vehicle) {
      $q->where('ap_vehicle_id', $vehicle->id);
    })
      ->where('aceptada_por_sunat', true)
      ->where(function ($q) {
        $q->where('anulado', false)->orWhereNull('anulado');
      })
      ->whereNotNull('client_id')
      ->whereNotNull('purchase_request_quote_id')
      ->orderByDesc('fecha_de_emision')
      ->first();

    if (!$isStockInicial) {
      if (!$electronicDocument) {
        $checks[] = [
          'step'    => 'Factura electrónica',
          'status'  => 'fail',
          'message' => 'No se encontró una factura electrónica aceptada por SUNAT asociada al vehículo.',
          'action'  => 'Emite y registra la factura electrónica del vehículo en el sistema antes de generar la entrega.',
        ];
        $canGenerate = false;
      } else {
        $checks[] = [
          'step'    => 'Factura electrónica',
          'status'  => 'pass',
          'message' => "Factura N° {$electronicDocument->full_number} aceptada por SUNAT.",
          'action'  => null,
        ];
      }
    }

    // ── 8. Liquidación (CxC no vencida) ─────────────────────────────────────
    // Aplica a todos los vehículos que tienen factura electrónica (incluyendo stock inicial facturado)
    if (!empty($electronicDocument)) {
      $tieneVencido = AccountReceivable::where('electronic_document_id', $electronicDocument->id)
        ->where('overdue_status', 'VENCIDO')
        ->exists();

      if ($tieneVencido) {
        $checks[] = [
          'step'    => 'Liquidación (CxC)',
          'status'  => 'fail',
          'message' => 'La cuenta por cobrar de la factura está vencida.',
          'action'  => 'Regularice el pago de la deuda vencida antes de programar la entrega.',
        ];
        $canGenerate = false;
      } else {
        $checks[] = [
          'step'    => 'Liquidación (CxC)',
          'status'  => 'pass',
          'message' => 'La cuenta por cobrar no está vencida. El vehículo está liquidado.',
          'action'  => null,
        ];
      }
    }

    // ── 9. Pago del vehículo ─────────────────────────────────────────────────
    if (!$vehicle->is_paid) {
      $checks[] = [
        'step'    => 'Pago del vehículo',
        'status'  => 'fail',
        'message' => 'El vehículo no está marcado como completamente pagado.',
        'action'  => 'Registra el pago completo del vehículo (campo is_paid) antes de generar la guía de remisión de entrega.',
      ];
      $canGenerate = false;
    } else {
      $checks[] = [
        'step'    => 'Pago del vehículo',
        'status'  => 'pass',
        'message' => 'El vehículo está completamente pagado.',
        'action'  => null,
      ];
    }

    return [
      'can_generate_delivery' => $canGenerate,
      'vehicle'               => $vehicleInfo,
      'checks'                => $checks,
    ];
  }

  private function sendClientWelcomeEmail(ApVehicleDelivery $delivery): void
  {
    $client = $delivery->client;
    if (!$client || empty($client->email)) {
      return;
    }

    $welcomeConfigs = ApMasters::ofType(ApMasters::TYPE_VEHICLE_WELCOME)->get()->keyBy('code');
    $letterUrl = $welcomeConfigs->get(ApMasters::VEHICLE_WELCOME_LETTER_CODE)?->description;
    $videoUrl = $welcomeConfigs->get(ApMasters::VEHICLE_WELCOME_VIDEO_CODE)?->description;

    $attachments = [];
    if ($letterUrl) {
      $attachments[] = [
        'url'  => $letterUrl,
        'name' => 'Carta de Bienvenida.pdf',
        'mime' => 'application/pdf',
      ];
    }

    preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl ?? '', $ytMatches);
    $videoThumbnail = isset($ytMatches[1])
      ? "https://img.youtube.com/vi/{$ytMatches[1]}/maxresdefault.jpg"
      : null;

    $vehicle = $delivery->vehicle;
    $modelVersion = $vehicle?->model?->version ?? '';
    $modelYear = $vehicle?->model?->model_year ?? '';
    $brandName = $vehicle?->model?->family?->brand?->name ?? '';
    $colorName = $vehicle?->color?->description ?? '';
    $advisorName = $delivery->advisor?->nombre_completo ?? '';
    $sedeName = $delivery->sede?->abreviatura ?? '';

    $this->emailService->queue([
      'to'          => $client->email,
      'subject'     => '¡Bienvenido a la familia! Tu ' . trim($modelVersion . ' ' . $modelYear) . ' te espera',
      'template'    => 'emails.vehicle-welcome',
      'attachments' => $attachments,
      'data'        => [
        'client_name'     => $client->full_name,
        'brand_name'      => $brandName,
        'model_version'   => $modelVersion,
        'model_year'      => $modelYear,
        'vehicle_vin'     => $vehicle?->vin ?? '',
        'color_name'      => $colorName,
        'advisor_name'    => $advisorName,
        'sede_name'       => $sedeName,
        'delivery_date'   => Carbon::parse($delivery->real_delivery_date ?? now())->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        'has_letter'      => !empty($letterUrl),
        'video_url'       => $videoUrl,
        'video_thumbnail' => $videoThumbnail,
      ],
    ]);
  }

  private function sendExtraordinaryApprovalEmail(ApVehicleDelivery $delivery): void
  {
    $approvalEmail = config('mail.delivery.extraordinary_approval');

    if (empty($approvalEmail)) {
      return;
    }

    $sentBy = \App\Models\User::find($delivery->extraordinary_sent_by);
    $approveUrl = rtrim(config('app.frontend_url'), '/') . '/ap/comercial/entrega-vehiculo/' . $delivery->id . '/aprobacion';

    $this->emailService->send([
      'to'       => $approvalEmail,
      'subject'  => 'Entrega extraordinaria pendiente de aprobación — ' . ($delivery->vehicle->vin ?? 'VIN no disponible'),
      'template' => 'emails.vehicle-delivery-extraordinary-approval',
      'data'     => [
        'sent_by_name'         => $sentBy?->name ?? 'Usuario del sistema',
        'client_name'          => $delivery->client?->full_name ?? '-',
        'vehicle_vin'          => $delivery->vehicle?->vin ?? '-',
        'scheduled_date'       => Carbon::parse($delivery->scheduled_delivery_date)->format('d/m/Y H:i'),
        'sede_name'            => $delivery->sede?->abreviatura ?? '-',
        'extraordinary_reason' => $delivery->extraordinary_reason ?? '-',
        'observations'         => $delivery->observations,
        'approve_url'          => $approveUrl,
      ],
    ]);
  }

  public function export(Request $request)
  {
    $title = $request->get('title', 'Reporte Entregas de Vehículos');

    $exportService = new ExportService();
    $filters = $exportService->buildFiltersFromRequest($request, ApVehicleDelivery::class);

    $model = new ApVehicleDelivery();
    $data = $model->getReportData($filters);
    $columns = $model->getReportableColumns();
    $styles = $model->getReportStyles();
    $colorRules = method_exists($model, 'getReportColorRules') ? $model->getReportColorRules() : [];

    $filename = \Str::slug($title) . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    return Excel::download(
      new VehicleDeliveryExport($data, $columns, $title, $styles, $colorRules),
      $filename
    );
  }

  public function availableSlots(string $date, ?int $shopId = null, bool $isExtraordinary = false): array
  {
    $day = Carbon::parse($date);
    $dayOfWeek = $day->dayOfWeek;

    if ($dayOfWeek === Carbon::SUNDAY) {
      return ['date' => $date, 'slots' => []];
    }

    $slots = $dayOfWeek === Carbon::SATURDAY
      ? ApVehicleDelivery::SATURDAY_SLOTS
      : ApVehicleDelivery::WEEKDAY_SLOTS;

    $takenQuery = ApVehicleDelivery::whereDate('scheduled_delivery_date', $date)
      ->whereNull('deleted_at');

    if ($shopId !== null) {
      $takenQuery->whereHas('sede', fn($q) => $q->where('shop_id', $shopId));
    }

    $takenDatetimes = $takenQuery->pluck('scheduled_delivery_date')
      ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
      ->toArray();

    $now = now();

    if ($isExtraordinary && !$day->isBefore(now()->addDays(2)->startOfDay())) {
      return ['date' => $date, 'slots' => []];
    }

    $result = [];

    if ($isExtraordinary) {
      // Extraordinario: solo slots que ya están tomados (hoy o mañana, sin filtro de hora)
      foreach ($slots as $time) {
        if (in_array($time, $takenDatetimes, true)) {
          $result[] = [
            'time'      => $time,
            'datetime'  => $date . ' ' . $time . ':00',
            'available' => true,
          ];
        }
      }
    } else {
      $cutoff = $now->copy()->addHours(24);
      foreach ($slots as $time) {
        $slotTime = Carbon::parse($date . ' ' . $time . ':00');

        if (!$slotTime->isAfter($cutoff)) {
          continue;
        }

        $result[] = [
          'time'      => $time,
          'datetime'  => $date . ' ' . $time . ':00',
          'available' => !in_array($time, $takenDatetimes, true),
        ];
      }
    }

    return ['date' => $date, 'slots' => $result];
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
