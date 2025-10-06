<?php

namespace App\Http\Services\DocumentValidation;

use App\Http\Services\DocumentValidation\Contracts\DocumentProviderInterface;
use App\Http\Services\DocumentValidation\Contracts\ResponseFormatterInterface;
use App\Http\Services\DocumentValidation\Providers\FactilizaProvider;
use App\Http\Services\DocumentValidation\Formatters\StandardResponseFormatter;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Support\Facades\Cache;
use Exception;

class DocumentValidationService
{
  protected ResponseFormatterInterface $formatter;
  protected int $cacheTtl;
  protected array $providerMapping;

  public function __construct(
    ?ResponseFormatterInterface $formatter = null
  )
  {
    $this->formatter = $formatter ?? new StandardResponseFormatter();
    $this->cacheTtl = config('services.document_validation.cache_ttl', 7 * 24 * 60 * 60);
    $this->providerMapping = config('services.document_validation.provider_mapping', []);
  }

  /**
   * Validate a document with caching
   *
   * @param string $documentType
   * @param string $documentNumber
   * @param array $additionalParams
   * @param bool $useCache
   * @return array
   */
  public function validateDocument(
    string $documentType,
    string $documentNumber,
    array  $additionalParams = [],
    bool   $useCache = true
  ): array
  {
    // Normalize inputs
    $documentType = strtolower(trim($documentType));
    $documentNumber = trim($documentNumber);

    // Validate inputs
    if (empty($documentNumber)) {
      return $this->formatter->formatError(
        'Document number is required',
        $documentType,
        $documentNumber
      );
    }

    // Check if document exists in BusinessPartners first (only if isBusinessPartners = true)
    $checkBusinessPartners = $additionalParams['isBusinessPartners'] ?? false;
    if ($checkBusinessPartners) {
      $businessPartner = $this->findInBusinessPartners($documentType, $documentNumber);
      if ($businessPartner) {
        return $this->formatBusinessPartnerResponse($businessPartner, $documentType, $documentNumber);
      }
    }

    // Get provider for this document type
    $provider = $this->getProviderForDocumentType($documentType);

    if (!$this->isValidDocumentType($documentType, $provider)) {
      return $this->formatter->formatError(
        "Document type '{$documentType}' is not supported",
        $documentType,
        $documentNumber
      );
    }

    if (!$provider->isAvailable()) {
      return $this->formatter->formatError(
        'Document validation service is not available',
        $documentType,
        $documentNumber
      );
    }

    // Generate cache key
    $cacheKey = $this->generateCacheKey($documentType, $documentNumber, $additionalParams);

    // Try to get from cache first
    if ($useCache) {
      $cachedResult = Cache::get($cacheKey);
      if ($cachedResult !== null) {
        $cachedResult['source'] = 'api';
        return $cachedResult;
      }
    }

    try {
      // Call the provider
      $providerResponse = $provider->validateDocument(
        $documentType,
        $documentNumber,
        $additionalParams
      );

      // Format the response
      $formattedResponse = $this->formatter->format(
        $providerResponse,
        $documentType,
        $documentNumber
      );

      // Add source field
      $formattedResponse['source'] = 'api';

      // Cache the result if using cache
      if ($useCache) {
        Cache::put($cacheKey, $formattedResponse, $this->cacheTtl);
      }

      return $formattedResponse;

    } catch (Exception $e) {
      return $this->formatter->formatError(
        $e->getMessage(),
        $documentType,
        $documentNumber
      );
    }
  }

  /**
   * Get available document types (from default provider)
   *
   * @return array
   */
  public function getAvailableDocumentTypes(): array
  {
    $defaultProvider = $this->createProvider(
      config('services.document_validation.default_provider', 'factiliza')
    );
    return $defaultProvider->getAvailableDocumentTypes();
  }

  /**
   * Get provider information (from default provider)
   *
   * @return array
   */
  public function getProviderInfo(): array
  {
    $defaultProvider = $this->createProvider(
      config('services.document_validation.default_provider', 'factiliza')
    );

    return [
      'name' => $defaultProvider->getProviderName(),
      'available' => $defaultProvider->isAvailable(),
      'document_types' => $defaultProvider->getAvailableDocumentTypes(),
      'mapping' => $this->providerMapping,
    ];
  }

  /**
   * Clear cache for a specific document
   *
   * @param string $documentType
   * @param string $documentNumber
   * @param array $additionalParams
   * @return bool
   */
  public function clearCache(string $documentType, string $documentNumber, array $additionalParams = []): bool
  {
    $cacheKey = $this->generateCacheKey($documentType, $documentNumber, $additionalParams);
    return Cache::forget($cacheKey);
  }

  /**
   * Clear all document validation cache
   *
   * @return bool
   */
  public function clearAllCache(): bool
  {
    return Cache::flush();
  }

  /**
   * Set cache TTL
   *
   * @param int $seconds
   * @return void
   */
  public function setCacheTtl(int $seconds): void
  {
    $this->cacheTtl = $seconds;
  }

  /**
   * Get provider for a specific document type
   *
   * @param string $documentType
   * @return DocumentProviderInterface
   * @throws Exception
   */
  protected function getProviderForDocumentType(string $documentType): DocumentProviderInterface
  {
    // Check if there's a specific provider mapping for this document type
    $providerName = $this->providerMapping[$documentType] ??
      config('services.document_validation.default_provider', 'factiliza');

    return $this->createProvider($providerName);
  }

  /**
   * Create provider instance by name
   *
   * @param string $providerName
   * @return DocumentProviderInterface
   * @throws Exception
   */
  protected function createProvider(string $providerName): DocumentProviderInterface
  {
    return match (strtolower($providerName)) {
      'factiliza' => new FactilizaProvider(),
      // Aquí puedes agregar más proveedores en el futuro:
      // 'reniec_direct' => new ReniecDirectProvider(),
      // 'sunat_direct' => new SunatDirectProvider(),
      default => throw new Exception("Unknown provider: {$providerName}")
    };
  }

  /**
   * Generate cache key for document validation
   *
   * @param string $documentType
   * @param string $documentNumber
   * @param array $additionalParams
   * @return string
   */
  protected function generateCacheKey(string $documentType, string $documentNumber, array $additionalParams = []): string
  {
    $provider = $this->getProviderForDocumentType($documentType)->getProviderName();
    $params = empty($additionalParams) ? '' : '_' . md5(serialize($additionalParams));

    return "document_validation:{$provider}:{$documentType}:{$documentNumber}{$params}";
  }

  /**
   * Check if document type is valid for the given provider
   *
   * @param string $documentType
   * @param DocumentProviderInterface $provider
   * @return bool
   */
  protected function isValidDocumentType(string $documentType, DocumentProviderInterface $provider): bool
  {
    $availableTypes = array_keys($provider->getAvailableDocumentTypes());
    return in_array($documentType, $availableTypes);
  }

  /**
   * Find document in BusinessPartners table
   *
   * @param string $documentType
   * @param string $documentNumber
   * @return BusinessPartners|null
   */
  protected function findInBusinessPartners(string $documentType, string $documentNumber): ?BusinessPartners
  {
    // Solo buscar para DNI y RUC
    if (!in_array($documentType, ['dni', 'ruc'])) {
      return null;
    }

    return BusinessPartners::where('num_doc', $documentNumber)->first();
  }

  /**
   * Format BusinessPartner data to match API response structure
   *
   * @param BusinessPartners $businessPartner
   * @param string $documentType
   * @param string $documentNumber
   * @return array
   */
  protected function formatBusinessPartnerResponse(BusinessPartners $businessPartner, string $documentType, string $documentNumber): array
  {
    $baseResponse = [
      'success' => true,
      'document_type' => $documentType,
      'document_number' => $documentNumber,
      'provider' => 'database',
      'source' => 'database',
      'validated_at' => now()->toISOString(),
    ];

    if ($documentType === 'dni') {
      return array_merge($baseResponse, [
        'data' => [
          'valid' => true,
          'document_number' => $businessPartner->num_doc,
          'names' => $businessPartner->full_name,
          'first_name' => $businessPartner->first_name . ' ' . $businessPartner->middle_name,
          'paternal_surname' => $businessPartner->paternal_surname,
          'maternal_surname' => $businessPartner->maternal_surname,
          'birth_date' => $businessPartner->birth_date?->format('Y-m-d'),
          'gender' => $businessPartner->gender?->code,
          'department' => $businessPartner->district?->province?->department?->description,
          'province' => $businessPartner->district?->province?->description,
          'district' => $businessPartner->district?->description,
          'ubigeo_reniec' => $businessPartner->district?->code,
          'ubigeo_sunat' => $businessPartner->district?->code,
          'address' => $businessPartner->direction,
          'ubigeo' => $businessPartner->district?->code,
          'restricted' => false,
          'type' => $businessPartner->type,
        ]
      ]);
    }

    if ($documentType === 'ruc') {
      return array_merge($baseResponse, [
        'data' => [
          'valid' => true,
          'ruc' => $businessPartner->num_doc,
          'business_name' => $businessPartner->full_name,
          'taxpayer_type' => $businessPartner->taxClassType?->description,
          'status' => $businessPartner->company_status,
          'condition' => $businessPartner->company_condition,
          'department' => $businessPartner->district?->province?->department?->description,
          'province' => $businessPartner->district?->province?->description,
          'district' => $businessPartner->district?->description,
          'address' => $businessPartner->direction,
          'full_address' => $businessPartner->direction,
          'ubigeo_sunat' => $businessPartner->district?->ubigeo,
          'ubigeo' => $businessPartner->district?->ubigeo,
          'type' => $businessPartner->type,
        ]
      ]);
    }

    return $baseResponse;
  }
}
