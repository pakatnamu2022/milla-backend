<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Utils\Constants;
use App\Imports\ap\comercial\VehicleUpdateByVinImport;
use App\Imports\ap\comercial\VehiclePurchaseOrderUpdateByVinImport;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApReceivingAccessoryStatus;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VehiclesService extends BaseService implements BaseServiceInterface
{

  public function exportAll(Request $request)
  {
    $request->merge([
      'title' => $request->get('title', 'Reporte General de Vehículos'),
    ]);

    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, Vehicles::class);
  }

  public function exportSales(Request $request)
  {
    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, Vehicles::class);
  }

  public function exportDelivery(Request $request)
  {
    $request->merge([
      'columns' => [
        'electronicDocumentParent.identityDocumentType.description',
        'electronicDocumentParent.cliente_denominacion',
        'electronicDocumentParent.cliente_numero_de_documento',
        'model.family.brand.name',
        'model.family.description',
        'vin',
        'plate',
        'electronicDocumentParent.seriesModel.sede.shop.description',
        'electronicDocumentParent.sale_date',
        'vehicleDelivery.real_delivery_date',
        'electronicDocumentParent.cliente_email',
        'electronicDocumentParent.client_phone',
        'vehicleDelivery.advisor.nombre_completo',
      ],
      'title' => $request->get('title', 'Consolidado Entregas Vehículos Nuevos'),
    ]);

    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, Vehicles::class);
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Vehicles::with('purchaseOrder'),
      $request,
      Vehicles::filters,
      Vehicles::sorts,
      VehiclesResource::class
    );
  }

  public function find($id): Vehicles
  {
    $vehicle = Vehicles::where('id', $id)->first();
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }
    return $vehicle;
  }

  public function store(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data = $this->enrichData($data);

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      // Si es tipo de operación comercial, crear movimiento en consignación
      if (($data['type_operation_id'] ?? null) == ApMasters::TIPO_OPERACION_COMERCIAL) {
        $movementService = new VehicleMovementService();
        $movementService->storeConsignmentVehicleMovement($vehicle->id);
      }

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function storeReplacement(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data['year'] = now()->year;
      $data['year_delivery'] = now()->year;
      $data['vehicle_color_id'] = ApMasters::COLOR_OTHERS_ID;
      $data['engine_type_id'] = ApMasters::ENGINE_TYPE_OTHERS_ID;
      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
      $data['type_operation_id'] = ApMasters::TIPO_OPERACION_POSTVENTA;

      // Obtener el almacén físico de postventa usando la sede
      $warehouse = Warehouse::getPhysicalWarehouseForPostsale($data['sede_id']);

      if (!$warehouse) {
        throw new Exception("No se encontró un almacén físico de postventa para la sede especificada");
      }

      // Setear warehouse_id y warehouse_physical_id con el mismo valor
      $data['warehouse_id'] = $warehouse->id;
      $data['warehouse_physical_id'] = $warehouse->id;

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  protected function enrichData(mixed $data)
  {
    // Establecer estado inicial del vehículo
    if (!isset($data['ap_vehicle_status_id'])) {
      $data['ap_vehicle_status_id'] = ApVehicleStatus::VENDIDO_ENTREGADO;
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

    if (!$data['type_operation_id']) $data['type_operation_id'] = ApMasters::TIPO_OPERACION_POSTVENTA;

    if (!isset($data['warehouse_id'])) {
      $data['warehouse_id'] = $data['warehouse_physical_id'] ?? null;
    }

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

      if (!isset($data['warehouse_id'])) {
        $data['warehouse_id'] = $data['warehouse_physical_id'] ?? null;
      }

      // No permitir actualizar warehouse_physical_id aunque se envíe
      unset($data['warehouse_physical_id']);

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

  public function updateByVin(UploadedFile $file): array
  {
    $import = new VehicleUpdateByVinImport();
    Excel::import($import, $file);
    return $import->getResults();
  }

  public function updatePurchaseOrderByVin(UploadedFile $file): array
  {
    $import = new VehiclePurchaseOrderUpdateByVinImport();
    Excel::import($import, $file);
    return $import->getResults();
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
    ])->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->whereIn('ap_vehicle_status_id', [
        ApVehicleStatus::INVENTARIO_VN,
        ApVehicleStatus::VEHICULO_EN_TRAVESIA
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

    if ($request->has('vehicle_color_id') && $request->vehicle_color_id) {
      $query->where('vehicle_color_id', $request->vehicle_color_id);
    }

    if ($request->has('warehouse_physical_id') && $request->warehouse_physical_id) {
      $query->where('warehouse_physical_id', $request->warehouse_physical_id);
    }

    // family_id
    if ($request->has('family_id') && $request->family_id) {
      $query->whereHas('model.family', function ($q) use ($request) {
        $q->where('id', $request->family_id);
      });
    }

    // Excluir vehículos ya asignados a otro PurchaseRequestQuote (solo al crear)
    $isEditing = filter_var($request->get('is_editing', false), FILTER_VALIDATE_BOOLEAN);
    if (!$isEditing) {
      $excludeQuoteId = $request->get('purchase_request_quote_id');
      $query->where(function ($q) use ($excludeQuoteId) {
        $q->whereDoesntHave('purchaseRequestQuote');
        if ($excludeQuoteId) {
          $q->orWhereHas('purchaseRequestQuote', function ($subQ) use ($excludeQuoteId) {
            $subQ->where('id', $excludeQuoteId);
          });
        }
      });
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
        'billed_cost' => $vehicle->purchase_price,
        'freight_cost' => $freightCost,
        'warehouse' => $vehicle->warehouse?->description,
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
    // Usar el método centralizado para obtener vehículo, documento y cliente
    $data = Vehicles::getElectronicDocumentWithClient($vehicleId);

    $vehicle = $data->vehicle;

    // Cargar datos de recepción
    $vehicle->load([
      'shippingGuideReceiving.receivingChecklists.receiving',
      'shippingGuideReceiving.receivingInspection.damages',
      'shippingGuideReceiving.receivingInspection.inspectedBy',
      'shippingGuideReceiving.receivedBy',
    ]);
    $electronicDocument = $data->electronicDocument;
    $client = $data->client;
    $purchaseRequestQuote = $electronicDocument->purchaseRequestQuote;

    // Obtener el monto total de la venta (sale_price de la cotización)
    $totalSalePrice = $purchaseRequestQuote->sale_price;

    // Obtener todos los documentos electrónicos asociados a esta cotización
    $documents = ElectronicDocument::where('purchase_request_quote_id', $purchaseRequestQuote->id)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->with(['documentType', 'currency', 'installments'])
      ->get();

    // Calcular total pagado
    $totalPaid = 0;
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
        'moneda' => $doc->currency?->description,
        'total' => $doc->total,
        'tipo_documento' => $doc->documentType?->description,
      ];

      // Facturas y boletas suman al total pagado
      if (in_array($doc->sunat_concept_document_type_id, [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])) {
        $totalPaid += $doc->total;
        $facturas[] = $docInfo;
      } // Notas de crédito restan del total pagado
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
        $totalPaid -= $doc->total;
        $notasCredito[] = $docInfo;
      } // Notas de débito suman al total pagado
      elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_DEBITO) {
        $totalPaid += $doc->total;
        $notasDebito[] = $docInfo;
      }
    }

    // Calcular deuda pendiente
    $pendingDebt = $totalSalePrice - $totalPaid;

    $isPaid = $vehicle->is_paid;

    // Determinar estado de la deuda
    $debtStatus = 'Sin deuda';
    $debtMessage = 'El cliente no tiene deuda pendiente';

    if ($pendingDebt > 0.01) {
      $debtStatus = 'Deuda pendiente';
      $debtMessage = 'El cliente tiene deuda pendiente';
    } elseif ($pendingDebt < -0.01) {
      $debtStatus = 'Sobrepago';
      $debtMessage = 'El cliente tiene un sobrepago';
    }

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'client' => [
        'id' => $client->id,
        'num_doc' => $client->num_doc,
        'full_name' => $client->full_name,
        'direction' => $client->direction,
        'email' => $client->email,
      ],
      'purchase_quote' => [
        'id' => $purchaseRequestQuote->id,
        'correlative' => $purchaseRequestQuote->correlative,
        'sale_price' => round($totalSalePrice, 2),
      ],
      'debt_summary' => [
        'total_sale_price' => round($totalSalePrice, 2),
        'total_paid' => round($totalPaid, 2),
        'pending_debt' => round($pendingDebt, 2),
        'status' => $debtStatus,
        'message' => $debtMessage,
        'has_pending_debt' => $pendingDebt > 0.01,
        'debt_is_paid' => $isPaid,
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
      'reception' => $this->buildReceptionData($vehicle),
    ]);
  }

  private function buildReceptionData(Vehicles $vehicle): ?array
  {
    $guide = $vehicle->shippingGuideReceiving;

    if (!$guide) {
      return null;
    }

    $inspection = $guide->receivingInspection;

    $accessoryStatuses = ApReceivingAccessoryStatus::where('shipping_guide_id', $guide->id)->get();

    return [
      'shipping_guide_id' => $guide->id,
      'document_number' => $guide->document_number,
      'issue_date' => $guide->issue_date?->format('Y-m-d'),
      'received_date' => $guide->received_date?->format('Y-m-d H:i:s'),
      'note_received' => $guide->note_received,
      'received_by' => $guide->receivedBy?->name,
      'checklist_items' => $guide->receivingChecklists->map(fn($c) => [
        'id' => $c->id,
        'description' => $c->receiving?->description,
        'quantity' => $c->quantity,
        'kilometers' => $c->kilometers,
      ])->values(),
      'inspection' => $inspection ? [
        'id' => $inspection->id,
        'photo_front_url' => $inspection->photo_front_url,
        'photo_back_url' => $inspection->photo_back_url,
        'photo_left_url' => $inspection->photo_left_url,
        'photo_right_url' => $inspection->photo_right_url,
        'general_observations' => $inspection->general_observations,
        'inspected_by' => $inspection->inspectedBy?->name,
        'created_at' => $inspection->created_at?->format('Y-m-d H:i:s'),
        'damages' => $inspection->damages->map(fn($d) => [
          'id' => $d->id,
          'damage_type' => $d->damage_type,
          'x_coordinate' => $d->x_coordinate,
          'y_coordinate' => $d->y_coordinate,
          'description' => $d->description,
          'photo_url' => $d->photo_url,
        ])->values(),
      ] : null,
      'accessories' => $accessoryStatuses->map(fn($a) => [
        'id' => $a->id,
        'description' => $a->description,
        'quantity' => $a->quantity,
        'received' => $a->received,
        'is_installed' => $a->is_installed,
      ])->values(),
    ];
  }

  /**
   * Obtiene la orden de compra asociada a un vehículo
   * @param int $vehicleId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function getPurchaseOrder(int $vehicleId)
  {
    $vehicle = $this->find($vehicleId);

    // Obtener la orden de compra con sus relaciones
    $purchaseOrder = $vehicle->purchaseOrder()
      ->with([
        'supplier',
        'currency',
        'warehouse',
        'warehouse.articleClass',
        'supplierOrderType',
        'sede',
        'items',
        'items.product',
        'items.unitMeasurement',
        'vehicleMovement',
      ])
      ->first();

    if (!$purchaseOrder) {
      throw new Exception('Este vehículo no tiene una orden de compra asociada');
    }

    return response()->json([
      'vehicle' => VehiclesResource::make($vehicle),
      'purchase_order' => new PurchaseOrderResource($purchaseOrder),
    ]);
  }

  public function updateStatus(int $vehicleId, array $data): array
  {
    $statusId = (int)$data['status_id'];
    $observation = $data['observation'] ?? null;
    $movementDate = $data['movement_date'] ?? now();
    $movementType = $data['movement_type'] ?? null;

    $movementTypeMap = [
      ApVehicleStatus::PEDIDO_VN => VehicleMovement::ORDERED,
      ApVehicleStatus::VEHICULO_EN_TRAVESIA => VehicleMovement::IN_TRANSIT,
      ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO => VehicleMovement::IN_TRANSIT_RETURNED,
      ApVehicleStatus::VENDIDO_NO_ENTREGADO => VehicleMovement::SOLD_NOT_DELIVERED,
      ApVehicleStatus::INVENTARIO_VN => VehicleMovement::INVENTORY,
      ApVehicleStatus::VENDIDO_ENTREGADO => VehicleMovement::SOLD_DELIVERED,
      ApVehicleStatus::FACTURADO => VehicleMovement::INVOICED,
      ApVehicleStatus::CONSIGNACION => VehicleMovement::CONSIGNMENT,
      ApVehicleStatus::FACTURADO_FINAL => VehicleMovement::INVOICED,
    ];

    if (!$movementType) {
      $movementType = $movementTypeMap[$statusId] ?? null;
    }

    if (!$movementType) {
      throw new Exception("No se pudo determinar el tipo de movimiento para el estado ID {$statusId}.");
    }

    $vehicle = $this->find($vehicleId);
    $previousStatusId = $vehicle->ap_vehicle_status_id;

    DB::transaction(function () use ($vehicle, $statusId, $previousStatusId, $movementType, $movementDate, $observation) {
      $vehicle->update(['ap_vehicle_status_id' => $statusId]);

      VehicleMovement::create([
        'movement_type'        => $movementType,
        'ap_vehicle_id'        => $vehicle->id,
        'ap_vehicle_status_id' => $statusId,
        'previous_status_id'   => $previousStatusId,
        'new_status_id'        => $statusId,
        'movement_date'        => $movementDate,
        'observation'          => $observation,
        'created_by'           => auth()->id(),
      ]);
    });

    return [
      'vehicle_id'         => $vehicle->id,
      'previous_status_id' => $previousStatusId,
      'new_status_id'      => $statusId,
      'movement_type'      => $movementType,
    ];
  }
}
