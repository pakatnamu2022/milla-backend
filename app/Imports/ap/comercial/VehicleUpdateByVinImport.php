<?php

namespace App\Imports\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VehicleUpdateByVinImport implements ToCollection, WithHeadingRow
{
  private array $results = [
    'updated'        => 0,
    'errors'         => [],
    'rows_processed' => 0,
  ];

  private Collection $engineTypeMap;

  public function collection(Collection $rows): void
  {
    // Carga el mapa de tipos de motor una sola vez, indexado por code (como entero)
    // para evitar problemas de CAST en MySQL y discrepancias de typos en descripción
    $this->engineTypeMap = ApMasters::where('type', 'TIPO_MOTOR')
      ->whereNull('deleted_at')
      ->get()
      ->keyBy(fn($m) => (int) $m->code);

    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2;
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
    $vin   = strtoupper(trim($row['vin']   ?? ''));
    $motor = strtoupper(trim($row['motor'] ?? ''));
    $color = trim($row['color'] ?? '');

    if (empty($vin))   throw new Exception('El VIN es requerido');
    if (empty($motor)) throw new Exception('El número de motor es requerido');
    if (empty($color)) throw new Exception('El color es requerido');

    $vehicle = Vehicles::where('vin', $vin)->whereNull('deleted_at')->first();
    if (!$vehicle) throw new Exception("No se encontró vehículo con VIN {$vin}");

    $model = ApModelsVn::find($vehicle->ap_models_vn_id);
    if (!$model) throw new Exception("El vehículo no tiene modelo asignado");

    $fuelType    = ApFuelType::find($model->fuel_id);
    $engineTypeId = $fuelType
      ? ($this->engineTypeMap->get((int) $fuelType->code)?->id ?? ApMasters::ENGINE_TYPE_OTHERS_ID)
      : ApMasters::ENGINE_TYPE_OTHERS_ID;

    $normalized = Str::upper(Str::ascii($color));
    $colorRecord = ApMasters::where('type', 'COLOR')->where('description', $normalized)->first();
    if (!$colorRecord) {
      $colorRecord = ApMasters::create([
        'code'        => $normalized,
        'description' => $normalized,
        'type'        => 'COLOR',
        'status'      => 1,
      ]);
    }

    DB::beginTransaction();
    try {
      $vehicle->update([
        'engine_number'    => $motor,
        'vehicle_color_id' => $colorRecord->id,
        'engine_type_id'   => $engineTypeId,
      ]);
      DB::commit();
      $this->results['updated']++;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
