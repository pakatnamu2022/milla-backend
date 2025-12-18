<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApExhibitionVehiclesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Models\ap\comercial\ApExhibitionVehicles;
use App\Models\ap\comercial\ApExhibitionVehicleItems;
use App\Models\ap\comercial\Vehicles;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ApExhibitionVehiclesService extends BaseService implements BaseServiceInterface
{
  /**
   * Export exhibition vehicles data
   */
  public function exportExhibitionVehicles(Request $request)
  {
    $exportService = new ExportService();
    return $exportService->exportFromRequest($request, ApExhibitionVehicles::class);
  }

  /**
   * List exhibition vehicles with filters, search and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApExhibitionVehicles::class,
      $request,
      ApExhibitionVehicles::$filters,
      ApExhibitionVehicles::$sorts,
      ApExhibitionVehiclesResource::class
    );
  }

  /**
   * Find exhibition vehicle by ID
   */
  public function find($id): ApExhibitionVehicles
  {
    $exhibitionVehicle = ApExhibitionVehicles::where('id', $id)->first();
    if (!$exhibitionVehicle) {
      throw new Exception('Vehículo de exhibición no encontrado');
    }
    return $exhibitionVehicle;
  }

  /**
   * Create new exhibition vehicle with items
   * WORKFLOW: Create header + items (vehicles + equipment)
   */
  public function store(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Extract header and items data
      $headerData = $this->extractHeaderData($data);
      $items = $data['items'] ?? [];

      if (empty($items)) {
        throw new Exception('Debe proporcionar al menos un item (vehículo o equipo)');
      }

      // Create header
      $exhibitionVehicle = ApExhibitionVehicles::create($headerData);

      // Create items
      foreach ($items as $itemData) {
        $this->createItem($exhibitionVehicle->id, $itemData);
      }

      // Reload with relationships
      $exhibitionVehicle->load([
        'items.vehicle.model.family.brand',
        'items.vehicle.color',
        'items.vehicle.vehicleStatus',
        'supplier',
        'ubicacion',
        'advisor',
        'propietario',
        'vehicleStatus'
      ]);

      DB::commit();
      return ApExhibitionVehiclesResource::make($exhibitionVehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Show exhibition vehicle by ID
   */
  public function show(int $id): JsonResource
  {
    $exhibitionVehicle = ApExhibitionVehicles::with([
      'items.vehicle.model.family.brand',
      'items.vehicle.color',
      'items.vehicle.vehicleStatus',
      'supplier',
      'ubicacion',
      'advisor',
      'propietario',
      'vehicleStatus'
    ])->findOrFail($id);

    return new ApExhibitionVehiclesResource($exhibitionVehicle);
  }

  /**
   * Update exhibition vehicle
   */
  public function update(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $exhibitionVehicle = $this->find($data['id']);

      // Update header
      $headerData = $this->extractHeaderData($data);
      $exhibitionVehicle->update($headerData);

      // Update items if provided
      if (isset($data['items'])) {
        // Delete existing items
        $exhibitionVehicle->items()->delete();

        // Create new items
        foreach ($data['items'] as $itemData) {
          $this->createItem($exhibitionVehicle->id, $itemData);
        }
      }

      // Reload with relationships
      $exhibitionVehicle->load([
        'items.vehicle.model.family.brand',
        'items.vehicle.color',
        'items.vehicle.vehicleStatus',
        'supplier',
        'ubicacion',
        'advisor',
        'propietario',
        'vehicleStatus'
      ]);

      DB::commit();
      return ApExhibitionVehiclesResource::make($exhibitionVehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete exhibition vehicle (soft delete)
   */
  public function destroy($id): array
  {
    DB::beginTransaction();
    try {
      $exhibitionVehicle = $this->find($id);

      // Soft delete items first
      $exhibitionVehicle->items()->delete();

      // Soft delete header
      $exhibitionVehicle->delete();

      DB::commit();
      return ['message' => 'Vehículo de exhibición eliminado correctamente'];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Create an exhibition vehicle item
   */
  protected function createItem(int $exhibitionVehicleId, array $itemData): ApExhibitionVehicleItems
  {
    $itemType = $itemData['item_type'] ?? null;

    if (!$itemType || !in_array($itemType, ['vehicle', 'equipment'])) {
      throw new Exception('El tipo de item debe ser "vehicle" o "equipment"');
    }

    if ($itemType === 'vehicle') {
      // Create vehicle in ap_vehicles first
      $vehicleData = $itemData['vehicle_data'] ?? [];

      if (empty($vehicleData)) {
        throw new Exception('Debe proporcionar los datos del vehículo (vehicle_data)');
      }

      // Use VehiclesService to create vehicle
      $vehicleService = new VehiclesService();
      $vehicleResource = $vehicleService->store($vehicleData);
      $vehicle = $vehicleResource->resource;

      // Create item with vehicle_id
      return ApExhibitionVehicleItems::create([
        'exhibition_vehicle_id' => $exhibitionVehicleId,
        'item_type' => 'vehicle',
        'vehicle_id' => $vehicle->id,
        'description' => $itemData['description'] ?? $vehicle->vin,
        'quantity' => 1, // Always 1 for vehicles
        'observaciones' => $itemData['observaciones'] ?? null,
        'status' => $itemData['status'] ?? true,
      ]);
    } else {
      // Equipment item
      if (empty($itemData['description'])) {
        throw new Exception('Debe proporcionar una descripción para el equipo');
      }

      return ApExhibitionVehicleItems::create([
        'exhibition_vehicle_id' => $exhibitionVehicleId,
        'item_type' => 'equipment',
        'vehicle_id' => null,
        'description' => $itemData['description'],
        'quantity' => $itemData['quantity'] ?? 1,
        'observaciones' => $itemData['observaciones'] ?? null,
        'status' => $itemData['status'] ?? true,
      ]);
    }
  }

  /**
   * Extract header data from request
   */
  protected function extractHeaderData(array $data): array
  {
    $headerFields = [
      'supplier_id',
      'guia_number',
      'guia_date',
      'llegada',
      'ubicacion_id',
      'advisor_id',
      'propietario_id',
      'ap_vehicle_status_id',
      'pedido_sucursal',
      'dua_number',
      'observaciones',
      'status',
    ];

    return array_filter($data, function ($key) use ($headerFields) {
      return in_array($key, $headerFields);
    }, ARRAY_FILTER_USE_KEY);
  }
}
