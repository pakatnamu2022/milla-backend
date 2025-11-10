<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Throwable;

class VehiclesService extends BaseService implements BaseServiceInterface
{
  /**
   * Lista vehículos con filtros, búsqueda y paginación
   * @param Request $request
   * @return mixed
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Vehicles::class,
      $request,
      Vehicles::$filters,
      Vehicles::$sorts,
      VehiclesResource::class,
      ['model', 'color', 'engineType', 'status', 'sede', 'warehousePhysical', 'vehicleMovements']
    );
  }

  /**
   * Busca un vehículo por ID
   * @param $id
   * @return Vehicles
   * @throws Exception
   */
  public function find($id): Vehicles
  {
    $vehicle = Vehicles::where('id', $id)->first();
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }
    return $vehicle;
  }

  /**
   * Crea un nuevo vehículo
   * @param mixed $data
   * @return Vehicles
   * @throws Exception|Throwable
   */
  public function store(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data = $this->enrichData($data);

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Enriquece los datos del vehículo antes de crear
   * @param mixed $data
   * @return mixed
   * @throws Exception
   */
  protected function enrichData(mixed $data)
  {
    // Establecer estado inicial del vehículo
    if (!isset($data['ap_vehicle_status_id'])) {
      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
    }

    // Validar que el VIN no exista
    $existingVehicle = Vehicles::where('vin', $data['vin'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingVehicle) {
      throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
    }

    // Validar que el número de motor no exista
    $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingEngine) {
      throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
    }

    if (!$data['type_operation_id']) $data['type_operation_id'] = Constants::TYPE_OPERATION_POSTVENTA_ID;

    return $data;
  }

  /**
   * Muestra un vehículo por ID
   * @param int $id
   * @return VehiclesResource
   * @throws Exception
   */
  public function show(int $id): JsonResource
  {
    $vehicle = $this->find($id);
    return new VehiclesResource($vehicle);
  }

  /**
   * Actualiza un vehículo
   * @param mixed $data
   * @return Vehicles
   * @throws Exception|Throwable
   */
  public function update(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($data['id']);

      // Si se actualiza el VIN, validar que no exista
      if (isset($data['vin']) && $data['vin'] !== $vehicle->vin) {
        $existingVehicle = Vehicles::where('vin', $data['vin'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingVehicle) {
          throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
        }
      }

      // Si se actualiza el número de motor, validar que no exista
      if (isset($data['engine_number']) && $data['engine_number'] !== $vehicle->engine_number) {
        $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingEngine) {
          throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
        }
      }

      $vehicle->update($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina un vehículo (soft delete)
   * @param $id
   * @return void
   * @throws Exception|Throwable
   */
  public function destroy($id): array
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($id);
      $vehicle->delete();
      DB::commit();
      return ['message' => 'Vehículo eliminado correctamente'];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Lista todos los vehículos con sus costos (sin movements)
   * @param Request $request
   * @return mixed
   */
  public function listWithCosts(Request $request)
  {
    $query = Vehicles::with([
      'model',
      'color',
      'engineType',
      'vehicleStatus',
      'warehousePhysical',
      'purchaseOrders.items' // Cambiado: ahora usa la relación correcta hasManyThrough
    ]);

    // Aplicar filtros si existen
    if ($request->has('search') && $request->search) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('vin', 'like', "%{$search}%")
          ->orWhere('engine_number', 'like', "%{$search}%")
          ->orWhere('year', 'like', "%{$search}%");
      });
    }

    if ($request->has('ap_models_vn_id') && $request->ap_models_vn_id) {
      $query->where('ap_models_vn_id', $request->ap_models_vn_id);
    }

    if ($request->has('ap_vehicle_status_id') && $request->ap_vehicle_status_id) {
      $query->where('ap_vehicle_status_id', $request->ap_vehicle_status_id);
    }

    if ($request->has('vehicle_color_id') && $request->vehicle_color_id) {
      $query->where('vehicle_color_id', $request->vehicle_color_id);
    }

    if ($request->has('warehouse_physical_id') && $request->warehouse_physical_id) {
      $query->where('warehouse_physical_id', $request->warehouse_physical_id);
    }

    // Verificar si se solicita todos los registros sin paginación
    $all = filter_var($request->get('all', false), FILTER_VALIDATE_BOOLEAN);

    // Obtener vehículos (paginados o todos)
    if ($all) {
      $vehicles = $query->get();
    } else {
      $perPage = $request->get('per_page', 15);
      $vehicles = $query->paginate($perPage);
    }

    // Función de transformación para incluir costos
    $transformVehicle = function ($vehicle) {
      // Obtener la primera orden de compra del vehículo (puedes cambiar a last() si necesitas la última)
      $firstPurchaseOrder = $vehicle->purchaseOrders->first();

      // Obtener el costo facturado (subtotal de la orden de compra)
      $billedCost = $firstPurchaseOrder?->subtotal ?? 0;

      // Obtener transport_cost del modelo (temporal)
      $freightCost = $vehicle->model?->transport_cost ?? 0;

      return [
        'id' => $vehicle->id,
        'vin' => $vehicle->vin,
        'year' => $vehicle->year,
        'engine_number' => $vehicle->engine_number,
        'ap_models_vn_id' => $vehicle->ap_models_vn_id,
        'vehicle_color_id' => $vehicle->vehicle_color_id,
        'engine_type_id' => $vehicle->engine_type_id,
        'ap_vehicle_status_id' => $vehicle->ap_vehicle_status_id,
        'model' => $vehicle->model?->version,
        'model_code' => $vehicle->model?->code,
        'family' => $vehicle->model?->family?->description,
        'vehicle_color' => $vehicle->color?->description,
        'engine_type' => $vehicle->engineType?->description,
        'status' => $vehicle->status,
        'vehicle_status' => $vehicle->vehicleStatus?->description,
        'status_color' => $vehicle->vehicleStatus?->color,
        'warehouse_physical_id' => $vehicle->warehouse_physical_id,
        'warehouse_physical' => $vehicle->warehousePhysical?->description,
        'billed_cost' => $billedCost,
        'freight_cost' => $freightCost,
      ];
    };

    // Transformar los datos según el tipo de resultado
    if ($all) {
      // Si es 'all', devolver array simple sin paginación
      $transformedData = $vehicles->map($transformVehicle);
      return response()->json($transformedData);
    } else {
      // Si está paginado, transformar la colección y mantener metadatos de paginación
      $vehicles->getCollection()->transform($transformVehicle);
      return response()->json($vehicles);
    }
  }

  /**
   * Obtiene las facturas (documentos electrónicos) asociadas a un vehículo
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getInvoices(int $vehicleId)
  {
    $vehicle = $this->find($vehicleId);

    // Obtener los documentos electrónicos con sus relaciones
    $documents = $vehicle->electronicDocuments()
      ->with([
        'documentType',
        'transactionType',
        'identityDocumentType',
        'currency',
        'vehicleMovement',
        'items',
        'creator'
      ])
      ->where('anulado', false)
      ->orderBy('fecha_de_emision', 'desc')
      ->get();

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'documents' => ElectronicDocumentResource::collection($documents),
      'total_documents' => $documents->count(),
      'total_amount' => $documents->sum('total'),
    ]);
  }

  /**
   * Obtiene información del cliente asociado a un vehículo y su estado de deuda
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getVehicleClientDebtInfo(int $vehicleId)
  {
    // Buscar el vehículo
    $vehicle = $this->find($vehicleId);

    // Obtener el movimiento más reciente del vehículo para conseguir el cliente
    $vehicleMovement = $vehicle->vehicleMovements()
      ->orderBy('created_at', 'desc')
      ->first();

    if (!$vehicleMovement || !$vehicleMovement->client_id) {
      throw new Exception('No se encontró cliente asociado al vehículo');
    }

    $clientId = $vehicleMovement->client_id;
    $client = $vehicleMovement->client;

    // Obtener todos los documentos electrónicos del cliente aceptados y no anulados
    $documents = ElectronicDocument::where('client_id', $clientId)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->with(['documentType', 'currency', 'installments'])
      ->get();

    // Calcular deuda total
    $totalDebt = 0;
    $facturas = [];
    $notasCredito = [];
    $notasDebito = [];

    foreach ($documents as $doc) {
      $docInfo = [
        'id' => $doc->id,
        'serie' => $doc->serie,
        'numero' => $doc->numero,
        'document_number' => $doc->document_number,
        'fecha_emision' => $doc->fecha_de_emision?->format('Y-m-d'),
        'fecha_vencimiento' => $doc->fecha_de_vencimiento?->format('Y-m-d'),
        'moneda' => $doc->currency?->description,
        'total' => $doc->total,
        'tipo_documento' => $doc->documentType?->description,
      ];

      // Facturas y boletas suman a la deuda
      if (in_array($doc->sunat_concept_document_type_id, [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])) {
        $totalDebt += $doc->total;
        $facturas[] = $docInfo;
      } // Notas de crédito restan de la deuda
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
        $totalDebt -= $doc->total;
        $notasCredito[] = $docInfo;
      } // Notas de débito suman a la deuda
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_DEBITO) {
        $totalDebt += $doc->total;
        $notasDebito[] = $docInfo;
      }
    }

    // Determinar estado de la deuda
    $debtStatus = 'sin_deuda';
    $debtMessage = 'El cliente no tiene deuda pendiente';

    if ($totalDebt > 0) {
      $debtStatus = 'deuda_pendiente';
      $debtMessage = 'El cliente tiene deuda pendiente';
    } elseif ($totalDebt < 0) {
      $debtStatus = 'saldo_a_favor';
      $debtMessage = 'El cliente tiene saldo a favor';
    }

    return response()->json([
      'client' => [
        'id' => $client->id,
        'ruc' => $client->ruc,
        'business_name' => $client->business_name,
        'trade_name' => $client->trade_name,
        'address' => $client->address,
        'email' => $client->email,
      ],
      'debt_summary' => [
        'total_debt' => round($totalDebt, 2),
        'status' => $debtStatus,
        'message' => $debtMessage,
        'has_pending_debt' => $totalDebt > 0,
        'debt_is_paid' => $totalDebt <= 0,
      ],
      'documents_summary' => [
        'total_documents' => $documents->count(),
        'total_facturas' => count($facturas),
        'total_notas_credito' => count($notasCredito),
        'total_notas_debito' => count($notasDebito),
      ],
      'facturas' => $facturas,
      'notas_credito' => $notasCredito,
      'notas_debito' => $notasDebito,
    ]);
  }
}
