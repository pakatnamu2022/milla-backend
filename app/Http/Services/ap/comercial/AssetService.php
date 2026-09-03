<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\AssetResource;
use App\Http\Services\BaseService;
use App\Http\Services\ap\comercial\dynamics\AssetMigrationLogService;
use App\Jobs\VerifyAndMigrateAssetJob;
use App\Models\ap\comercial\ApAsset;
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
    protected AssetMigrationLogService $logService
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

    $vehicles = Vehicles::with(['model.family.brand', 'color', 'warehouse.sede'])
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

    return $vehicles->map(fn(Vehicles $v) => [
      'id'             => $v->id,
      'vin'            => $v->vin,
      'plate'          => $v->plate,
      'year'           => $v->year,
      'model'          => $v->model?->version,
      'brand'          => $v->model?->family?->brand?->name,
      'color'          => $v->color?->description,
      'warehouse'      => $v->warehouse?->description,
      'sede'           => $v->warehouse?->sede?->abreviatura,
      'has_asset_account' => (bool) $v->warehouse?->asset_account,
    ]);
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

      $asset = ApAsset::create([
        'ap_vehicle_id'    => $vehicle->id,
        'worker_id'        => $data['worker_id'],
        'assigned_date'    => $data['assigned_date'],
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
