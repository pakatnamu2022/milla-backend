<?php

namespace App\Imports\ap\comercial;

use App\Http\Utils\Constants;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PotentialBuyersSocialNetworksImport implements ToModel, WithHeadingRow, WithValidation
{
  // Mapeo de marcas del Excel a nombres del sistema
  private $vehicleBrandMap = [
    'GWM' => 'GREAT WALL',
    'GREAT WALL' => 'GREAT WALL',
    'JAC' => 'JAC',
    'JAC CARS' => 'JAC',
    'JAC TRUCK' => 'JAC CAMIONES',
    'JAC CAMIONES' => 'JAC CAMIONES',
  ];

  // Lista de campañas válidas (medios de redes sociales permitidos)
  // Solo se aceptarán estos valores en la columna 'campana'
  private $validCampaigns = [
    'FACEBOOK',
    'WHATSAPP',
    'INSTAGRAM',
    'TIKTOK',
    'LINKEDIN',
    'TWITTER',
    'YOUTUBE',
    'GOOGLE ADS',
    'REDES SOCIALES',
  ];

  // Mapeo de nombres de ciudades a district_ids
  private $cityToDistrictMap = [
    'CHICLAYO' => [1227, 1238], // CHICLAYO - PIMENTEL
    'CAJAMARCA' => 558, // CAJAMARCA
    'JAEN' => 631, // JAÉN
    'PIURA' => 1549, // PIURA
  ];

  // Mapeo de nombres de ciudades a nombre de distrito (para referencia)
  private $cityToDistrictNameMap = [
    'CHICLAYO' => 'CHICLAYO - PIMENTEL',
    'CAJAMARCA' => 'CAJAMARCA',
    'JAEN' => 'JAÉN',
    'PIURA' => 'PIURA',
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

  /**
   * Transforma una fila del Excel al formato requerido
   *
   * Formato del Excel:
   * marca | modelo | version | tipo_doc | documento_cliente | nombre | apellido | celular | email | sede | fecha | campana
   *   0   |   1    |    2    |    3     |         4         |   5    |    6     |    7    |   8   |  9   |  10   |   11
   */
  public function transformRow(array $row)
  {
    // Obtener sede desde el nombre de ciudad
    $ciudad = $row[9] ?? $row['sede'] ?? null;
    $sedeData = $this->getSedeDataFromCity($ciudad);

    // Obtener marca
    $marca = $row[0] ?? $row['marca'] ?? null;
    $vehicleBrandId = $this->getVehicleBrandId($marca);

    // Obtener el asesor asignado mediante distribución equitativa
    $workerData = $this->getAssignedWorker($sedeData['id'], $vehicleBrandId);

    // Obtener nombre del distrito desde la ciudad
    $normalizedCity = strtoupper(trim($ciudad ?? ''));
    $districtName = $this->cityToDistrictNameMap[$normalizedCity] ?? null;

    // Consolidar nombre completo eliminando duplicados
    $nombre = $row[5] ?? $row['nombre'] ?? '';
    $apellido = $row[6] ?? $row['apellido'] ?? '';
    $fullName = $this->consolidateFullName($nombre, $apellido);

    // Obtener y validar campaña
    $campana = strtoupper(trim($row[11] ?? $row['campana'] ?? ''));

    // Validar que la campaña sea válida
    if (!empty($campana) && !in_array($campana, $this->validCampaigns)) {
      throw new \Exception("Campaña no válida: '{$campana}'. Las campañas válidas son: " . implode(', ', $this->validCampaigns));
    }

    return [
      'registration_date' => $this->parseDate($row[10] ?? $row['fecha'] ?? null),
      'model' => strtoupper($row[1] ?? $row['modelo'] ?? ''),
      'version' => strtoupper($row[2] ?? $row['version'] ?? null),
      'num_doc' => $row[4] ?? $row['documento_cliente'] ?? null,
      'full_name' => $fullName,
      'phone' => $row[7] ?? $row['celular'] ?? null,
      'email' => strtolower($row[8] ?? $row['email'] ?? null),
      'campaign' => $campana, // Campaña como string
      'sede_id' => $sedeData['id'],
      'sede' => $sedeData['abreviatura'],
      'district' => $districtName,
      'vehicle_brand_id' => $vehicleBrandId,
      'vehicle_brand' => $marca, // Solo para referencia
      'worker_id' => $workerData['worker_id'],
      'worker_name' => $workerData['worker_name'],
      'document_type_id' => $this->getDocumentTypeId($row[3] ?? $row['tipo_doc'] ?? null),
      'document_type' => $row[3] ?? $row['tipo_doc'] ?? null, // Solo para referencia
      'type' => 'LEADS', // Por defecto LEADS para redes sociales
      'income_sector_id' => 829, // Valor por defecto
      'area_id' => 826, // Valor por defecto
    ];
  }

  public function rules(): array
  {
    return [
      '*.num_doc' => 'required|string|max:20',
      '*.full_name' => 'required|string|max:100',
      '*.phone' => 'nullable|string|max:20',
      '*.email' => 'nullable|email|max:100',
      '*.campaign' => 'nullable|string|max:50',
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
      '*.full_name.required' => 'El nombre es obligatorio',
      '*.email.email' => 'El formato del email no es válido',
      '*.campaign.string' => 'La campaña debe ser un texto válido',
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

    // Mapeo de tipos de documento
    $documentTypeMap = [
      'DNI' => Constants::TYPE_DOCUMENT_DNI_ID,
      'RUC' => Constants::TYPE_DOCUMENT_RUC_ID,
//      'CE' => Constants::TYPE_DOCUMENT_CE_ID, // Carnet de extranjería
//      'PASAPORTE' => Constants::TYPE_DOCUMENT_PASAPORTE_ID,
    ];

    $documentTypeUpper = strtoupper(trim($documentType));
    $id_document = $documentTypeMap[$documentTypeUpper] ?? 0;

    if ($id_document === 0) {
      return null;
    }

    $master = ApCommercialMasters::where('type', 'TIPO_DOCUMENTO')
      ->where('id', $id_document)
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

  /**
   * Obtiene la sede desde el nombre de la ciudad
   */
  private function getSedeDataFromCity($cityName)
  {
    $defaultReturn = ['id' => null, 'abreviatura' => null];

    if (empty($cityName)) {
      return $defaultReturn;
    }

    // 1. Mapear el nombre de ciudad a district_id (puede ser un ID o array de IDs)
    $normalizedCity = strtoupper(trim($cityName));
    $districtId = $this->cityToDistrictMap[$normalizedCity] ?? null;

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
   * - Nombre: "Carloman Saucedo estela"
   * - Apellido: "Saucedo estela"
   * - Resultado: "CARLOMAN SAUCEDO ESTELA"
   *
   * @param string $nombre
   * @param string $apellido
   * @return string
   */
  private function consolidateFullName($nombre, $apellido)
  {
    // Limpiar y normalizar
    $nombre = trim($nombre ?? '');
    $apellido = trim($apellido ?? '');

    // Si ambos están vacíos, retornar null
    if (empty($nombre) && empty($apellido)) {
      return null;
    }

    // Convertir a mayúsculas y dividir en palabras
    $nombreArray = array_filter(explode(' ', strtoupper($nombre)));
    $apellidoArray = array_filter(explode(' ', strtoupper($apellido)));

    // Si solo hay nombre, retornarlo
    if (empty($apellidoArray)) {
      return implode(' ', $nombreArray);
    }

    // Si solo hay apellido, retornarlo
    if (empty($nombreArray)) {
      return implode(' ', $apellidoArray);
    }

    // Eliminar duplicados: quitar de nombre las palabras que están en apellido
    $nombreUnicos = array_diff($nombreArray, $apellidoArray);

    // Combinar nombres únicos + apellidos
    $fullNameArray = array_merge(array_values($nombreUnicos), $apellidoArray);

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
