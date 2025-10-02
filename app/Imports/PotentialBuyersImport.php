<?php

namespace App\Imports;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PotentialBuyersImport implements ToModel, WithHeadingRow, WithValidation
{
  // Mapeo de marcas del Excel a nombres del sistema
  private $vehicleBrandMap = [
    'GWM' => 'GREAT WALL',
    'GREAT WALL' => 'GREAT WALL',
    'JAC' => 'JAC',
    'JAC CARS' => 'JAC',
    'JAC TRUCK' => 'JAC CAMIONES',
    'JAC CAMIONES' => 'JAC CAMIONES',
    // Agregar más mapeos según necesites
  ];

  // Mapeo de código de tienda a district_id (puede ser un ID o array de IDs)
  private $storeCodeToDistrictMap = [
    'PE35' => [1227, 1238], // CHICLAYO - PIMENTEL
    'PE36' => [1227, 1238], // CHICLAYO - PIMENTEL
    'PE46' => 558, // CAJAMARCA
    'PE48' => 631, // JAÉN
    'PE50' => 1549, // PIURA
  ];

  // Mapeo de código de tienda a nombre de distrito
  private $storeCodeToDistrictNameMap = [
    'PE35' => 'CHICLAYO - PIMENTEL',
    'PE36' => 'CHICLAYO - PIMENTEL',
    'PE46' => 'CAJAMARCA',
    'PE48' => 'JAÉN',
    'PE50' => 'PIURA',
  ];

  // Cache de asesores por sede y marca para distribución equitativa
  private $workersCache = [];

  // Contador de distribución round-robin por clave (sede_id + brand_id)
  private $distributionCounter = [];

  public function model(array $row)
  {
    // No crear el modelo aquí, solo retornar null
    // La creación será manejada por el ImportService
    return null;
  }

  public function transformRow(array $row)
  {
    $storeCode = $row[9] ?? $row['codigo_de_tienda'] ?? null;
    $sedeData = $this->getSedeData($storeCode, $row[10] ?? $row['marca'] ?? null);
    $vehicleBrandId = $this->getVehicleBrandId($row[10] ?? $row['marca'] ?? null);

    // Obtener el asesor asignado mediante distribución equitativa
    $workerData = $this->getAssignedWorker($sedeData['id'], $vehicleBrandId);

    // Obtener nombre del distrito desde el código de tienda
    $normalizedStoreCode = strtoupper(trim($storeCode ?? ''));
    $districtName = $this->storeCodeToDistrictNameMap[$normalizedStoreCode] ?? null;

    // Consolidar nombre completo eliminando duplicados
    $nombres = $row[4] ?? $row['nombres'] ?? '';
    $apellidos = $row[5] ?? $row['apellidos'] ?? '';
    $fullName = $this->consolidateFullName($nombres, $apellidos);

    return [
      'registration_date' => $this->parseDate($row[0] ?? $row['creado'] ?? null),
      'model' => strtoupper($row[1] ?? $row['modelo'] ?? ''),
      'version' => strtoupper($row[2] ?? $row['version'] ?? null),
      'num_doc' => $row[3] ?? $row['nro_documento'] ?? null,
      'full_name' => $fullName,
      'phone' => $row[6] ?? $row['celular'] ?? null,
      'email' => strtolower($row[7] ?? $row['correo'] ?? null),
      'campaign' => $row[8] ?? $row['campana'] ?? 'DERCO',
      'sede_id' => $sedeData['id'],
      'sede' => $sedeData['abreviatura'],
      'district' => $districtName,
      'vehicle_brand_id' => $vehicleBrandId,
      'vehicle_brand' => $row[10] ?? $row['marca'] ?? null, // Solo para referencia, no se guarda en BD
      'worker_id' => $workerData['worker_id'],
      'worker_name' => $workerData['worker_name'],
      'document_type_id' => $this->getDocumentTypeId($row[11] ?? $row['tipo_documento'] ?? null),
      'document_type' => $row[11] ?? $row['tipo_documento'] ?? null, // Solo para referencia, no se guarda en BD
      'type' => $row[12] ?? $row['tipo'] ?? 'LEADS',
      'income_sector_id' => $row[13] ?? $row['sector_ingresos_id'] ?? 829,
      'area_id' => $row[14] ?? $row['area_id'] ?? 826,
    ];
  }

  public function rules(): array
  {
    return [
      '*.num_doc' => 'required|string|max:20',
      '*.name' => 'required|string|max:100',
      '*.phone' => 'nullable|string|max:20',
      '*.email' => 'nullable|email|max:100',
      '*.sede_id' => 'required|exists:sedes,id',
      '*.vehicle_brand_id' => 'nullable|exists:ap_vehicle_brands,id',
      '*.document_type_id' => 'required|exists:ap_commercial_masters,id',
      '*.income_sector_id' => 'nullable|exists:ap_commercial_masters,id',
      '*.area_id' => 'nullable|exists:ap_commercial_masters,id',
      '*.type' => 'nullable|in:VISITA,LEADS',
    ];
  }

  public function customValidationMessages(): array
  {
    return [
      '*.num_doc.required' => 'El número de documento es obligatorio',
      '*.name.required' => 'El nombre es obligatorio',
      '*.email.email' => 'El formato del email no es válido',
      '*.sede_id.required' => 'La sede es obligatoria',
      '*.sede_id.exists' => 'La sede especificada no existe',
      '*.vehicle_brand_id.exists' => 'La marca de vehículo especificada no existe',
      '*.document_type_id.required' => 'El tipo de documento es obligatorio',
      '*.document_type_id.exists' => 'El tipo de documento especificado no existe',
      '*.income_sector_id.exists' => 'El sector de ingresos especificado no existe',
      '*.area_id.exists' => 'El área especificada no existe',
      '*.type.in' => 'El tipo debe ser: VISITA, LEADS',
    ];
  }

  private function getDocumentTypeId($documentType)
  {
    if (empty($documentType)) {
      return null;
    }

    // Si no encuentra, buscar en BD como fallback
    $master = ApCommercialMasters::where('description', 'LIKE', trim($documentType))
      ->where('type', 'TIPO_DOCUMENTO')
      ->first();
    return $master ? $master->id : null;
  }

  private function getVehicleBrandId($vehicleBrand)
  {
    if (empty($vehicleBrand)) {
      return null;
    }

    // Normalizar el texto (mayúsculas y sin espacios extra)
    $normalizedBrand = strtoupper(trim($vehicleBrand));

    // Aplicar mapeo si existe
    $mappedBrand = $this->vehicleBrandMap[$normalizedBrand] ?? $normalizedBrand;

    // Buscar en BD con el nombre mapeado
    $brand = ApVehicleBrand::where('name', 'LIKE', $mappedBrand)->first();
    return $brand ? $brand->id : null;
  }

  private function getSedeData($storeCode, $vehicleBrand)
  {
    $defaultReturn = ['id' => null, 'abreviatura' => null];

    if (empty($storeCode)) {
      return $defaultReturn;
    }

    // 1. Mapear el código de tienda a district_id (puede ser un ID o array de IDs)
    $normalizedStoreCode = strtoupper(trim($storeCode));
    $districtId = $this->storeCodeToDistrictMap[$normalizedStoreCode] ?? null;

    if (!$districtId) {
      return $defaultReturn;
    }

    // 2. Buscar la sede por district_id (soporta un ID o array de IDs)
    $query = Sede::query();

    if (is_array($districtId)) {
      $query->whereIn('district_id', $districtId);
    } else {
      $query->where('district_id', $districtId);
    }

    $sede = $query->first();

    if (!$sede) {
      return $defaultReturn;
    }

    return ['id' => $sede->id, 'abreviatura' => $sede->abreviatura];
  }

  /**
   * Obtiene el asesor asignado mediante distribución equitativa round-robin
   *
   * @param int|null $sedeId
   * @param int|null $brandId
   * @return array ['worker_id' => int|null, 'worker_name' => string|null]
   */
  private function getAssignedWorker($sedeId, $brandId)
  {
    $defaultReturn = ['worker_id' => null, 'worker_name' => null];

    if (empty($sedeId) || empty($brandId)) {
      return $defaultReturn;
    }

    // Crear clave única para esta combinación de sede + marca
    $cacheKey = "{$sedeId}_{$brandId}";

    // Si no están en cache, obtener los asesores de ApAssignBrandConsultant
    if (!isset($this->workersCache[$cacheKey])) {
      $currentYear = date('Y');
      $currentMonth = date('m');

      // Obtener asesores que tienen esta marca y sede asignada
      $workerIds = ApAssignBrandConsultant::where('sede_id', $sedeId)
        ->where('brand_id', $brandId)
        ->where('year', $currentYear)
        ->where('month', $currentMonth)
        ->where('status', 1) // Asumiendo que 1 es activo
        ->pluck('worker_id')
        ->toArray();

      if (empty($workerIds)) {
        $this->workersCache[$cacheKey] = [];
        return $defaultReturn;
      }

      // Obtener datos de los workers (id y nombre)
      $workers = Worker::whereIn('id', $workerIds)
        ->get(['id', 'nombre_completo'])
        ->map(function ($worker) {
          return [
            'worker_id' => $worker->id,
            'worker_name' => $worker->nombre_completo
          ];
        })
        ->toArray();

      $this->workersCache[$cacheKey] = $workers;
      $this->distributionCounter[$cacheKey] = 0;
    }

    // Si no hay asesores disponibles, retornar null
    if (empty($this->workersCache[$cacheKey])) {
      return $defaultReturn;
    }

    // Obtener el asesor actual según round-robin
    $workers = $this->workersCache[$cacheKey];
    $currentIndex = $this->distributionCounter[$cacheKey] % count($workers);
    $assignedWorker = $workers[$currentIndex];

    // Incrementar el contador para el siguiente registro
    $this->distributionCounter[$cacheKey]++;

    return $assignedWorker;
  }

  /**
   * Consolida el nombre completo eliminando duplicados entre nombres y apellidos
   *
   * Ejemplo:
   * - Nombres: "Carloman Saucedo estela"
   * - Apellidos: "Saucedo estela"
   * - Resultado: "CARLOMAN SAUCEDO ESTELA"
   *
   * @param string $nombres
   * @param string $apellidos
   * @return string
   */
  private function consolidateFullName($nombres, $apellidos)
  {
    // Limpiar y normalizar
    $nombres = trim($nombres ?? '');
    $apellidos = trim($apellidos ?? '');

    // Si ambos están vacíos, retornar null
    if (empty($nombres) && empty($apellidos)) {
      return null;
    }

    // Convertir a mayúsculas y dividir en palabras
    $nombresArray = array_filter(explode(' ', strtoupper($nombres)));
    $apellidosArray = array_filter(explode(' ', strtoupper($apellidos)));

    // Si solo hay nombres, retornarlos
    if (empty($apellidosArray)) {
      return implode(' ', $nombresArray);
    }

    // Si solo hay apellidos, retornarlos
    if (empty($nombresArray)) {
      return implode(' ', $apellidosArray);
    }

    // Eliminar duplicados: quitar de nombres las palabras que están en apellidos
    $nombresUnicos = array_diff($nombresArray, $apellidosArray);

    // Combinar nombres únicos + apellidos
    $fullNameArray = array_merge(array_values($nombresUnicos), $apellidosArray);

    // Retornar el nombre completo sin duplicados
    return implode(' ', $fullNameArray);
  }

  private function parseDate($date)
  {
    if (empty($date)) {
      return now()->format('Y-m-d');
    }

    try {
      // Intentar diferentes formatos de fecha
      if (is_numeric($date)) {
        // Excel serial date
        return Date::excelToDateTimeObject($date)->format('Y-m-d');
      }

      return Carbon::parse($date)->format('Y-m-d');
    } catch (Exception $e) {
      return now()->format('Y-m-d');
    }
  }
}
