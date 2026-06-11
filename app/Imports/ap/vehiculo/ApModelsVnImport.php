<?php

namespace App\Imports\ap\vehiculo;

use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class ApModelsVnImport implements ToCollection, WithHeadingRow
{
  private array $results = [
    'created'        => 0,
    'skipped'        => 0,
    'errors'         => [],
    'rows_processed' => 0,
  ];

  // Lookups pre-cargados para evitar N+1
  private Collection $familias;
  private Collection $clases;
  private Collection $combustibles;
  private Collection $tiposVehiculo;
  private Collection $carrocerias;
  private Collection $tracciones;
  private Collection $transmisiones;
  private Collection $monedas;
  private Collection $tiposOp;

  public function __construct()
  {
    $this->familias      = ApFamilies::where('status', true)->pluck('id', 'description');
    $this->clases        = ApClassArticle::where('status', true)->pluck('id', 'description');
    $this->combustibles  = ApFuelType::where('status', true)->pluck('id', 'description');
    $this->tiposVehiculo = ApMasters::where('type', 'TIPO_VEHICULO')->where('status', true)->pluck('id', 'description');
    $this->carrocerias   = ApMasters::where('type', 'TIPO_CARROCERIA')->where('status', true)->pluck('id', 'description');
    $this->tracciones    = ApMasters::where('type', 'TIPO_TRACCION')->where('status', true)->pluck('id', 'description');
    $this->transmisiones = ApMasters::where('type', 'TRANSMISION_VEHICULO')->where('status', true)->pluck('id', 'description');
    $this->monedas       = TypeCurrency::where('status', true)->pluck('id', 'code');
    $this->tiposOp       = ApMasters::where('type', 'TIPO_OPERACION')->where('status', true)->pluck('id', 'description');
  }

  public function collection(Collection $rows): void
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // +2 porque fila 1 es cabecera
      try {
        $this->processRow($row->toArray(), $rowNumber);
      } catch (Exception $e) {
        $version = $this->val($row['version_descripcion_del_modelo'] ?? null) ?: "(sin versión)";
        $this->results['errors'][] = [
          'fila'    => $rowNumber,
          'version' => $version,
          'motivo'  => $e->getMessage(),
        ];
      }
    }
  }

  private function processRow(array $row, int $rowNumber): void
  {
    // Omitir filas completamente vacías
    $valores = array_filter(array_values($row), fn($v) => $v !== null && $v !== '');
    if (empty($valores)) {
      return;
    }

    $this->results['rows_processed']++;

    // ── Leer y normalizar campos de texto ────────────────────────────────────
    $tipoOpRaw  = $this->val($row['tipo_de_operacion'] ?? null);
    $familiaRaw = $this->val($row['familia'] ?? null);
    $claseRaw   = $this->val($row['clase'] ?? null);
    $version    = $this->val($row['version_descripcion_del_modelo'] ?? null);
    $combRaw    = $this->val($row['combustible'] ?? null);
    $tipVehRaw  = $this->val($row['tipo_de_vehiculo'] ?? null);
    $carrRaw    = $this->val($row['tipo_de_carroceria'] ?? null);
    $tracRaw    = $this->val($row['tipo_de_traccion'] ?? null);
    $transRaw   = $this->val($row['transmision'] ?? null);
    $monedaRaw  = $this->val($row['moneda'] ?? null);

    // ── Validar obligatorios ─────────────────────────────────────────────────
    $requeridos = [
      'Tipo de Operación' => $tipoOpRaw,
      'Familia'           => $familiaRaw,
      'Clase'             => $claseRaw,
      'Versión'           => $version,
      'Combustible'       => $combRaw,
      'Tipo de Vehículo'  => $tipVehRaw,
      'Tipo de Carrocería'=> $carrRaw,
      'Tipo de Tracción'  => $tracRaw,
      'Transmisión'       => $transRaw,
      'Moneda'            => $monedaRaw,
    ];

    $faltantes = array_keys(array_filter($requeridos, fn($v) => $v === null));
    if (!empty($faltantes)) {
      throw new Exception('Campos obligatorios vacíos: ' . implode(', ', $faltantes));
    }

    // ── Resolver FK ──────────────────────────────────────────────────────────
    $familyId        = $this->resolve($this->familias, $familiaRaw, 'Familia', $familiaRaw);
    $classId         = $this->resolve($this->clases, $claseRaw, 'Clase', $claseRaw);
    $fuelId          = $this->resolve($this->combustibles, $combRaw, 'Combustible', $combRaw);
    $vehicleTypeId   = $this->resolve($this->tiposVehiculo, $tipVehRaw, 'Tipo de Vehículo', $tipVehRaw);
    $bodyTypeId      = $this->resolve($this->carrocerias, $carrRaw, 'Tipo de Carrocería', $carrRaw);
    $tractionTypeId  = $this->resolve($this->tracciones, $tracRaw, 'Tipo de Tracción', $tracRaw);
    $transmissionId  = $this->resolve($this->transmisiones, $transRaw, 'Transmisión', $transRaw);
    $currencyId      = $this->resolveByValue($this->monedas, $monedaRaw, 'Moneda', $monedaRaw);
    $typeOperationId = $this->resolve($this->tiposOp, $tipoOpRaw, 'Tipo de Operación', $tipoOpRaw);

    $modelYear = isset($row['ano_del_modelo']) && $row['ano_del_modelo'] !== ''
      ? (int) $row['ano_del_modelo']
      : null;

    // ── Verificar duplicado ──────────────────────────────────────────────────
    $duplicate = ApModelsVn::where('family_id', $familyId)
      ->where('version', strtoupper(Str::ascii($version)))
      ->where('type_operation_id', $typeOperationId)
      ->where('model_year', $modelYear)
      ->whereNull('deleted_at')
      ->exists();

    if ($duplicate) {
      $this->results['skipped']++;
      return;
    }

    // ── Generar código (solo COMERCIAL) ──────────────────────────────────────
    $code = null;
    if ($typeOperationId === ApMasters::TIPO_OPERACION_COMERCIAL && $modelYear) {
      $code = ApModelsVn::generateNextCode($familyId, (string) $modelYear, $typeOperationId);
    }

    // ── Crear el modelo ──────────────────────────────────────────────────────
    DB::transaction(function () use (
      $familyId, $classId, $fuelId, $vehicleTypeId, $bodyTypeId,
      $tractionTypeId, $transmissionId, $currencyId, $typeOperationId,
      $version, $modelYear, $code, $row
    ) {
      ApModelsVn::create([
        'code'              => $code,
        'version'           => $version,
        'family_id'         => $familyId,
        'class_id'          => $classId,
        'fuel_id'           => $fuelId,
        'vehicle_type_id'   => $vehicleTypeId,
        'body_type_id'      => $bodyTypeId,
        'traction_type_id'  => $tractionTypeId,
        'transmission_id'   => $transmissionId,
        'currency_type_id'  => $currencyId,
        'type_operation_id' => $typeOperationId,
        'model_year'        => $modelYear,
        'power'             => $this->val($row['potencia_hp'] ?? null),
        'wheelbase'         => $this->val($row['distancia_entre_ejes_mm'] ?? null),
        'axles_number'      => $this->val($row['n_de_ejes'] ?? null),
        'width'             => $this->val($row['ancho_mm'] ?? null),
        'length'            => $this->val($row['largo_mm'] ?? null),
        'height'            => $this->val($row['alto_mm'] ?? null),
        'seats_number'      => $this->val($row['n_de_asientos'] ?? null),
        'doors_number'      => $this->val($row['n_de_puertas'] ?? null),
        'net_weight'        => $this->val($row['peso_neto_kg'] ?? null),
        'gross_weight'      => $this->val($row['peso_bruto_kg'] ?? null),
        'payload'           => $this->val($row['carga_util_kg'] ?? null),
        'displacement'      => $this->val($row['cilindrada_cc'] ?? null),
        'cylinders_number'  => $this->val($row['n_de_cilindros'] ?? null),
        'passengers_number' => $this->val($row['n_de_pasajeros'] ?? null),
        'wheels_number'     => $this->val($row['n_de_ruedas'] ?? null),
        'distributor_price' => $row['precio_distribuidor'] ?? 0,
        'transport_cost'    => $row['costo_de_transporte'] ?? 0,
        'other_amounts'     => $row['otros_montos'] ?? 0,
        'purchase_discount' => $row['descuento_de_compra'] ?? 0,
        'status'            => true,
      ]);
    });

    $this->results['created']++;
  }

  public function getResults(): array
  {
    return $this->results;
  }

  // Normaliza un valor de celda a string uppercase ASCII o null
  private function val(mixed $value): ?string
  {
    if ($value === null || $value === '') return null;
    $normalized = strtoupper(Str::ascii(trim((string) $value)));
    return $normalized === '' ? null : $normalized;
  }

  // Busca por clave (description) en el lookup
  private function resolve(Collection $lookup, string $value, string $campo, string $raw): int
  {
    $id = $lookup->get($value);
    if (!$id) {
      throw new Exception("'{$raw}' no existe en el catálogo de {$campo}. Usa los valores del dropdown.");
    }
    return $id;
  }

  // Busca por valor (para moneda donde el lookup está indexado por código)
  private function resolveByValue(Collection $lookup, string $value, string $campo, string $raw): int
  {
    $id = $lookup->search($value);
    if ($id === false) {
      $id = $lookup->get($value);
    }
    if (!$id) {
      throw new Exception("'{$raw}' no existe en el catálogo de {$campo}.");
    }
    return $id;
  }
}
