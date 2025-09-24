<?php

namespace App\Http\Services\DocumentValidation\Contracts;

interface DocumentProviderInterface
{
    /**
     * Validate a document (DNI, RUC, Driver License, etc.)
     *
     * @param string $documentType
     * @param string $documentNumber
     * @param array $additionalParams
     * @return array
     */
    public function validateDocument(string $documentType, string $documentNumber, array $additionalParams = []): array;

    /**
     * Get available document types for this provider
     *
     * @return array
     */
    public function getAvailableDocumentTypes(): array;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Check if provider is available/configured
     *
     * @return bool
     */
    public function isAvailable(): bool;
}