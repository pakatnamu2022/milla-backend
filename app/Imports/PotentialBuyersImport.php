<?php

namespace App\Imports;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\ap\configuracionComercial\venta\ApAssignCompanyBranch;
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

  public function model(array $row)
  {
    // No crear el modelo aquí, solo retornar null
    // La creación será manejada por el ImportService
    return null;
  }

  public function transformRow(array $row)
  {
    $sedeData = $this->getSedeData($row[9] ?? $row['codigo_de_tienda'] ?? null, $row[10] ?? $row['marca'] ?? null);

    return [
      'registration_date' => $this->parseDate($row[0] ?? $row['creado'] ?? null),
      'model' => strtoupper($row[1] ?? $row['modelo'] ?? ''),
      'version' => strtoupper($row[2] ?? $row['version'] ?? null),
      'num_doc' => $row[3] ?? $row['nro_documento'] ?? null,
      'name' => strtoupper($row[4] ?? $row['nombres'] ?? null),
      'surnames' => strtoupper($row[5] ?? $row['apellidos'] ?? null),
      'phone' => $row[6] ?? $row['celular'] ?? null,
      'email' => strtolower($row[7] ?? $row['correo'] ?? null),
      'campaign' => $row[8] ?? $row['campana'] ?? 'DERCO',
      'sede_id' => $sedeData['id'],
      'sede' => $sedeData['abreviatura'],
      'vehicle_brand_id' => $this->getVehicleBrandId($row[10] ?? $row['marca'] ?? null),
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

    // 1. Buscar la sede por derco_store_code
    $sede = Sede::where('derco_store_code', trim($storeCode))->first();

    if (!$sede) {
      return $defaultReturn;
    }

    // Si no hay marca específica, retornar la sede encontrada
    if (empty($vehicleBrand)) {
      return ['id' => $sede->id, 'abreviatura' => $sede->abreviatura];
    }

    // 2. Obtener el brand_id de la marca
    $brandId = $this->getVehicleBrandId($vehicleBrand);

    if (!$brandId) {
      return ['id' => $sede->id, 'abreviatura' => $sede->abreviatura]; // Retornar sede aunque no se encuentre la marca
    }

    // 3. Verificar que la sede tenga esa marca asignada
    // Primero obtener workers de esa sede
    $currentYear = date('Y');
    $currentMonth = date('m');

    $workers = ApAssignCompanyBranch::where('sede_id', $sede->id)
      ->where('year', $currentYear)
      ->where('month', $currentMonth)
      ->pluck('worker_id');

    // Luego verificar si algún worker tiene esa marca
    $hasBrand = ApAssignBrandConsultant::whereIn('worker_id', $workers)
      ->where('brand_id', $brandId)
      ->exists();

    // Si la sede tiene la marca, retornarla
    if ($hasBrand) {
      return ['id' => $sede->id, 'abreviatura' => $sede->abreviatura];
    }

    // Si no tiene la marca, buscar cualquier sede con ese derco_store_code
    $fallbackSede = Sede::where('derco_store_code', trim($storeCode))->first();
    return $fallbackSede ? ['id' => $fallbackSede->id, 'abreviatura' => $fallbackSede->abreviatura] : $defaultReturn;
  }

//  private function getSedeId($storeCode, $vehicleBrand)
//  {
//    return $this->getSedeData($storeCode, $vehicleBrand)['id'];
//  }

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
