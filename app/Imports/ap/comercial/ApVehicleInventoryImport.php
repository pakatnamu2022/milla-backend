<?php

namespace App\Imports\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApVehicleInventory;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ApVehicleInventoryImport implements ToCollection, WithHeadingRow
{
  private array $results = [
    'created' => 0,
    'updated' => 0,
    'errors' => [],
    'rows_processed' => 0,
  ];

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // +2 porque la fila 1 es el header
      try {
        $this->processRow($row->toArray(), $rowNumber);
        $this->results['rows_processed']++;
      } catch (Exception $e) {
        $this->results['errors'][] = "Fila {$rowNumber} (VIN: " . ($row['vin'] ?? 'N/A') . "): " . $e->getMessage();
      }
    }
  }

  private function processRow(array $row, int $rowNumber): void
  {
    $vin = strtoupper(trim($row['vin'] ?? ''));
    if (empty($vin)) {
      throw new Exception('El VIN es requerido');
    }

    $colorId = $this->resolveColor($row['color'] ?? null);
    $brandId = $this->resolveBrand($row['marca'] ?? null);
    $modelId = $this->resolveModel($row['modelo'] ?? null, $brandId);
    $year = $this->parseYear($row['ano'] ?? $row['año'] ?? null);
    $fuelTypeId = $this->resolveFuelType($row['gasolina'] ?? $row['combustible'] ?? null);
    $adjudicationDate = $this->parseDate($row['fecha_de_adjudicacion'] ?? $row['fecha_adjudicacion'] ?? null);
    $days = isset($row['dias']) ? (int) $row['dias'] : null;
    $limitDate = $this->parseDate($row['fecha_limite'] ?? null);
    $receptionDate = $this->parseDate($row['fecha_recepcion'] ?? null);
    $warehouseId = $this->resolveWarehouse($row['sede'] ?? $row['warehouse'] ?? $row['almacen'] ?? null);

    DB::beginTransaction();
    try {
      // Buscar vehículo existente por VIN
      $vehicle = Vehicles::where('vin', $vin)->first();

      $vehicleId = $vehicle?->id;

      // Determinar si está donde el inventario dice que está
      $isLocationConfirmed = false;
      if ($vehicle && $warehouseId) {
        $vehicleWarehouseId = $vehicle->warehouse_id ?? $vehicle->warehouse_physical_id;
        $isLocationConfirmed = $vehicleWarehouseId === $warehouseId;
      }

      // Si ya existe un registro de inventario para este VIN, actualizar; sino crear
      $inventoryRecord = ApVehicleInventory::where('vin', $vin)->first();

      $data = [
        'ap_vehicle_id' => $vehicleId,
        'inventory_warehouse_id' => $warehouseId,
        'vin' => $vin,
        'vehicle_color_id' => $colorId,
        'brand_id' => $brandId,
        'model_id' => $modelId,
        'year' => $year,
        'fuel_type_id' => $fuelTypeId,
        'adjudication_date' => $adjudicationDate,
        'days' => $days,
        'limit_date' => $limitDate,
        'reception_date' => $receptionDate,
        'is_location_confirmed' => $isLocationConfirmed,
        'is_evaluated' => false,
        'status' => true,
      ];

      if ($inventoryRecord) {
        $inventoryRecord->update($data);
        $this->results['updated']++;
      } else {
        ApVehicleInventory::create($data);
        $this->results['created']++;
      }

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function resolveColor(?string $colorName): ?int
  {
    if (empty($colorName)) {
      return null;
    }

    $normalized = strtoupper(Str::ascii(trim($colorName)));

    $color = ApMasters::where('type', 'COLOR')
      ->where('description', $normalized)
      ->first();

    if (!$color) {
      $color = ApMasters::create([
        'code' => $normalized,
        'description' => $normalized,
        'type' => 'COLOR',
        'status' => true,
      ]);
    }

    return $color->id;
  }

  private function resolveBrand(?string $brandName): ?int
  {
    if (empty($brandName)) {
      return null;
    }

    $normalized = strtoupper(Str::ascii(trim($brandName)));
    $brand = ApVehicleBrand::where('name', $normalized)->first();

    return $brand?->id;
  }

  private function resolveModel(?string $modelVersion, ?int $brandId): ?int
  {
    if (empty($modelVersion)) {
      return null;
    }

    $normalized = strtoupper(Str::ascii(trim($modelVersion)));

    // Buscar modelo existente por versión
    $modelQuery = ApModelsVn::where('version', $normalized);

    if ($brandId) {
      // Filtrar por familia de la marca si se conoce la marca
      $familyIds = ApFamilies::where('brand_id', $brandId)->pluck('id');
      if ($familyIds->isNotEmpty()) {
        $modelQuery->whereIn('family_id', $familyIds);
      }
    }

    $model = $modelQuery->first();

    if (!$model && $brandId) {
      // Obtener la primera familia de la marca para crear el modelo
      $family = ApFamilies::where('brand_id', $brandId)->first();

      if ($family) {
        $model = ApModelsVn::firstOrCreate(
          ['version' => $normalized, 'family_id' => $family->id],
          [
            'code' => $normalized,
            'version' => $normalized,
            'family_id' => $family->id,
            'type_operation_id' => ApMasters::TIPO_OPERACION_COMERCIAL,
            'status' => true,
          ]
        );
      }
    }

    return $model?->id;
  }

  private function resolveFuelType(?string $fuelName): ?int
  {
    if (empty($fuelName)) {
      return null;
    }

    $normalized = strtoupper(Str::ascii(trim($fuelName)));

    $fuelType = ApFuelType::where('description', $normalized)->first();

    if (!$fuelType) {
      $fuelType = ApFuelType::firstOrCreate(
        ['description' => $normalized],
        [
          'code' => substr($normalized, 0, 10),
          'description' => $normalized,
          'electric_motor' => false,
          'status' => true,
        ]
      );
    }

    return $fuelType->id;
  }

  private function resolveWarehouse(?string $warehouseDesc): ?int
  {
    if (empty($warehouseDesc)) {
      return null;
    }

    $normalized = strtoupper(trim($warehouseDesc));

    $warehouse = Warehouse::where('description', 'LIKE', "%{$normalized}%")
      ->orWhere('dyn_code', 'LIKE', "%{$normalized}%")
      ->first();

    return $warehouse?->id;
  }

  private function parseDate($value): ?string
  {
    if (empty($value)) {
      return null;
    }

    try {
      if (is_numeric($value)) {
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
      }
      return Carbon::parse($value)->format('Y-m-d');
    } catch (Exception $e) {
      return null;
    }
  }

  private function parseYear($value): ?int
  {
    if (empty($value)) {
      return null;
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    try {
      return (int) Carbon::parse($value)->year;
    } catch (Exception $e) {
      return null;
    }
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
