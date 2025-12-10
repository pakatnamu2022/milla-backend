<?php

namespace App\Http\Services\DocumentValidation\Providers;

use App\Http\Services\DocumentValidation\Contracts\DocumentProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class FactilizaProvider implements DocumentProviderInterface
{
  protected string $baseUrl;
  protected string $token;
  protected int $timeout;

  public function __construct()
  {
    $this->baseUrl = config('services.factiliza.base_url', 'https://api.factiliza.com/v1');
    $this->token = config('services.factiliza.token');
    $this->timeout = config('services.factiliza.timeout', 30);
  }

  public function validateDocument(string $documentType, string $documentNumber, array $additionalParams = []): array
  {
    if (!$this->isAvailable()) {
      throw new Exception('Factiliza provider is not configured properly');
    }

    $endpoint = $this->getEndpointForDocumentType($documentType);

    try {
      $fullUrl = "{$this->baseUrl}/{$endpoint}/{$documentNumber}";

      $httpClient = Http::timeout($this->timeout)
        ->withHeaders([
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token,
        ]);

      // Disable SSL verification in development
      if (config('app.env') === 'local' || config('app.env') === 'development') {
        $httpClient = $httpClient->withOptions(['verify' => false]);
      }

      $response = $httpClient->get($fullUrl);

      if ($response->successful()) {
        return $response->json();
      }

      throw new Exception("HTTP Error: " . $response->status() . " - " . $response->body());

    } catch (Exception $e) {
      throw $e;
    }
  }

  public function getAvailableDocumentTypes(): array
  {
    return [
      'dni' => 'DNI - Documento Nacional de Identidad',
      'ruc' => 'RUC - Registro Único de Contribuyentes',
      'anexo' => 'RUC - Establecimientos',
      'license' => 'Carnet de Conducir',
      'soat' => 'Soat',
      'ce' => 'Carnet de Extranjería',
      'plate' => 'Placa Vehicular',
    ];
  }

  public function getProviderName(): string
  {
    return 'Factiliza';
  }

  public function isAvailable(): bool
  {
    return !empty($this->token) && !empty($this->baseUrl);
  }

  protected function getEndpointForDocumentType(string $documentType): string
  {
    $endpoints = [
      'dni' => 'dni/info',
      'ruc' => 'ruc/info',
      'anexo' => 'ruc/anexo',
      'license' => 'licencia/info',
      'soat' => 'placa/soat',
      'ce' => 'cee/info',
      'plate' => 'placa/info',
    ];

    if (!isset($endpoints[$documentType])) {
      throw new Exception("Document type '{$documentType}' is not supported by Factiliza provider");
    }

    return $endpoints[$documentType];
  }
}
