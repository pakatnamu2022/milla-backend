<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\AssetResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\BaseService;
use App\Http\Services\ap\comercial\dynamics\AssetMigrationLogService;
use App\Jobs\VerifyAndMigrateAssetJob;
use App\Models\ap\comercial\ApAsset;
use App\Models\ap\comercial\ApReceivingInspection;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AssetService extends BaseService
{
  const array WITH_RELATIONS = [
    'vehicle.model.family.brand',
    'vehicle.color',
    'vehicle.vehicleStatus',
    'vehicle.warehouse.sede',
    'worker',
  ];

  public function __construct(
    protected VehicleMovementService  $vehicleMovementService,
    protected AssetMigrationLogService $logService,
    protected VehiclesService          $vehiclesService
  ) {}

  public function list(Request $request)
  {
    $query = ApAsset::with(self::WITH_RELATIONS);

    return $this->getFilteredResults(
      $query,
      $request,
      ApAsset::filters,
      ApAsset::sorts,
      AssetResource::class
    );
  }

  public function show(int $id): JsonResource
  {
    $record = ApAsset::with(self::WITH_RELATIONS)->findOrFail($id);
    return AssetResource::make($record);
  }

  /**
   * Vehículos elegibles para pasar a activo: en INVENTARIO_VN, sin cotización,
   * sin activo previo, sin guía de entrega ni documentos electrónicos.
   */
  public function eligibleVehicles(Request $request)
  {
    $search = $request->query('search');

    $vehicles = Vehicles::with(['model.family.brand', 'color', 'warehouse.sede', 'shippingGuideReceiving'])
      ->where('ap_vehicle_status_id', ApVehicleStatus::INVENTARIO_VN)
      ->whereDoesntHave('purchaseRequestQuote')
      ->whereDoesntHave('electronicDocuments')
      ->whereNotIn('id', ApAsset::query()->select('ap_vehicle_id'))
      ->when($search, function ($q) use ($search) {
        $q->where(function ($sub) use ($search) {
          $sub->where('vin', 'like', "%{$search}%")
            ->orWhere('plate', 'like', "%{$search}%");
        });
      })
      ->orderBy('vin')
      ->limit(50)
      ->get()
      ->filter(fn(Vehicles $v) => !$v->has_delivery_guide)
      ->values();

    return [
      'data' => $vehicles->map(fn(Vehicles $v) => $this->mapEligibleVehicle($v))->all(),
    ];
  }

  /**
   * Datos del vehículo elegible + detalle de la guía de recepción de compra
   * (se muestra al seleccionar el VIN en el formulario de alta).
   */
  private function mapEligibleVehicle(Vehicles $vehicle): array
  {
    $reception = $this->receptionGuide($vehicle);

    return [
      'id'                => $vehicle->id,
      'vin'               => $vehicle->vin,
      'plate'             => $vehicle->plate,
      'year'              => $vehicle->year,
      'model'             => $vehicle->model?->version,
      'brand'             => $vehicle->model?->family?->brand?->name,
      'color'             => $vehicle->color?->description,
      'warehouse'         => $vehicle->warehouse?->description,
      'sede'              => $vehicle->warehouse?->sede?->abreviatura,
      'has_asset_account' => (bool) $vehicle->warehouse?->asset_account,
      'reception'         => $reception ? [
        'number'        => $reception->document_number
          ?? trim(($reception->series ?? '') . '-' . ($reception->correlative ?? '')) ?: null,
        'issue_date'    => $reception->issue_date?->format('Y-m-d'),
        'received_date' => $reception->received_date?->format('Y-m-d'),
      ] : null,
    ];
  }

  /**
   * Detalle de un vehículo elegible + inspección de recepción (fotos y daños),
   * usado al seleccionar el VIN en el formulario de alta.
   */
  public function eligibleVehicleDetail(int $id): array
  {
    $vehicle = Vehicles::with(['model.family.brand', 'color', 'warehouse.sede'])->findOrFail($id);
    $data = $this->mapEligibleVehicle($vehicle);

    $reception = $this->receptionGuide($vehicle);
    if ($reception) {
      $inspection = ApReceivingInspection::with('damages')
        ->where('shipping_guide_id', $reception->id)
        ->latest('id')
        ->first();

      $data['reception']['inspection'] = $inspection ? [
        'general_observations' => $inspection->general_observations,
        'inspected_by_name'    => $inspection->inspectedBy?->name,
        'photos'               => array_values(array_filter([
          ['label' => 'Frontal',        'url' => $inspection->photo_front_url],
          ['label' => 'Posterior',      'url' => $inspection->photo_back_url],
          ['label' => 'Lado izquierdo', 'url' => $inspection->photo_left_url],
          ['label' => 'Lado derecho',   'url' => $inspection->photo_right_url],
          ['label' => 'VIN',            'url' => $inspection->photo_vin_url],
        ], fn($p) => !empty($p['url']))),
        'damages' => $inspection->damages->map(fn($d) => [
          'damage_type' => $d->damage_type,
          'description' => $d->description,
          'photo_url'   => $d->photo_url,
        ])->all(),
      ] : null;
    }

    return $data;
  }

  /**
   * Guía de remisión (GR) de recepción de compra del vehículo. Su fecha de emisión
   * es la que se usa como fecha de asignación del activo por defecto.
   */
  private function receptionGuide(Vehicles $vehicle): ?ShippingGuides
  {
    return $vehicle->shippingGuides()
      ->where('shipping_guides.document_type', ShippingGuides::DOCUMENT_TYPE_GR)
      ->whereHas('receivingChecklists')
      ->orderBy('shipping_guides.issue_date')
      ->first();
  }

  /**
   * @throws Exception
   */
  public function store(array $data): JsonResource
  {
    return DB::transaction(function () use ($data) {
      $vehicle = Vehicles::with(['warehouse.sede', 'model'])->findOrFail($data['ap_vehicle_id']);

      if ((int) $vehicle->ap_vehicle_status_id !== ApVehicleStatus::INVENTARIO_VN) {
        throw new Exception('El vehículo no está en estado INVENTARIO VN.');
      }

      if ($vehicle->purchaseRequestQuote()->exists()) {
        throw new Exception('El vehículo tiene una cotización asociada y no puede pasar a activo.');
      }

      if ($vehicle->electronicDocuments()->exists()) {
        throw new Exception('El vehículo tiene documentos electrónicos asociados y no puede pasar a activo.');
      }

      if (ApAsset::where('ap_vehicle_id', $vehicle->id)->exists()) {
        throw new Exception('El vehículo ya fue registrado como activo.');
      }

      $warehouse = $vehicle->warehouse ?? throw new Exception('El vehículo no tiene almacén asignado.');
      if (empty($warehouse->asset_account)) {
        throw new Exception('El almacén "' . $warehouse->description . '" no tiene configurada la Cuenta de Activos. Configúrela en Almacenes antes de continuar.');
      }

      $worker = Worker::withoutGlobalScope('working')->find($data['worker_id']);
      if (!$worker || !Worker::working()->whereKey($data['worker_id'])->exists()) {
        throw new Exception('El trabajador seleccionado no está activo.');
      }

      $assignedDate = $data['assigned_date']
        ?? $this->receptionGuide($vehicle)?->issue_date?->format('Y-m-d')
        ?? now()->toDateString();

      $asset = ApAsset::create([
        'ap_vehicle_id'    => $vehicle->id,
        'worker_id'        => $data['worker_id'],
        'assigned_date'    => $assignedDate,
        'observation'      => $data['observation'] ?? null,
        'migration_status' => ApAsset::MIGRATION_STATUS_PENDING,
        'created_by'       => auth()->id(),
      ]);

      $this->vehicleMovementService->storeAssetVehicleMovement(
        $vehicle,
        'Conversión a activo fijo | Responsable: ' . $worker->nombre_completo
      );

      $this->logService->ensureAssetLogsExist($asset);

      VerifyAndMigrateAssetJob::dispatch($asset->id);

      $asset->load(self::WITH_RELATIONS);
      return AssetResource::make($asset);
    });
  }

  public function dispatchMigration(int $id): array
  {
    $asset = ApAsset::findOrFail($id);
    VerifyAndMigrateAssetJob::dispatch($asset->id);
    return ['message' => 'Migración despachada correctamente'];
  }

  /**
   * @throws Exception
   */
  public function destroy(int $id): array
  {
    $asset = ApAsset::findOrFail($id);

    if ($asset->migration_status === ApAsset::MIGRATION_STATUS_COMPLETED) {
      throw new Exception('No se puede eliminar un activo ya sincronizado con Dynamics.');
    }

    return DB::transaction(function () use ($asset) {
      $vehicle = $asset->vehicle;
      if ($vehicle && (int) $vehicle->ap_vehicle_status_id === ApVehicleStatus::ACTIVO) {
        $vehicle->update(['ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN]);
      }
      $asset->delete();
      return ['message' => 'Activo eliminado correctamente'];
    });
  }
}
