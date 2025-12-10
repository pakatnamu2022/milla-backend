<?php

namespace App\Http\Services\DocumentValidation\Formatters;

use App\Http\Services\DocumentValidation\Contracts\ResponseFormatterInterface;

class StandardResponseFormatter implements ResponseFormatterInterface
{
  public function format(array $providerResponse, string $documentType, string $documentNumber): array
  {
    $baseResponse = [
      'success' => true,
      'document_type' => $documentType,
      'document_number' => $documentNumber,
      'provider' => 'factiliza',
      'validated_at' => now()->toISOString(),
      'data' => null,
    ];

    return match ($documentType) {
      'dni' => array_merge($baseResponse, [
        'data' => $this->formatDniResponse($providerResponse)
      ]),
      'ruc' => array_merge($baseResponse, [
        'data' => $this->formatRucResponse($providerResponse)
      ]),
      'anexo' => array_merge($baseResponse, [
        'data' => $this->formatAnexoResponse($providerResponse)
      ]),
      'license' => array_merge($baseResponse, [
        'data' => $this->formatLicenseResponse($providerResponse)
      ]),
      'soat', 'ce' => array_merge($baseResponse, [
        'data' => $this->formatMigracionesResponse($providerResponse)
      ]),
      'plate' => array_merge($baseResponse, [
        'data' => $this->formatPlateResponse($providerResponse)
      ]),
      default => array_merge($baseResponse, [
        'data' => $this->formatGenericResponse($providerResponse)
      ]),
    };
  }

  public function formatError(string $error, string $documentType, string $documentNumber): array
  {
    return [
      'success' => false,
      'document_type' => $documentType,
      'document_number' => $documentNumber,
      'provider' => 'factiliza',
      'validated_at' => now()->toISOString(),
      'error' => $error,
      'data' => null,
    ];
  }

  protected function formatDniResponse(array $response): ?array
  {
    if (!isset($response['success']) || !$response['success']) {
      return null;
    }

    $data = $response['data'] ?? [];

    return [
      'valid' => true,
      'document_number' => $data['numero'] ?? null,
      'names' => str_replace(',', '', $data['nombre_completo']) ?? null,
      'first_name' => $data['nombres'] ?? null,
      'paternal_surname' => $data['apellido_paterno'] ?? null,
      'maternal_surname' => $data['apellido_materno'] ?? null,
      'birth_date' => $data['fecha_nacimiento'] ?? null,
      'gender' => $data['sexo'] ?? null,
      'department' => $data['departamento'] ?? null,
      'province' => $data['provincia'] ?? null,
      'district' => $data['distrito'] ?? null,
      'ubigeo_reniec' => $data['ubigeo_reniec'] ?? null,
      'ubigeo_sunat' => $data['ubigeo_sunat'] ?? null,
      'address' => $data['direccion'] ?? null,
      'ubigeo' => $data['ubigeo'] ?? null,
      'restricted' => $data['restriccion'] ?? false,
    ];
  }

  protected function formatRucResponse(array $response): ?array
  {
    if (!isset($response['success']) || !$response['success']) {
      return null;
    }

    $data = $response['data'] ?? [];

    return [
      'valid' => true,
      'ruc' => $data['numero'] ?? null,
      'business_name' => $data['nombre_o_razon_social'] ?? null,
      "taxpayer_type" => $data['tipo_contribuyente'] ?? null,
      'status' => $data['estado'] ?? null,
      'condition' => $data['condicion'] ?? null,
      'department' => $data['departamento'] ?? null,
      'province' => $data['provincia'] ?? null,
      'district' => $data['distrito'] ?? null,
      'address' => $data['direccion'] ?? null,
      'full_address' => $data['direccion_completa'] ?? null,
      'ubigeo_sunat' => $data['ubigeo_sunat'] ?? null,
      'ubigeo' => $data['ubigeo'] ?? null,
    ];
  }

  protected function formatAnexoResponse(array $response): ?array
  {
    if (!isset($response['message']) || strtolower($response['message']) !== 'exito') {
      return null;
    }

    $data = $response['data'] ?? [];

    if (empty($data) || !is_array($data)) {
      return null;
    }

    $establishments = [];
    foreach ($data as $establishment) {
      $establishments[] = [
        'code' => $establishment['codigo'] ?? null,
        'type' => $establishment['tipo_establecimiento'] ?? null,
        'activity_economic' => $establishment['actividad_economica'] ?? null,
        'address' => $establishment['direccion'] ?? null,
        'full_address' => $establishment['direccion_completa'] ?? null,
        'ubigeo_sunat' => $establishment['ubigeo_sunat'] ?? null,
        'ubigeo' => $establishment['ubigeo'] ?? null,
      ];
    }

    return [
      'valid' => true,
      'establishments' => $establishments,
    ];
  }

  protected function formatLicenseResponse(array $response): ?array
  {
    if (!isset($response['message']) || strtolower($response['message']) !== 'exito!') {
      return null;
    }

    $data = $response['data'] ?? [];

    return [
      'valid' => true,
      'license_number' => $data['numero_documento'] ?? null,
      'full_name' => $data['nombre_completo'] ?? null,
      'licencia' => $data['licencia'] ?? null,
    ];
  }

  protected function formatMigracionesResponse(array $response): ?array
  {
    if (!isset($response['success']) || !$response['success']) {
      return null;
    }

    $data = $response['data'] ?? [];

    return [
      'valid' => true,
      'document_number' => $data['numero_documento'] ?? null,
      'names' => $data['nombres'] ?? null,
      'paternal_surname' => $data['apellido_paterno'] ?? null,
      'maternal_surname' => $data['apellido_materno'] ?? null,
      'nationality' => $data['nacionalidad'] ?? null,
      'birth_date' => $data['fecha_nacimiento'] ?? null,
      'immigration_status' => $data['calidad_migratoria'] ?? null,
      'entry_date' => $data['fecha_ingreso'] ?? null,
      'expiration_date' => $data['fecha_vencimiento'] ?? null,
    ];
  }


  protected function formatPlateResponse(array $response): ?array
  {
    if (!isset($response['message']) || strtolower($response['message']) !== 'exito') {
      return null;
    }

    $data = $response['data'] ?? [];

    return [
      'valid' => true,
      'plate_number' => $data['placa'] ?? null,
      'brand' => $data['marca'] ?? null,
      'model' => $data['modelo'] ?? null,
      'series' => $data['serie'] ?? null,
      'color' => $data['color'] ?? null,
      'engine_number' => $data['motor'] ?? null,
      'vin' => $data['vin'] ?? null,
    ];
  }

  protected function formatGenericResponse(array $response): ?array
  {
    if (!isset($response['success']) || !$response['success']) {
      return null;
    }

    return [
      'valid' => true,
      'raw_data' => $response['data'] ?? [],
    ];
  }
}
