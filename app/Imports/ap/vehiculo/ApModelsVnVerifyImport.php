<?php

namespace App\Imports\ap\vehiculo;

use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ApModelsVnVerifyImport implements ToCollection, WithHeadingRow
{
  private array $results = [
    'rows_processed' => 0,
    'existing'       => [],
    'not_found'      => [],
  ];

  private Collection $combustibles;

  public function __construct()
  {
    $this->combustibles = ApFuelType::where('status', true)->pluck('id', 'description');
  }

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2;
      $nro = $row['n'] ?? null;
      if (!is_numeric($nro) || (int) $nro <= 0) {
        continue;
      }

      $this->results['rows_processed']++;

      $version   = $this->val($row['version_descripcion_del_modelo'] ?? null);
      $combRaw   = $this->val($row['combustible'] ?? null);
      $modelYear = isset($row['ano_del_modelo']) && $row['ano_del_modelo'] !== ''
        ? (int) $row['ano_del_modelo']
        : null;

      if (!$version || !$combRaw) {
        $this->results['not_found'][] = [
          'fila'      => $rowNumber,
          'version'   => $version ?? '(vacío)',
          'model_year'=> $modelYear,
          'fuel'      => $combRaw ?? '(vacío)',
          'motivo'    => 'Versión o Combustible vacíos',
        ];
        continue;
      }

      $fuelId = $this->combustibles->get($combRaw);
      if (!$fuelId) {
        $this->results['not_found'][] = [
          'fila'      => $rowNumber,
          'version'   => $version,
          'model_year'=> $modelYear,
          'fuel'      => $combRaw,
          'motivo'    => "Combustible '{$combRaw}' no existe en el catálogo",
        ];
        continue;
      }

      $model = ApModelsVn::where('version', strtoupper(Str::ascii($version)))
        ->where('model_year', $modelYear)
        ->where('fuel_id', $fuelId)
        ->whereNull('deleted_at')
        ->select('id', 'code', 'version', 'model_year', 'fuel_id')
        ->first();

      if ($model) {
        $this->results['existing'][] = [
          'fila'      => $rowNumber,
          'id'        => $model->id,
          'code'      => $model->code,
          'version'   => $model->version,
          'model_year'=> $model->model_year,
          'fuel'      => $combRaw,
        ];
      } else {
        $this->results['not_found'][] = [
          'fila'      => $rowNumber,
          'version'   => $version,
          'model_year'=> $modelYear,
          'fuel'      => $combRaw,
          'motivo'    => 'No existe en el sistema',
        ];
      }
    }
  }

  public function getResults(): array
  {
    return $this->results;
  }

  private function val(mixed $value): ?string
  {
    if ($value === null || $value === '') return null;
    $normalized = strtoupper(Str::ascii(trim((string) $value)));
    return $normalized === '' ? null : $normalized;
  }
}
